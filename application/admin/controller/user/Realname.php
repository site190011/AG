<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;

/**
 * 用户实名管理
 *
 * @icon fa fa-circle-o
 */
class Realname extends Backend
{

    /**
     * Realname模型对象
     * @var \app\admin\model\user\Realname
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\user\Realname;
        $this->view->assign("statusList", $this->model->getStatusList());
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

        $data = [
            'remark' => $params['remark'],
        ];

        Db::startTrans();

        if ($params['audit'] == 'pass') {
            $data['status'] = 1;
        } else if ($params['audit'] == 'refuse') {
            $data['status'] = 2;
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
