<?php

namespace app\agent\controller;

use app\common\controller\Agent;
use fast\Random;
use think\Config;
use think\Db;
use think\Hook;
use think\Session;
use think\Validate;

/**
 * @internal
 */
class User extends Agent
{

    protected $noNeedLogin = [''];
    protected $model;

    public function _initialize()
    {
        parent::_initialize();
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
        $this->model = new \app\agent\model\User;

    }

    /**
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
            $user = $this->auth->getUserInfo();
            $sub_user_ids_fun = function ($query) use ($user) {
                $query->name('user')->where('pid_path', 'like', '%,' . $user['id'] . ',%')->field('id');
            };
            $list = $this->model
                ->with('group')
                ->whereIn('user.id', $sub_user_ids_fun)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }

        if ($_GET['pid'] ?? false) {
            $user = \app\admin\model\User::get($_GET['pid']);
            if ($user) {
                $pids = explode(',', $user->pid_path);
                $pids[] = $user->id;
                $parents = \app\admin\model\User::where('id', 'in', $pids)->order('id asc')->select();
                $this->view->assign('parents',$parents);
            }
        }

        return $this->view->fetch();
    }

    public function add(){

        $this->model->event('before_insert', function ($user) {
            $parentUser = $this->auth->getUserInfo();
            $user->pid_path = $parentUser['pid_path'] . $parentUser['id'] . ',';
            $user->pid = $parentUser['id'];
            $user->salt = Random::alnum();
            $user->password = $this->auth->getEncryptPassword($user->password, $user->salt);
            $user->invitation_code = strtoupper(Random::alnum());
            $user->invitation_uid = $parentUser['id'];
            $user->jointime = time();
            $user->createtime = time();
            $user->joinip = request()->ip();
        });

        return parent::add();

    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::edit($ids);
    }
}