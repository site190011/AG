<?php

namespace app\common\model;

use app\admin\model\AgApi;
use think\Db;
use think\Model;

/**
 * 会员模型
 * @method static mixed getByUsername($str) 通过用户名查询用户
 * @method static mixed getByNickname($str) 通过昵称查询用户
 * @method static mixed getByMobile($str) 通过手机查询用户
 * @method static mixed getByEmail($str) 通过邮箱查询用户
 */
class User extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'url',
    ];

    protected $wallet = null;

    /**
     * 获取个人URL
     * @param string $value
     * @param array  $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return "/u/" . $data['id'];
    }

    /**
     * 获取头像
     * @param string $value
     * @param array  $data
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        if (!$value) {
            //如果不需要启用首字母头像，请使用
            //$value = '/assets/img/avatar.png';
            $value = letter_avatar($data['nickname']);
        }
        return $value;
    }

    /**
     * 获取会员的组别
     */
    public function getGroupAttr($value, $data)
    {
        return UserGroup::get($data['group_id']);
    }

    /**
     * 获取验证字段数组值
     * @param string $value
     * @param array  $data
     * @return  object
     */
    public function getVerificationAttr($value, $data)
    {
        $value = array_filter((array)json_decode($value, true));
        $value = array_merge(['email' => 0, 'mobile' => 0], $value);
        return (object)$value;
    }

    /**
     * 设置验证字段
     * @param mixed $value
     * @return string
     */
    public function setVerificationAttr($value)
    {
        $value = is_object($value) || is_array($value) ? json_encode($value) : $value;
        return $value;
    }

    /**
     * 变更会员余额
     * @param int    $money   余额
     * @param int    $user_id 会员ID
     * @param string $memo    备注
     */
    public static function money($money, $user_id, $memo)
    {
        //弃用
        return;
        Db::startTrans();
        try {
            $user = self::lock(true)->find($user_id);
            if ($user && $money != 0) {
                $before = $user->money;
                //$after = $user->money + $money;
                $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
                //更新会员信息
                $user->save(['money' => $after]);
                //写入日志
                MoneyLog::create(['user_id' => $user_id, 'money' => $money, 'before' => $before, 'after' => $after, 'memo' => $memo]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 变更会员积分
     * @param int    $score   积分
     * @param int    $user_id 会员ID
     * @param string $memo    备注
     */
    public static function score($score, $user_id, $memo)
    {
        Db::startTrans();
        try {
            $user = self::lock(true)->find($user_id);
            if ($user && $score != 0) {
                $before = $user->score;
                $after = $user->score + $score;
                $level = self::nextlevel($after);
                //更新会员信息
                $user->save(['score' => $after, 'level' => $level]);
                //写入日志
                ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 根据积分获取等级
     * @param int $score 积分
     * @return int
     */
    public static function nextlevel($score = 0)
    {
        $lv = array(1 => 0, 2 => 30, 3 => 100, 4 => 500, 5 => 1000, 6 => 2000, 7 => 3000, 8 => 5000, 9 => 8000, 10 => 10000);
        $level = 1;
        foreach ($lv as $key => $value) {
            if ($score >= $value) {
                $level = $key;
            }
        }
        return $level;
    }

    public function getSpecialCacheKey($key = null)
    {
        $dataSet = [
            'betTotalVaildAmount' => 'betTotalVaildAmount_' . $this->id,
            'betTotalSettledAmount' => 'betTotalSettledAmount_' . $this->id,
            'betTotalAmount' => 'betTotalAmount_' . $this->id,
        ];

        return $key ? $dataSet[$key] : $dataSet;
    }

    public function clearSpecialCache($key)
    {
        $dataSet = $this->getSpecialCacheKey($key);
        if (is_array($dataSet)) {
            foreach ($dataSet as $key => $value) {
                cache($value, null);
            }
        } else {
            cache($dataSet, null);
        }
    }
    public function changeMoney($wallet_type, $amount, $logType, $logMsg = '', $related_table = null, $related_table_ids = null, $isStartTrans = true)
    {
        if ($isStartTrans) {
            Db::startTrans();
        }

        $user_id = $this->id;

        // $wallet = $this->getWallet();
        // $walletBuilder = Db::name('user_wallet')->where('user_id', $user_id);

        // switch ($wallet_type) {
        //     case 'balance':
        //         $walletBuilder->update([
        //             'balance' => Db::raw('balance+' . $amount)
        //         ]);
        //         break;
        //     case 'freeze':
        //         $walletBuilder->update([
        //             'freeze' => Db::raw('freeze+' . $amount)
        //         ]);
        //         break;
        //     case 'rebate':
        //         $walletBuilder->update([
        //             'rebate' => Db::raw('rebate+' . $amount)
        //         ]);
        //         break;
        // }

        // $money = bcadd(bcadd($wallet['balance'], $wallet['freeze'], 2), bcadd($wallet['rebate'], $amount, 2), 2);

        $this->where('id', $user_id)->update([
            'money' => Db::raw('money+' . $amount)
        ]);

        Db::name('user_money_log')->insert([
            'wallet' => $wallet_type,
            'user_id' => $user_id,
            'money' => $amount,
            'before' => $this->money,
            'after' => $this->money + $amount,
            'memo' => $logMsg,
            'create_time' => Date("Y-m-d H:i:s"),
            'type' => $logType,
            'related_table' => $related_table,
            'related_table_ids' => $related_table_ids
        ]);

        $this->money = $this->money + $amount;

        if ($isStartTrans) {
            Db::commit();
        }

        return true;
    }

    public function getWallet()
    {

        if ($this->wallet) {
            return $this->wallet;
        }

        $user_id = $this->id;
        $this->wallet = Db::table('fa_user_wallet')->where('user_id', $user_id)->find();

        if (!$this->wallet) {
            Db::table('fa_user_wallet')->insert([
                'user_id' => $user_id,
                'create_time' => Date("Y-m-d H:i:s"),
            ]);

            $this->wallet = Db::table('fa_user_wallet')->where('user_id', $user_id)->find();
        }

        return $this->wallet;
    }

    public function transferAll()
    {
        $user = $this;
        $agApi = new AgApi();
        $playerId = $agApi->getPlayerId($user);
        $res = $agApi->transferAll($playerId);

        $balanceAll = $res['data']['balanceAll'] ?: 0;

        if ($balanceAll != 0) {
            $this->changeMoney('balance', $balanceAll, 'AGTransferAll');
        }

        Db::table('fa_user_wallet')->where('user_id', $user->id)->update([
            'has_money_in_game' => 0,
        ]);
    }

    /**
     * 尝试会员升级
     */
    public function tryVipUpgrade($isStartTrans = true)
    {
        $currentRechargeSum = Db::table('fa_user_recharge')->where('user_id', $this->id)->where('status', 1)->sum('amount');
        $vipList = Db::table('fa_vip_config')->where('level', '>', $this->viplevel)->order('level', 'asc')->select();

        foreach ($vipList as $vip) {
            if ($currentRechargeSum >= $vip['upgradeConsumed']) {
                $this->viplevel = $vip['level'];
                $this->changeMoney('balance', $vip['upgradeBonus'], 'VipUpgrade', '会员升级', 'vip_config', $vip['id'], $isStartTrans);
                $this->save();
            }
        }
    }

    /**
     * 领取奖励
     * @param int $reward_id 奖励ID
     */
    public function grantReward($reward_id)
    {
        $reward = Db::name('user_reward')->where('id', $reward_id)->where('user_id', $this->id)->where('is_grant', 0)->find();
        if (!$reward) {
            return false;
        }

        $this->changeMoney('balance', $reward['amount'], 'RewardGrant', $reward['remark'], 'user_reward', $reward_id, false);
        Db::name('user_reward')->where('id', $reward_id)->update(['is_grant' => 1]);

        return true;
    }

    /**
     * 尝试发放活动奖励
     */
    public function tryActivityReward()
    {
        $activitys = Db::name('article')->where("status", 'published')->where("type", "events")->select();

        foreach ($activitys as $activity) {
            $reward_rules = json_decode($activity['reward_rules'], true);
            if (is_array($reward_rules) && !empty($reward_rules)) {
                foreach ($reward_rules as $rule) {
                    $amountNeeds = $rule['amountNeeds'];
                    $amountBonus = $rule['amountBonus'];
                    $slug = "{$rule['type']}_{$activity['id']}_{$this->id}";

                    if ($amountBonus <= 0 || $amountNeeds <= 0) {
                        //奖励金额或需求金额不合法
                        continue;
                    }

                    $isGranted = Db::name('user_reward')->where('slug', $slug)->count() > 0;
                    if ($isGranted) {
                        //奖励已发放
                        continue;
                    }

                    $build = Db::name('user_recharge')->where('user_id', $this->id)->where('status', 1);
                    switch ($rule['type']) {
                        case 'recharge_sum':
                            //累计充值
                            $totalRecharge = $build->sum('amount');
                            if ($totalRecharge >= $amountNeeds) {
                                Db::name('user_reward')->insert([
                                    'user_id' => $this->id,
                                    'type' => 'recharge_sum',
                                    'slug' => $slug,
                                    'amount' => $amountBonus,
                                    'is_grant' => 0,
                                    'remark' => '累计充值满' . $amountNeeds . '送' . $amountBonus . '元',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ]);
                            }
                            break;
                        case 'recharge_day':
                            //每日充值
                            $todayRecharge = $build->whereTime('create_time', 'today')->sum('amount');
                            if ($todayRecharge >= $amountNeeds) {
                                Db::name('user_reward')->insert([
                                    'user_id' => $this->id,
                                    'type' => 'recharge_day',
                                    'slug' => $slug,
                                    'amount' => $amountBonus,
                                    'is_grant' => 0,
                                    'remark' => '每日充值满' . $amountNeeds . '送' . $amountBonus . '元',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ]);
                            }
                            break;
                        case 'recharge_first':
                            //首次充值
                            $isFirstRecharge = $build->count() == 1;
                            if (!$isFirstRecharge) {
                                break; //如果不是首次充值，则跳过
                            }
                            $totalRecharge = Db::name('user_recharge')->where('user_id', $this->id)->where('status', 1)->sum('amount');
                            if ($totalRecharge >= $amountNeeds) {
                                //如果首次充值满足条件，则发放奖励
                                Db::name('user_reward')->insert([
                                    'user_id' => $this->id,
                                    'type' => 'recharge_first',
                                    'slug' => $slug,
                                    'amount' => $amountBonus,
                                    'is_grant' => 0,
                                    'remark' => '首次充值送' . $amountBonus . '元',
                                    'create_time' => time(),
                                    'update_time' => time(),
                                ]);
                            }
                    }
                }
            }
        }
    }

    public function onRechargeSuccess($isStartTrans = true)
    {
        $this->tryVipUpgrade($isStartTrans);
        $this->tryActivityReward();
    }
}
