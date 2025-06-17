<?php

namespace app\admin\model\vip;

use think\Model;
use think\Db;
use app\common\model\User;
use fast\Date;

class Info extends Model
{





    // 表名
    protected $name = 'vip_config';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    //派发红利
    public function handleBonus($type, $slug, $typeMsg = '')
    {

        $vip_config = array_column((array)Db::name('vip_config')->select(), null, 'level');

        Db::name('user')->field('id,viplevel')->where('status', 'normal')->where('viplevel', '>=', 1)->chunk(1000, function ($userList) use ($type, $slug, $vip_config, $typeMsg) {
            $userSet = array_column($userList, null, 'id');
            $uids = array_keys($userSet);
            $user_behaviors = Db::name('user_behavior')->where('uid', 'in', $uids)->where('type', $type)->select();
            foreach ($user_behaviors as $user_behavior) {
                $data = $user_behavior['data'];
                if (strpos($data, $slug)) {
                    //经派发过了
                    unset($userSet[$user_behavior['uid']]);
                }
            }

            foreach ($userSet as $user) {
                $bonusAmount = $vip_config[$user['viplevel']][$type] ?? false;

                if (!is_numeric($bonusAmount)) {
                    //无正确的礼金配置
                    continue;
                }

                $exist = Db::name('user_behavior')->where('uid', $user['id'])->where('type', $type)->find();

                Db::startTrans();

                if ($exist) {
                    $data = json_decode($exist['data'] ?: '[]', true);

                    if (in_array($slug, $data)) {
                        //已派送过
                        continue;
                    }

                    $data[] = $slug;
                    Db::name('user_behavior')->where('uid', $user['id'])->where('type', $type)->update([
                        'data' => json_encode($data)
                    ]);
                } else {
                    Db::name('user_behavior')->insert([
                        'uid' => $user['id'],
                        'type' => $type,
                        'data' => json_encode([$slug])
                    ]);
                }

                User::find($user['id'])->changeMoney('rebate', $bonusAmount, $type, $typeMsg, null, null, false);

                Db::commit();
            }
        });
    }

    public function handleBirthdayBonus()
    {
        $year = date('Y');
        $today = date('m-d');
        $type = 'birthdayBonus';

        $vip_config = array_column((array)Db::name('vip_config')->select(), null, 'level');

        //查今天的生日用户
        $userList = (array)Db::name('user')->where('viplevel', '>=', 1)->where('birthday', 'like', '%' . $today)->select();
        $userSet = array_column($userList, null, 'id');
        $uids = array_keys($userSet);

        $user_behaviors = Db::name('user_behavior')->where('uid', 'in', $uids)->where('type', $type)->select();

        foreach ($user_behaviors as $user_behavior) {
            $data = $user_behavior['data'];
            if (strpos($data, $year)) {
                //已派送过今年的生日礼包
                unset($userSet[$user_behavior['uid']]);
            }
        }


        foreach ($userSet as $user) {
            $bonusAmount = $vip_config[$user['viplevel']]['birthdayBonus'] ?? false;

            if (!is_numeric($bonusAmount)) {
                //无正确的礼金配置
                continue;
            }

            $exist = Db::name('user_behavior')->where('uid', $user['id'])->where('type', $type)->find();

            Db::startTrans();

            if ($exist) {
                $data = json_decode($exist['data'] ?: '[]', true);

                if (in_array($year, $data)) {
                    //今年的已派送过
                    continue;
                }

                $data[] = $year;
                Db::name('user_behavior')->where('uid', $user['id'])->where('type', $type)->update([
                    'data' => json_encode($data)
                ]);
            } else {
                Db::name('user_behavior')->insert([
                    'uid' => $user['id'],
                    'type' => $type,
                    'data' => json_encode([$year])
                ]);
            }

            User::find($user['id'])->changeMoney('rebate', $bonusAmount, 'birthday', '生日礼金', null, null, false);

            Db::commit();
        }
    }
}
