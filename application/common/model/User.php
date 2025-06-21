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
        if ($isStartTrans){
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

        if ($isStartTrans){
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
}
