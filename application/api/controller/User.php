<?php

namespace app\api\controller;

use app\admin\model\AgApi;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Date;
use fast\Random;
use think\Config;
use think\Db;
use think\Validate;
use think\Cache;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['getCaptcha', 'login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'));
        }
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }


    public function change_pay_password()
    {

        $pay_password = $this->request->post('pay_password');
        $pay_password_old = $this->request->post('pay_password_old');
        $pay_password_comfirm = $this->request->post('pay_password_comfirm');
        if ($pay_password != $pay_password_comfirm) {
            $this->error('两次密码不一致');
        }

        if (strlen($pay_password) != 6) {
            $this->error('支付密码长度为6位');
        }

        $user = $this->auth->getUser();

        if ($pay_password_old != $user->pay_password) {
            $this->error('旧支付密码错误');
        }

        $user->pay_password = $pay_password;
        $user->save();

        $this->success('修改成功');
    }

    public function getUserInfo()
    {
        $user = $this->auth->getUser();

        // 返回部分数据
        $user = [
            'id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'score' => $user->score,
            'level' => $user->level,
            'viplevel' => $user->viplevel,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'money' => $user->money,
            'gender' => $user->gender,
            'joinDay' => ceil((time() - $user->jointime) / 86400),
            'has_pay_password' => $user->pay_password ? true : false,
            'agent_promotion' => $user->agent_promotion,
            'invitation_code' => $user->invitation_code,
            'pid' => $user->pid,
            'pid_path' => $user->pid_path,
            'birthday' => $user->birthday,
            'bio' => $user->bio,
        ];

        $this->success('', $user);
    }

    public function setUserInfo(){
        $user = $this->auth->getUser();
        $data = $this->request->post();

        $validateRes = $this->validate($data, [
            'nickname' => 'max:20',
            'mobile' => 'max:11',
            'email' => 'max:50',
        ]);

        if ($validateRes !== true) {
            return $this->error($validateRes);
        }

        $newData = array_intersect_key($data, array_flip(['nickname', 'mobile', 'email']));

        $user->save($newData);

        return $this->success('修改成功');
    }

    /**
     * 会员登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="account", type="string", required=true, description="账号")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     */
    public function login()
    {
        $account = $this->request->post('username');
        $password = $this->request->post('password');
        $captcha = $this->request->post('captcha');
        $captcha_key = $this->request->post('captcha_key');

        if (!$captcha) {
            $this->error('验证码不能为空');
        }

        //验证码
        if (!captcha_check($captcha, 'login', $captcha_key)) {
            $this->error('验证码错误');
        }

        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function mobilelogin()
    {
        //弃用
        return;
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @ApiMethod (POST)
     * @ApiParams (name="username", type="string", required=true, description="用户名")
     * @ApiParams (name="password", type="string", required=true, description="密码")
     * @ApiParams (name="email", type="string", required=true, description="邮箱")
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="code", type="string", required=true, description="验证码")
     */
    public function register()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $invitation_code = $this->request->post('invitation_code');
        $captcha = $this->request->post('captcha');
        $captcha_key = $this->request->post('captcha_key');

        //用户名长度在3到16个字符之间,只允许使用英文大小写字母、数字、下划线，和常见的中文汉字
        if (!Validate::regex($username, "/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{3,16}$/u")) {
            $this->error('用户名无效');
        }

        //密码 6到16位，只能包含字母、数字
        if (!Validate::regex($password, "/^[a-zA-Z0-9]{6,16}$/")) {
            $this->error('密码无效');
        }

        if (!$captcha) {
            $this->error('验证码不能为空');
        }

        //验证码
        if (!captcha_check($captcha, 'register', $captcha_key)) {
            $this->error('验证码错误');
        }

        $invitation_uid = Db::name('user')->where('invitation_code', $invitation_code)->value('id') ?: 0;

        $ret = $this->auth->register($username, $password, '', '', [
            'invitation_code' => strtoupper(Random::alnum()),
            'invitation_uid' => $invitation_uid,
        ]);

        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success('注册成功', $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     * @ApiMethod (POST)
     */
    public function logout()
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    public function getCaptcha()
    {
        $type = $this->request->post('type');

        return captcha_json($type);
    }

    /**
     * 重置密码
     *
     * @ApiMethod (POST)
     * @ApiParams (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams (name="newpassword", type="string", required=true, description="新密码")
     * @ApiParams (name="captcha", type="string", required=true, description="验证码")
     */
    public function resetpwd()
    {
        //弃用
        return;
        $type = $this->request->post("type", "mobile");
        $mobile = $this->request->post("mobile");
        $email = $this->request->post("email");
        $newpassword = $this->request->post("newpassword");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        //验证Token
        if (!Validate::make()->check(['newpassword' => $newpassword], ['newpassword' => 'require|regex:\S{6,30}'])) {
            $this->error(__('Password must be 6 to 30 characters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    public function change_password()
    {

        $password = $this->request->post('password');
        $password_old = $this->request->post('password_old');
        $password_comfirm = $this->request->post('password_comfirm');

        if ($password_comfirm !== $password) {
            $this->error('两次密码不一致');
        }

        if (!Validate::make()->check(['password' => $password], ['password' => 'require|regex:\S{6,30}'])) {
            $this->error('密码必须大于6位');
        }

        $user = $this->auth->getUser();

        if ($user->password != $this->auth->getEncryptPassword($password_old, $user->salt)) {
            $this->error('旧密码错误');
        }

        $ret = $this->auth->changepwd($password, $password_old, false);

        if ($ret) {
            $this->success('修改成功');
        } else {
            $this->error($this->auth->getError());
        }
    }

    public function realname()
    {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $real_name = $this->request->post('real_name');
        $id_card_number = $this->request->post('id_card_number');
        $id_card_image1 = $this->request->post('id_card_image1');
        $id_card_image2 = $this->request->post('id_card_image2');
        $remark = $this->request->post('remark');
dd($real_name);
        $exist = Db::name('user_realname')->where('user_id', $user_id)->find();

        if ($exist) {
            if($remark['status'] == 0) {
                $this->error('已提交，等待审核');
            } else if ($remark['status'] == 1) {
                $this->error('已审核通过');
            }

            Db::name('user_realname')->where('user_id', $user_id)->update([
                'real_name' => $real_name,
                'id_card_number' => $id_card_number,
                'id_card_image1' => $id_card_image1,
                'id_card_image2' => $id_card_image2,
                'remark' => '',
                'status' => '0',
                'update_time' => Date('Y-m-d H:i:s')
            ]);
        } else {
            Db::name('user_realname')->insert([
                'real_name' => $real_name,
                'id_card_number' => $id_card_number,
                'id_card_image1' => $id_card_image1,
                'id_card_image2' => $id_card_image2,
                'remark' => '',
                'status' => '0',
                'create_time' => Date('Y-m-d H:i:s'),
                'update_time' => Date('Y-m-d H:i:s'),
            ]);
        }

        $this->success('提交成功');

    }

    public function add_bank()
    {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $card_number = $this->request->post('card_number');
        $card_holder = $this->request->post('card_holder');
        $bank_name = $this->request->post('bank_name');
        $bank_name2 = $this->request->post('bank_name2');
        $bank_code = $this->request->post('bank_code');
        $type = $this->request->post('type');

        if ($type == 'bank') {
            if (!$card_number || !$card_holder || !$bank_name || !$bank_name2) {
                $this->error('参数错误');
            }
        } elseif ($type == 'virtual') {
            if (!$card_number || !$card_holder ) {
                $this->error('参数错误');
            }
        } elseif ($type == 'third') {
            if (!$card_number || !$card_holder ) {
                $this->error('参数错误');
            }
        }

        $where = [
            'user_id' => $user_id,
            'type' => $type,
        ];

        $existCount = Db::table('fa_user_bank_card')->where($where)->count();

        switch ($type) {
            case 'bank':
                if ($existCount >= 10) {
                    $this->error('最多只能添加10张银行卡');
                }
            break;
            case 'virtual':
                if ($existCount >= 10) {
                    $this->error('最多只能添加5个地址');
                }
            break;
            case 'third':
                if ($existCount >= 10) {
                    $this->error('最多只能添加5个地址');
                }
            break;
            default:
                $this->error('type参数错误');
            break;
        }

        $bank = Db::table('fa_user_bank_card')->where($where)->where('card_number', $card_number)->find();

        if ($bank) {
            $this->error('银行卡已存在');
        } else {
            $data = [
                'user_id' => $user_id,
                'card_number' => $card_number,
                'card_holder' => $card_holder,
                'bank_name' => $bank_name,
                'bank_code' => $bank_code,
                'type' => $type,
                'is_default' => 0,
                'status' => 1,
            ];

            $res = Db::table('fa_user_bank_card')->insert($data);

            if ($res) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }
    }

    public function del_bank()
    {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $id = $this->request->post('id');
        if (!$id) {
            $this->error('参数错误');
        }
        $res = Db::table('fa_user_bank_card')->where('user_id', $user_id)->where('id', $id)->delete();
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function set_default_bank()
    {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $id = $this->request->post('id');
        $type = $this->request->post('type');

        if (!$id) {
            $this->error('参数错误');
        }

        $where = [
            'user_id' => $user_id,
            'type' => $type
        ];

        $res = Db::table('fa_user_bank_card')->where($where)->update(['is_default' => 0]);
        if ($res) {
            $res = Db::table('fa_user_bank_card')->where($where)->where('id', $id)->update(['is_default' => 1]);
            if ($res) {
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        } else {
            $this->error('设置失败');
        }
    }

    public function update_bank()
    {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $id = $this->request->post('id');
        $card_number = $this->request->post('card_number');
        $card_holder = $this->request->post('card_holder');
        $bank_name = $this->request->post('bank_name');
        if (!$id || !$card_number || !$card_holder || !$bank_name) {
            $this->error('参数错误');
        }
        $res = Db::table('fa_user_bank_card')->where('user_id', $user_id)->where('id', $id)->update([
            'card_number' => $card_number,
            'card_holder' => $card_holder,
            'bank_name' => $bank_name,
        ]);
        if ($res) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    public function get_bank_list()
    {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $bank_list = Db::table('fa_user_bank_card')->where('type', 'bank')->where('user_id', $user_id)->select();
        $virtual_list = Db::table('fa_user_bank_card')->where('type', 'virtual')->where('user_id', $user_id)->select();
        $third_list = Db::table('fa_user_bank_card')->where('type', 'third')->where('user_id', $user_id)->select();

        $bankList = (array)Db::table('fa_bank')->select();
        $bankList = array_column($bankList, null, 'bank_code');

        if ($bank_list) {
            foreach($bank_list as &$item) {
                $item['logo'] = $bankList[$item['bank_code']]['logo'] ?? '';
            }
        }

        if ($bank_list) {
            foreach($virtual_list as &$item) {
                $item['logo'] = $bankList[$item['bank_code']]['logo'] ?? '';
            }
        }

        if ($third_list) {
            foreach($third_list as &$item) {
                $item['logo'] = $bankList[$item['bank_code']]['logo'] ?? '';
            }
        }

        $this->success('获取成功', ['bank_list' => $bank_list, 'virtual_list' => $virtual_list, 'third_list' => $third_list]);
    }

    public function get_all_bank_list(){
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $bank_list = Db::table('fa_user_bank_card')->where('user_id', $user_id)->select();

        $this->success('获取成功', $bank_list);

    }

    public function get_game_bet() {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $field = 'plat_type,currency,game_type,game_name,valid_amount,settled_amount,bet_time,status';

        $startDate = $this->request->get('startDate');
        $endDate = $this->request->get('endDate');
        $plat_type = $this->request->get('game_plat_type');

        $where = [
            'user_id' => $user_id,
            'bet_time' => ['between', [$startDate, $endDate]],
            'plat_type' => $plat_type,
        ];

        if ($plat_type == '__all__'){
            unset($where['plat_type']);
        }

        $bet_list = Db::table('fa_game_bet')->field($field)->where($where)->order('id desc')->paginate(20);

        $betTotalVaildAmount = Db::table('fa_game_bet')->where($where)->sum('valid_amount');
        $betTotalSettledAmount = Db::table('fa_game_bet')->where($where)->sum('settled_amount');
        $betTotalAmount = Db::table('fa_game_bet')->where($where)->sum('bet_amount');
        
        $this->success('获取成功', [
            'bet_list' => $bet_list,
            'betTotalVaildAmount' => $betTotalVaildAmount,
            'betTotalSettledAmount' => $betTotalSettledAmount,
            'betTotalAmount' => $betTotalAmount,
        ]);
    }

    function monery_log(){
        $user = $this->auth->getUser();

        $startDate = $this->request->get('startDate');
        $endDate = $this->request->get('endDate');
        $type = $this->request->get('type');
        $timeRange = $this->request->get('timeRange');
        $page = $this->request->get('page');
        $where = [
            'user_id' => $user->id,
        ];

        //type类型的值
        // recharge: 存款
        // withdraw: 取款
        // rebate: 返水
        // birthday: 生日礼金
        // promotion: 晋级礼金
        // monthlyRedPacket: 每月红包
        // exclusive: 专属豪礼
        

        switch ($timeRange) {
            case 'today':
                $where['create_time'] = ['between', [strtotime(date('Y-m-d')), time()]];
                break;
            case 'yesterday':
                $where['create_time'] = ['between', [strtotime(date('Y-m-d', strtotime("-1 day"))), strtotime(date('Y-m-d')) - 1]];
                break;
            case '7day':
                $where['create_time'] = ['between', [strtotime(date('Y-m-d', strtotime("-7 day"))), time()]];
                break;
            case 'week':
                $where['create_time'] = ['between', [strtotime(date('Y-m-d', strtotime("-1 week"))), time()]];
                break;
            case '30day':
                $where['create_time'] = ['between', [strtotime(date('Y-m-d', strtotime("-30 day"))), time()]];
                break;
            case 'month':
                $where['create_time'] = ['between', [strtotime(date('Y-m-d', strtotime("-1 month"))), time()]];
                break;
            case 'custom':
                $where['create_time'] = ['between', [$startDate, $endDate]];
                break;
        }

        if ($type != '__all__'){
            $where['type'] = $type;
        }

        $log_list = Db::table('fa_user_money_log')->where($where)->order('id desc')->paginate(20);

        $this->success('获取成功', $log_list);
    }

    public function withdraw()
    {
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $amount = $this->request->post('amount');
        $password = $this->request->post('password');
        $bank_id = $this->request->post('bank_id');

        if (config('site.withdraw_switch') !== 'off') {
            $this->error('提现功能已关闭');
        }

        $time = time();
        if (config('site.withdraw_time') && config('site.withdraw_time') != '*') {
            $withdraw_time = explode('-', config('site.withdraw_time'));
            if (count($withdraw_time) != 2) {
                $this->error('提现时间段配置错误');
            }
            $start_time = strtotime(date('Y-m-d', $time) . ' ' . $withdraw_time[0]);
            $end_time = strtotime(date('Y-m-d', $time) . ' ' . $withdraw_time[1]);

            if ($time < $start_time || $time > $end_time) {
                $this->error('提现时间段为' . $withdraw_time[0] . '-' . $withdraw_time[1]);
            }
        }

        if ($amount < config('site.min_withdraw')) {
            $this->error('提现金额不能小于' . config('site.min_withdraw'));
        }

        if ($amount > $user->money) {
            $this->error('提现金额不能大于余额');
        }

        if ($amount < config('site.max_withdraw')) {
            $this->error('提现金额不能大于' . config('site.max_withdraw'));
        }

        $bank_info = Db::table('fa_user_bank_card')->where('id', $bank_id)->find();

        if (!$bank_info) {
            $this->error('银行卡不存在');
        }

        if ($bank_info['user_id'] != $user_id) {
            $this->error('银行卡不属于当前用户');
        }

        $withdraw_info = Db::table('fa_user_withdraw')->where('user_id', $user_id)->where('status', 'processing')->find();

        if ($withdraw_info) {
            $this->error('您有未处理的提现申请，请处理后再次申请');
        }

        if ($user->pay_password != $password) {
            $this->error('交易密码错误');
        }

        Db::startTrans();

        try {
            Db::table('fa_user')->where('id', $user_id)->setDec('money', $amount);
            $lastInsID = Db::table('fa_user_withdraw')->insertGetId([
                'user_id' => $user_id,
                'amount' => $amount,
                'status' => 'processing',
                'create_time' => Date('Y-m-d H:i:s'),
                'type' => $bank_info['type'], //类型 bank:银行卡,virtual:虚拟币
                'card_number' => $bank_info['card_number'], //卡号/虚拟币地址
                'bank_code' => $bank_info['bank_code'],//银行编码
                'card_holder' => $bank_info['card_holder'],//持卡人姓名
                'bank_name' => $bank_info['bank_name'],//银行名称
                'bank_name2' => $bank_info['bank_name2'],//开户行
            ]);
            Db::table('fa_user_money_log')->insert([
                'user_id' => $user_id,
                'money' => -$amount,
                'before' => $user->money,
                'after' => $user->money - $amount,
                'memo' => '申请提现',
                'create_time' => Date("Y-m-d H:i:s"),
                'type' => 'withdraw',
                'related_table' => 'user_withdraw',
                'related_table_ids' => $lastInsID,
            ]);
            
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('提现失败:' . $e->getMessage());
        }

        $this->success('提现申请成功，请等待审核');
    }

    // public function get_wallet(){
    //     $user = $this->auth->getUser();
    //     $wallet = $user->getWallet();

    //     if ($wallet['has_money_in_game'] == 1) {
    //         $user->transferAll();
    //     }

    //     $this->success('获取成功',$wallet);
    // }

    /**
     * 充值
     */
    public function recharge(){
        $user = $this->auth->getUser();
        $user_id = $user->id;
        $amount = $this->request->post('amount');
        $bank_id = $this->request->post('bank_id');
        $transaction_image = $this->request->post('transaction_image');

        if (config('site.recharge_switch') !== 'off') {
            $this->error('充值功能已关闭');
        }

        if(!$bank_id) {
            $this->error('请选择充值渠道');
        }

        $bank = Db::name('bank')->where('id', $bank_id)->find();

        if(!$bank) {
            $this->error('充值渠道不存在');
        }

        if($bank['status'] !== 1) {
            $this->error('充值渠道已关闭');
        }

        $min_recharge = $bank['min_recharge'] ?? config('site.min_recharge');
        $max_recharge = $bank['max_recharge'] ?? config('site.max_recharge');

        if($min_recharge > $amount) {
            $this->error('充值金额不能低于' . $min_recharge);
        }

        if($max_recharge < $amount) {
            $this->error('充值金额不能高于' . $max_recharge);
        }

        Db::name('user_recharge')->insert([
            'user_id' => $user_id,
            'amount' => $amount,
            'status' => 0,
            'success_time' => 0,
            'create_time' => time(),
            'update_time' => time(),
            'bank_id' => $bank_id,
            'currency' => 'cny',
            'transaction_image' => $transaction_image,
        ]);

        $this->success('提交成功,等待确认结果');

    }
}
