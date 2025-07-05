<?php

namespace app\agent\controller;

use app\common\controller\Agent;
use think\Config;
use think\Db;
use think\Hook;
use think\Session;
use think\Validate;

/**
 * @internal
 */
class Gamerecord extends Agent
{

    protected $noNeedLogin = [''];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $user = $this->auth->getUserInfo();
            $sub_user_ids_fun = function ($query) use ($user) {
                $query->name('user')->where('pid_path', 'like', '%,' . $user['id'] . ',%')->field('id');
            };
            $list = Db::name('game_bet')
                ->whereIn('user_id', $sub_user_ids_fun)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }

        return $this->view->fetch(); 
    }
}