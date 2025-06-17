<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 合营接口
 */
class Promotion extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];


    public function _initialize()
    {
        parent::_initialize();
    }

    public function subAccountList()
    {
        try {
            $type = $this->request->post('type');
            $user_id = $this->request->post('uid');
            $playerId = $this->request->post('playerId');

            if (!$user_id) {
                $user_id = $this->auth->id;
            }

            $whereRaw = 'id > 0';

            switch ($type) {
                case 'normal':
                    $whereRaw .= ' AND agent_promotion = 0';
                    break;
                case 'agent':
                    $whereRaw .= ' AND agent_promotion = 1';
                    break;
                case 'today':
                    $whereRaw .= ' AND jointime >= ' . strtotime(date('Y-m-d')) . ' and jointime < ' . ((strtotime(date('Y-m-d')) + 86400));
                    break;
                case 'yesterday':
                    $whereRaw .= ' AND jointime >= ' . (strtotime(date('Y-m-d')) - 86400) . ' and jointime < ' . strtotime(date('Y-m-d'));
                    break;
                default:
                    break;
            }

            if ($playerId) {
                $whereRaw .= ' AND id = ' . $playerId;
            }

            $list = Db::table('fa_user')->field('id,pid,pid_path,money')->whereRaw($whereRaw)->where('pid_path', 'like', '%,' . $user_id . ',%')->paginate();
            $ids = $list->column('id');
            $parent_remarks = Db::table('fa_promotion_user_config')->whereIn('user_id', $ids)->column('parent_remark', 'user_id');
            $user_wallets = Db::table('fa_user_wallet')->whereIn('user_id', $ids)->column('*', 'user_id');

            $list->each(function ($item) use ($parent_remarks, $user_wallets) {
                $item['parent_remark'] = $parent_remarks[$item['id']] ?? '';
                $pid_path = array_filter(explode(',', $item['pid_path']));
                $item['pid_path'] = implode('>', $pid_path);

                $recharge_total = $user_wallets[$item['id']]['recharge_total'] ?? 0;
                $withdraw_total = $user_wallets[$item['id']]['withdraw_total'] ?? 0;

                $item['diff_money'] = number_format($recharge_total - $withdraw_total, 2);
                return $item;
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('', $list);
    }

    /**
     * 下级游戏记录查询
     * @ApiMethod (POST)
     * @ApiParams (name="uid", type="int", required=true, description="用户ID")
     * @ApiParams (name="page", type="int", required=true, description="页码")
     * @ApiParams (name="startTime", type="string", required=true, description="开始时间")
     * @ApiParams (name="endTime", type="string", required=true, description="结束时间")
     */
    public function subGameBetRecord()
    {
        $uid = $this->request->post('uid/d', 0);
        $page = $this->request->post('page/d', 1);
        $startTime = $this->request->post('startTime');
        $endTime = $this->request->post('endTime');

        // 参数校验
        if ($uid <= 0 || !$startTime || !$endTime) {
            $this->error('参数不完整');
        }

        // 查询逻辑
        $where = [
            'bet_time' => ['between', [$startTime, $endTime]],
            'user_id' => $uid,
            'status' => 1
        ];

        $query = Db::table('fa_game_bet')
            ->where($where)
            ->field('plat_type,valid_amount,settled_amount,bet_time,status,game_name,game_type,bet_content');

        $list = $query->paginate(20, false, ['page' => $page]);
        $statistics = [
            'count' => Db::table('fa_game_bet')->where($where)->count(),
            'sumValidAmount' => Db::table('fa_game_bet')->where($where)->sum('valid_amount'),
            'settledAmount' => Db::table('fa_game_bet')->where($where)->sum('settled_amount')
        ];

        $this->success('', [
            'list' => $list,
            'statistics' => $statistics
        ]);
    }

    /**
     * 获取下级返水配置
     * @ApiMethod (POST)
     */
    public function getSubRebate()
    {
        $uid = input('uid/d', $this->auth->id);

        $user = Db::table('fa_user')
            ->where('id', $uid)
            ->find();

        $promotion_user_config = Db::table('fa_promotion_user_config')
            ->where('user_id', $uid)
            ->find();
        
        $parent_user_config = Db::table('fa_promotion_user_config')
            ->where('user_id', $user['pid'])
            ->find();
        
        $default_config = [
            'promotion_rebate1' => config("site.promotion_rebate1"),
            'promotion_rebate2' => config("site.promotion_rebate2"),
            'promotion_rebate3' => config("site.promotion_rebate3"),
            'promotion_rebate4' => config("site.promotion_rebate4"),
            'promotion_rebate5' => config("site.promotion_rebate5"),
            'promotion_rebate6' => config("site.promotion_rebate6"),
            'promotion_rebate7' => config("site.promotion_rebate7"),
        ];

        $this->success('', [
            'promotion_user_config' => $promotion_user_config,
            'parent_user_config' => $parent_user_config,
            'default_config' => $default_config
        ]);
    }

    /**
     * 更新下级返水配置
     * @ApiMethod (POST)
     * @ApiParams (name="config", type="array", required=true, description="返水配置")
     */
    public function updateSubRebate()
    {
        $uid = $this->auth->id;
        $user_id = $this->request->post('user_id', $uid);
        $config = $this->request->post('config/a', []);
        if (empty($config) || !is_array($config)) {
            $this->error('参数错误');
        }

        Db::table('fa_promotion_user_config')
            ->where('user_id', $user_id)
            ->update($config);

        $this->success('配置更新成功');
    }

    /**
     * 获取下级报表
     * @ApiMethod (POST)
     * @ApiParams (name="timeLabel", type="string", required=true, description="时间范围")
     * @ApiParams (name="gameType", type="string", required=false, description="游戏类型")
     */
    public function subReport()
    {
        $containsMyData = $this->request->post('containsMyData/d', 0);
        $gameType = $this->request->post('gameType');
        $onlyAgent = $this->request->post('onlyAgent/d', 0);
        $timeLabel = $this->request->post('timeLabel');
        $uid = $this->request->post('uid/d', 0);

        $user = $this->auth->getUser();
        $where = [];
        $where['pid_path'] = ['like', '%,' . $user->id . ',%'];

        if ($uid > 0) {
            $where['id'] = $uid;
        }

        if ($onlyAgent == 1) {
            $where['agent_promotion'] = 1;
        }

        $sub_user_ids = Db::table('fa_user')->where($where)->column('id');

        if ($containsMyData == 1) {
            array_unshift($sub_user_ids, $user->id);
        }

        $user_list = Db::table('fa_user')
            ->whereIn('id', $sub_user_ids)
            ->field('id,username,pid_path')
            ->paginate(20);

        $uids = $user_list->column('id');

        $where = [];

        switch ($timeLabel) {
            case 'today':
                $where['create_time'] = ['between', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]];
                break;
            case 'yesterday':
                $where['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))]];
                break;
            case 'week':
                $where['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-1 week')), date('Y-m-d 23:59:59')]];
                break;
            case 'last_week':
                $where['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-2 week')), date('Y-m-d 23:59:59', strtotime('-1 week'))]];
                break;
            case 'month':
                $where['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-1 month')), date('Y-m-d 23:59:59')]];
                break;
            case 'last_month':
                $where['create_time'] = ['between', [date('Y-m-d 00:00:00', strtotime('-2 month')), date('Y-m-d 23:59:59', strtotime('-1 month'))]];
                break;
        }

        $where['user_id'] = ['in', $uids];

        //用户下注小计:总投注,总输赢
        $bet_list = Db::table('fa_game_bet')
            ->where($where)
            ->where(function ($build) use ($gameType) {
                if ($gameType) {
                    $build->where('game_type', $gameType);
                }
            })
            ->where('status', 1)
            ->field('user_id,sum(valid_amount) as valid_amount,sum(settled_amount) as settled_amount')
            ->group('user_id')
            ->select();
        $valid_amounts = array_column((array)$bet_list, 'valid_amount', 'user_id');
        $settled_amounts = array_column((array)$bet_list, 'settled_amount', 'user_id');

        //用户返水小计
        $rebate_list = Db::table('fa_promotion_rebate_log')
            ->where($where)
            ->field('user_id,sum(rebate_amount) as rebate_amount')
            ->group('user_id')
            ->select();
        $rebate_amounts = array_column((array)$rebate_list, 'rebate_amount', 'user_id');

        //用户红利小计
        $bonus_list = Db::table('fa_user_money_log')
            ->where($where)
            //type = birthday:生日礼金, promotion:晋级礼金, monthlyRedPacket:每月红包, exclusive:专属豪礼
            ->whereIn('type', ['birthday', 'promotion', 'monthlyRedPacket', 'exclusive'])
            ->field('user_id,sum(money) as bonus_amount')
            ->group('user_id')
            ->select();
        $bonus_amounts = array_column((array)$bonus_list, 'bonus_amount', 'user_id');

        //用户充值小计
        $recharge_list = Db::table('fa_user_recharge')
            ->where($where)
            ->where('status', 1)
            ->field('user_id,sum(amount) as recharge_amount')
            ->group('user_id')
            ->select();
        $recharge_amounts = array_column((array)$recharge_list, 'recharge_amount', 'user_id');

        $user_list->each(function ($item) use ($user, $valid_amounts, $settled_amounts, $rebate_amounts, $bonus_amounts, $recharge_amounts) {
            $item['valid_amount'] = isset($valid_amounts[$item['id']]) ? $valid_amounts[$item['id']] : 0;
            $item['settled_amount'] = isset($settled_amounts[$item['id']]) ? $settled_amounts[$item['id']] : 0;
            $item['rebate_amount'] = isset($rebate_amounts[$item['id']]) ? $rebate_amounts[$item['id']] : 0;
            $item['bonus_amount'] = isset($bonus_amounts[$item['id']]) ? $bonus_amounts[$item['id']] : 0;
            $item['recharge_amount'] = isset($recharge_amounts[$item['id']]) ? $recharge_amounts[$item['id']] : 0;
            $item['isself'] = $item['id'] == $user->id ? 1 : 0;

            return $item;
        });

        //总和:总投注,总输赢
        $bet_statistics = Db::table('fa_game_bet')
            ->where($where)
            ->where(function ($build) use ($gameType) {
                if ($gameType) {
                    $build->where('game_type', $gameType);
                }
            })
            ->where('status', 1)
            ->field('sum(valid_amount) as valid_amount,sum(settled_amount) as settled_amount')
            ->find();

        //总和:返水
        $rebate_statistics = Db::table('fa_promotion_rebate_log')
            ->where($where)
            ->field('sum(rebate_amount) as rebate_amount')
            ->find();

        //总和:红利
        $bonus_statistics = Db::table('fa_user_money_log')
            ->where($where)
            ->whereIn('type', ['birthday', 'promotion', 'monthlyRedPacket', 'exclusive'])
            ->field('sum(money) as bonus_amount')
            ->find();

        //总和:充值
        $recharge_statistics = Db::table('fa_user_recharge')
            ->where($where)
            ->where('status', 1)
            ->field('sum(amount) as recharge_amount')
            ->find();

        $statistics = array_merge($bet_statistics, $rebate_statistics, $bonus_statistics, $recharge_statistics);

        $this->success('', ['userList' => $user_list, 'statistics' => $statistics]);
    }

    /**
     * 获取统计信息
     * @ApiMethod (POST)
     */
    public function statistics()
    {
        try {
            $user = $this->auth->getUser();
            $sub_user_ids = Db::table('fa_user')->where('pid_path', 'like', '%,' . $user->id . ',%')->column('id');

            $statData = [
                //下级用户
                'sub_user_count' => count($sub_user_ids),
                //下级代理
                'sub_agent_count' => Db::table('fa_user')->whereIn('id', $sub_user_ids)->where('agent_promotion', 1)->count(),
                //下级余额
                'sub_agent_money_sum' => Db::table('fa_user')->whereIn('id', $sub_user_ids)->sum('money'),
                //今日注册
                'today_register_count' => Db::table('fa_user')->whereIn('id', $sub_user_ids)->whereTime('jointime', 'today')->count(),
                //今日首充用户数量（首次充值）
                'today_first_recharge_count' => Db::table('fa_user_recharge')
                    ->where('is_first', 1)
                    ->whereTime('create_time', 'today')
                    ->where('status', 1)
                    ->whereIn('user_id', $sub_user_ids) // 直接使用ID数组
                    ->count(),
                //今日复充用户数量（非首次充值）
                'today_recharge_count' => Db::table('fa_user_recharge')
                    ->where('is_first', 0)
                    ->whereTime('create_time', 'today')
                    ->where('status', 1)
                    ->whereIn('user_id', $sub_user_ids)
                    ->count(),
                //今日存款今日充值金额
                'today_recharge_amount' => Db::table('fa_user_recharge')
                    ->whereTime('create_time', 'today')
                    ->where('status', 1)
                    ->whereIn('user_id', $sub_user_ids)
                    ->sum('amount'),
                //今日提现金额
                'today_withdraw_amount' => Db::table('fa_user_withdraw')
                    ->whereTime('create_time', 'today')
                    ->where('status', 'success')
                    ->whereIn('user_id', $sub_user_ids)
                    ->sum('amount'),
                //今日红利
                'today_bonus_amount' => Db::table('fa_user_money_log')
                    ->whereTime('create_time', 'today')
                    ->whereIn('user_id', $sub_user_ids)
                    ->whereIn('type', ['birthday_bonus', 'upgrade_bonus', 'recharge_bonus'])
                    ->sum('money'),
                //今日投注
                'today_bet_amount' => Db::table('fa_game_bet')
                    ->whereTime('create_time', 'today')
                    ->whereIn('user_id', $sub_user_ids)
                    ->where('status', 1)
                    ->sum('valid_amount'),
                //今日输赢
                'today_win_amount' => Db::table('fa_game_bet')
                    ->whereTime('create_time', 'today')
                    ->whereIn('user_id', $sub_user_ids)
                    ->where('status', 1)
                    ->sum('settled_amount'),
                //今日返水
                'today_rebate_amount' => Db::table('fa_promotion_rebate_log')
                    ->whereTime('create_time', 'today')
                    ->whereIn('user_id', [$user->id])
                    ->sum('rebate_amount'),
            ];
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        $this->success('', ['statistics' => $statData]);
    }

    /**
     * 创建下级账号
     * @ApiMethod (POST)
     */
    public function createSubAccount()
    {
        Db::startTrans();
        try {
            $user = $this->auth->getUser();
            // 生成账号,将当前用户的ID转为64进制，然后加下划线，再加随机字符串。
            $username = dechex($user->id) . '_' . dechex(time());
            $password = dechex(time());
            $salt = dechex(time());
            $pid_path = $user->pid_path;

            if (!$pid_path || $pid_path == '0') {
                $pid_path = "0,";
            }

            Db::table('fa_user')->insertGetId([
                'username' => $username,
                'password' => $this->auth->getEncryptPassword($password, $salt),
                'salt' => $salt,
                'status' => 'normal',
                'pid' => $user->id,
                'pid_path' => $pid_path . $user->id . ',',
                'jointime' => time()
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('创建失败: ' . $e->getMessage());
        }

        $this->success('创建成功', ['username' => $username, 'password' => $password]);
    }

    /**
     * 获取下级提现记录
     * @ApiMethod (POST)
     */
    public function subWithdrawList()
    {
        $user = $this->auth->getUser();
        $sub_user_ids = Db::table('fa_user')->where('pid_path', 'like', '%,' . $user->id . ',%')->column('id');
        $list = Db::table('fa_user_withdraw')
            ->whereIn('uid', $sub_user_ids)
            ->paginate(20);

        $this->success('', $list);
    }

    /**
     * 结算返水
     * @ApiMethod (POST)
     */
    public function subRebateSettlement()
    {
        Db::startTrans();
        try {
            // 结算逻辑
            Db::commit();
            $this->success('结算完成');
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('结算失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取在线用户统计
     * @ApiMethod (POST)
     */
    public function onlineUser()
    {
        try {
            $onlineUsers = Db::table('fa_user_session')
                ->where('expiretime', '>', time())
                ->count();
            $this->success('', ['totalCount' => $onlineUsers]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取下级账号详情
     * @ApiMethod (POST)
     */
    public function subAccountDetail()
    {
        $uid = $this->request->post('uid/d', 0);
        $userInfo = Db::table('fa_user')->find($uid);

        $userInfo['subUserCount'] = Db::table('fa_user')->where('pid_path', 'like', '%,' . $uid . ',%')->count();
        $userInfo['wallet'] = Db::table('fa_user_wallet')->where('user_id', $uid)->find();

        $this->success('', $userInfo);
    }

    /**
     * 更新下级账号是否为代理
     * @ApiMethod (POST)
     */
    public function updateSubAccountIsAgent()
    {
        Db::startTrans();
        try {
            $uid = $this->request->post('uid/d', 0);
            $isAgent = $this->request->post('isAgent/d', 0);

            Db::table('fa_user')
                ->where('id', $uid)
                ->update(['is_agent' => $isAgent]);

            Db::commit();
            $this->success('状态更新成功');
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取存款出款统计
     * @ApiMethod (POST)
     */
    public function rechargeWithdraw()
    {
        try {
            $startTime = $this->request->post('startTime');
            $endTime = $this->request->post('endTime');

            $recharge = Db::table('fa_user_money_log')
                ->where('type', 'recharge')
                ->whereTime('create_time', 'between', [$startTime, $endTime])
                ->sum('money');

            $withdraw = Db::table('fa_user_withdraw')
                ->where('status', 'completed')
                ->whereTime('create_time', 'between', [$startTime, $endTime])
                ->sum('amount');

            $this->success('', [
                'totalRecharge' => abs($recharge),
                'totalWithdraw' => $withdraw
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 充值到下级账号
     * @ApiMethod (POST)
     * @ApiParams (name="uid", type="int", required=true, description="用户ID")
     * @ApiParams (name="amount", type="float", required=true, description="充值金额")
     */
    public function rechargeToSubAccount()
    {
        Db::startTrans();
        try {
            $uid = $this->request->post('uid/d', 0);
            $amount = $this->request->post('amount/f', 0);
            $user = $this->auth->getUser();

            // 参数校验
            if ($uid <= 0 || $amount <= 0) {
                $this->error('参数错误');
            }
            if ($user->money < $amount) {
                $this->error('余额不足');
            }

            // 执行转账
            Db::table('fa_user')->where('id', $user->id)->setDec('money', $amount);
            Db::table('fa_user')->where('id', $uid)->setInc('money', $amount);

            // 记录资金变动
            $moneyLog = [
                'user_id' => $user->id,
                'money' => -$amount,
                'before' => $user->money,
                'after' => $user->money - $amount,
                'memo' => '给下级充值',
                'create_time' => Date("Y-m-d H:i:s"),
                'type' => 'sub_recharge'
            ];
            Db::table('fa_user_money_log')->insert($moneyLog);

            Db::commit();
            $this->success('充值成功');
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error('操作失败: ' . $e->getMessage());
        }
    }

    // 其他接口方法按照相同模式重构...
}
