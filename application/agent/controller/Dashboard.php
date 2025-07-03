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
class Dashboard extends Agent
{

    protected $noNeedLogin = [''];

    public function _initialize()
    {
        parent::_initialize();
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
    }

    /**
     */
    public function index()
    {
        $user = $this->auth->getUserInfo();
        $sub_user_ids_fun = function ($query) use ($user) {
            $query->name('user')->where('pid_path', 'like', '%,' . $user['id'] . ',%')->field('id');
        };

        $statData = [
            //下级用户
            'sub_user_count' => Db::table('fa_user')->where('pid_path', 'like', '%,' .  $user['id'] . ',%')->count(),
            //下级代理
            'sub_agent_count' => Db::table('fa_user')->whereIn('id', $sub_user_ids_fun)->where('agent_promotion', 1)->count(),
            //下级余额
            'sub_agent_money_sum' => Db::table('fa_user')->whereIn('id', $sub_user_ids_fun)->sum('money'),
            //今日注册
            'today_register_count' => Db::table('fa_user')->whereIn('id', $sub_user_ids_fun)->whereTime('jointime', 'today')->count(),
            //今日首充用户数量（首次充值）
            'today_first_recharge_count' => Db::table('fa_user_recharge')
                ->where('is_first', 1)
                ->whereTime('create_time', 'today')
                ->where('status', 1)
                ->whereIn('user_id', $sub_user_ids_fun) // 直接使用ID数组
                ->count(),
            //今日复充用户数量（非首次充值）
            'today_recharge_count' => Db::table('fa_user_recharge')
                ->where('is_first', 0)
                ->whereTime('create_time', 'today')
                ->where('status', 1)
                ->whereIn('user_id', $sub_user_ids_fun)
                ->count(),
            //今日存款今日充值金额
            'today_recharge_amount' => Db::table('fa_user_recharge')
                ->whereTime('create_time', 'today')
                ->where('status', 1)
                ->whereIn('user_id', $sub_user_ids_fun)
                ->sum('amount'),
            //今日提现金额
            'today_withdraw_amount' => Db::table('fa_user_withdraw')
                ->whereTime('create_time', 'today')
                ->where('status', 'success')
                ->whereIn('user_id', $sub_user_ids_fun)
                ->sum('amount'),
            //今日红利
            'today_bonus_amount' => Db::table('fa_user_money_log')
                ->whereTime('create_time', 'today')
                ->whereIn('user_id', $sub_user_ids_fun)
                ->whereIn('type', ['birthday_bonus', 'upgrade_bonus', 'recharge_bonus'])
                ->sum('money'),
            //今日投注
            'today_bet_amount' => Db::table('fa_game_bet')
                ->whereTime('create_time', 'today')
                ->whereIn('user_id', $sub_user_ids_fun)
                ->where('status', 1)
                ->sum('valid_amount'),
            //今日输赢
            'today_win_amount' => Db::table('fa_game_bet')
                ->whereTime('create_time', 'today')
                ->whereIn('user_id',$sub_user_ids_fun)
                ->where('status', 1)
                ->sum('settled_amount'),
            //今日返水
            'today_rebate_amount' => Db::table('fa_promotion_rebate_log')
                ->whereTime('create_time', 'today')
                ->whereIn('user_id', [$user['id']])
                ->sum('rebate_amount'),
        ];

        $this->assign('statData', $statData);

        return $this->view->fetch();
    }
}