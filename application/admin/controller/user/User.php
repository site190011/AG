<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Db;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
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
            $list = $this->model
                ->with('group')
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

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
        }
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

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

    public function promotion_user_config() {

        $uid = $this->request->get('uid');
        $user = Db::name('user')->where('id',$uid)->find();
        $row = Db::name('promotion_user_config')->where('user_id',$uid)->find();
        $row_p = Db::name('promotion_user_config')->where('user_id',$user['pid'])->find();
        $saveMessage = '';

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            if ($params) { 
                $params = array_filter($params);

                $rebates = [
                    'rebate1' => '真人',
                    'rebate2' => '电子',
                    'rebate3' => '彩票',
                    'rebate4' => '体育',
                    'rebate5' => '电竞',
                    'rebate6' => '捕鱼',
                    'rebate7' => '棋牌',
                ];

                foreach ($rebates as $key => $value) {
                    if ($params[$key] > ($row_p[$key] - 0.1)) {
                        $this->error("{$value}不允许设置高于上级的返佣比例");
                    }
                }

                if ($row) {
                    Db::name('promotion_user_config')->where('user_id', $params['user_id'])->update($params);
                } else {
                    Db::name('promotion_user_config')->insert($params);
                }

                $saveMessage = '保存成功';

                $row = Db::name('promotion_user_config')->where('user_id', $params['user_id'])->find();
            }
        }

        

        if (!$row) {
            $row = [
                'user_id' => $uid,
            ];
        }

        $this->view->assign('row', $row);
        $this->view->assign('row_p', $row_p ?: []);
        $this->view->assign('saveMessage', $saveMessage);

        return $this->view->fetch();
    }

}
