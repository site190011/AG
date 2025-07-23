<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 用户资金统计
 *
 * @icon fa fa-circle-o
 */
class Total extends Backend
{

    /**
     * Recharge模型对象
     * @var \app\admin\model\user\Recharge
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\User;
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // var_dump($where);
            $filter = $this->request->get('filter','');
            $filter = json_decode($filter,true);
            $whereTime = [];
            if(!empty($filter['jointime'])){
                $whereTime['create_time'] = ['between',explode('-',$filter['jointime'])];
                unset($filter['jointime']);
            }
            $list = $this->model
                    ->where($filter)
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as &$v) {
                $v['recharge'] = Db::name('user_recharge')->where(['status'=>'1','user_id'=>$v['id']])->where($whereTime)->sum('amount');
                $v['recharge_num'] = Db::name('user_recharge')->where(['status'=>'1','user_id'=>$v['id']])->where($whereTime)->count();
                $v['recharge_gift'] =0;
                $v['withdraw'] = Db::name('user_withdraw')->where(['status'=>'success','user_id'=>$v['id']])->where($whereTime)->sum('amount');
                $v['withdraw_num'] = Db::name('user_withdraw')->where(['status'=>'success','user_id'=>$v['id']])->where($whereTime)->count();
                
                $v['add'] = Db::name('user_money_log')->where('user_id',$v['id'])->where('money > 0')->where($whereTime)->sum('money');
                $v['sub'] = Db::name('user_money_log')->where('user_id',$v['id'])->where('money < 0')->where($whereTime)->sum('money');
             
                $v['discount'] = 0;
                $v['red'] = Db::name('user_money_log')->where('user_id',$v['id'])->where('money','in',['birthday','VipUpgrade','monthlyRedPacket','exclusive'])->where($whereTime)->sum('money');
                $v['activity'] = 0;
                $v['defect'] =  Db::name('user_money_log')->where('user_id',$v['id'])->where('money','game_rebate')->where($whereTime)->sum('money');
                
                $v['bet'] = Db::name('game_bet')->where(['user_id'=>$v['id']])->where($whereTime)->sum('bet_amount');
                $v['valid'] = Db::name('game_bet')->where(['user_id'=>$v['id'],'status'=>'1'])->where($whereTime)->sum('valid_amount');
                $v['settled'] = Db::name('game_bet')->where(['user_id'=>$v['id'],'status'=>'1'])->where($whereTime)->sum('settled_amount');
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }

        return $this->view->fetch();
    }

}
