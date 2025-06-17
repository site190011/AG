<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 用户提现记录管理
 *
 * @icon fa fa-circle-o
 */
class Withdraw extends Backend
{

    /**
     * Withdraw模型对象
     * @var \app\admin\model\user\Withdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\user\Withdraw;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("typeList", $this->model->getTypeList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

     public function edit($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($row->process_time) {
            $this->error('已处理，请勿重复处理');
        }

        $data = [
            'process_time' => Date("Y-m-d H:i:s"),
            'remark' => $params['remark'],
        ];

        Db::startTrans();

        if ($params['audit'] == 'pass') {
            $data['status'] = 'success';
        } else if ($params['audit'] == 'refuse') {
            $data['status'] = 'failed';
            //退回金额
            $user = Db::table('fa_user')->where('id', $row->user_id)->find();
            $user_id = $row->user_id;
            $amount = $row->amount;

            Db::table('fa_user')->where('id', $user_id)->setInc('money', $amount);
            Db::table('fa_user_money_log')->insert([
                'user_id' => $user_id,
                'money' => $amount,
                'before' => $user['money'],
                'after' => $user['money'] + $amount,
                'memo' => '提现失败',
                'create_time' => Date("Y-m-d H:i:s"),
                'type' => 'withdraw',
            ]);
        }

        $result = $row->allowField(true)->save($data);

        if ($result !== false) {
            Db::commit();
            $this->success();
        } else {
            Db::rollback();
            $this->error($row->getError());
        }

     }


}
