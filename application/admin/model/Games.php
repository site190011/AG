<?php

namespace app\admin\model;

use think\Db;
use think\Model;
use app\common\model\User;
use app\common\model\MoneyLog;


class Games extends Model
{





    // 表名
    protected $name = 'games';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];




    public function getGameList($platType, $gameType, $gameType2, $isRecommend = null, $keyword = null, $favoriteUserId = null)
    {
        $build = $this->where('is_enable', 1);

        if (($gameType !== 'all') && $gameType ) {
            $build->where('game_type', $gameType);
        }

        if ($gameType2) {
            $build->where('game_type2', $gameType2);
        }

        if ($platType) {
            $build->where('plat_type', $platType);
        }

        if ($isRecommend) {
            $build->where('is_recommend', $isRecommend);
        }

        if ($keyword) {
           $build->where('game_name', 'like', '%' . $keyword . '%');
        }

        // 添加收藏筛选逻辑，使用子查询
        if ($favoriteUserId) {
            // 构建子查询获取用户收藏的游戏ID列表
            $subQuery = Db::name('user_favorite_games')->where('user_id', $favoriteUserId)->field('game_id')->buildSql();
            $build->whereRaw("id IN {$subQuery}");
        }

        $list = $build->order('sort', 'desc')->paginate(50);

        return $list;
    }

    /**
     * 获取子类型列表
     */
    public function getType2List()
    {
        return [
            '' => '无',
            'f1' => '分类1',
            'f2' => '分类2',
            'f3' => '分类3',
            'f4' => '分类4',
        ];
    }


    /**
     * 同步投注实时记录
     */
    public function syncRealTimeRecord($pageNo, $pageSize)
    {
        $this->syncRecord('realTime', $pageNo, $pageSize, null, null);
    }

    /**
     * 同步投注历史记录
     */
    public function syncHistoryRecord($startTime, $endTime, $pageNo, $pageSize)
    {
        $this->syncRecord('history', $pageNo, $pageSize, $startTime, $endTime);
    }

    public function syncRecord($recordType, $pageNo, $pageSize, $startTime, $endTime)
    {
        $agapi = new \app\admin\model\AgApi();
        $res = null;

        switch ($recordType) {
            case 'realTime':
                $res = $agapi->recordAll($pageNo, $pageSize);
                break;
            case 'history':
                $res = $agapi->getRecordHistory($startTime, $endTime, $pageNo, $pageSize);
                break;
        }

        if ($res['code'] == 10000) {
            $nowTime = time();
            $list = $res['data']['list'] ?? [];

            foreach ($list as $item) {
                $record = [
                    'player_id' => $item['playerId'], // 玩家账号
                    'plat_type' => $item['platType'], // 游戏平台
                    'currency' => $item['currency'], // 游戏货币
                    'game_type' => $item['gameType'], // 游戏类型，1:视讯、2:老虎机、3:彩票、4:体育、5:电竞、6:捕猎、7:棋牌
                    'game_name' => $item['gameName'], // 游戏名称
                    'round' => $item['round'], // 局号
                    'table_no' => $item['table'], // 桌号
                    'seat_no' => $item['seat'], // 座号
                    'bet_amount' => $item['betAmount'], // 投注金额
                    'valid_amount' => $item['validAmount'], // 有效投注金额
                    'settled_amount' => $item['settledAmount'], // 输赢金额
                    'bet_content' => $item['betContent'], // 投注内容
                    'status' => $item['status'], // 状态，0:未完成、1:已完成、2:已取消、3:已撤单
                    'game_order_id' => $item['gameOrderId'], // 订单号
                    'bet_time' => $item['betTime'], // 订单创建时间(UTC +8)
                    'last_update_time' => $item['lastUpdateTime'], // 订单更新时间(UTC +8)
                    'is_rebate' => 0, // 已返水，0:否、1:是
                ];

                $betBuid = Db::name('game_bet');

                if ($betBuid->where('game_order_id', $item['gameOrderId'])->count() == 0) {
                    $record['user_id'] = intval(str_replace('p', '', $item['playerId']));
                    $record['create_time'] = date("Y-m-d H:i:s");
                    $betBuid->insert($record);
                } else {
                    $betBuid->where('game_order_id', $item['gameOrderId'])->update([
                        'last_update_time' => $item['lastUpdateTime'],
                        'status' => $item['status']
                    ]);
                }
            }

            $total = $res['data']['total'];
            $pageNoTmp = $res['data']['pageNo'];
            $pageSizeTmp = $res['data']['pageSize'];

            if ($total > $pageNoTmp * $pageSizeTmp) {
                if (($nowTime + 10) > time()) {
                    //按接口要求,每次翻页请求必须间隔 10 秒钟
                    sleep(time() - $nowTime);
                }

                $this->syncRecord($recordType, $pageNo + 1, $pageSize, $startTime, $endTime);
            }
        }

        $this->updateUserMoneyByBetRecords();
        $this->handleRebate();
    }

    public function updateUserMoneyByBetRecords ()
    {
        // 找出未返利的订单,按userId分组,先查出数据,再批量更新
        $list = Db::name('game_bet')
            ->where('is_update_user_money', 0)
            ->where('status', 1)
            ->field('user_id, game_type, group_concat(id) as ids, sum(settled_amount) as settled_amount')
            ->group('user_id, game_type')
            ->select();

        Db::startTrans();
        foreach ($list as $item) {
            $user_id = $item['user_id'];
            $user = User::lock(true)->find($user_id);

            if (!$user) {
                Db::name('game_bet')->where('id', 'in', $item['ids'])->update(['is_update_user_money' => -1]);
                continue;
            }

            $user->changeMoney('game_pay', $item['settled_amount'], 'game_pay', '玩游戏', 'game_pay', $item['ids'], false);

            //更新下注记录为已更新用户信息
            Db::name('game_bet')->where('id', 'in', $item['ids'])->update(['is_update_user_money' => 1]);
        }
        Db::commit();
    }

    public function handleRebate()
    {

        $systemConfig = (array)Db::name('promotion_user_config')->where('user_id', 0)->find();

        // 找出未返利的订单,按userId分组,先查出数据,再批量更新
        $list = Db::name('game_bet')
            ->where('is_rebate', 0)
            ->where('status', 1)
            ->field('user_id, game_type, group_concat(id) as ids, sum(valid_amount) as valid_amount')
            ->group('user_id, game_type')
            ->select();

        Db::startTrans();

        foreach ($list as $item) {
            $user_id = $item['user_id'];

            $user = User::lock(true)->find($user_id);

            if (!$user) {
                Db::name('game_bet')->where('id', 'in', $item['ids'])->update(['is_rebate' => -1]);
                continue;
            }

            $pid_path = $user->pid_path;
            $pid_list = $pid_path ? array_filter(explode(',', $pid_path)) : [];
            $parentConfigList = (array)Db::name('promotion_user_config')->whereIn('user_id', $pid_list)->select();
            $parentConfigSet = array_column($parentConfigList, null, 'user_id');
            $rebateConfigKey = 'rebate' . $item['game_type'];
            $rebateRatioTotal = $systemConfig[$rebateConfigKey] ?? 0;
            $rebateAmountTotal = $item['valid_amount'] * $rebateRatioTotal / 100;

            if ($rebateRatioTotal <= 0) {
                // 没有返水比例
                Db::name('game_bet')->where('id', 'in', $item['ids'])->update(['is_rebate' => 2]);
                continue;
            }

            if ($rebateAmountTotal <= 0) {
                // 没有返水金额
                Db::name('game_bet')->where('id', 'in', $item['ids'])->update(['is_rebate' => 3]);
                continue;
            }

            //剩余返水金额 = 返水小计金额
            $subRebateAmountSurplus = $rebateAmountTotal;
            
            //按顺序给上级返水
            foreach ($pid_list as $pid) {
                $ratio = $parentConfigSet[$pid][$rebateConfigKey] ?? 0;
                //留给下一层上级返水的金额
                $nextLayerRebateAmount = abs($item['valid_amount'] * $ratio / 100);
                //这层上级应得返水金额 = 剩余返水金额 - 留给下一层上级返水金额
                $rebateAmount = $subRebateAmountSurplus - $nextLayerRebateAmount;
                //剩余返水金额 = 留给下一层上级的返水金额
                $subRebateAmountSurplus = $nextLayerRebateAmount;

                if ($rebateAmount < 0.01) {
                    //这层上级没有返水金额
                    continue;
                }
                
                //这层上级用户信息
                $parent = User::where('status', 'normal')->find($pid);

                if ($parent) {
                    //给这层上级返水钱包添加金额
                    $parent->changeMoney('rebate', $rebateAmount, 'game_rebate', '合营返水', 'game_bet', $item['ids'], false);
                    Db::name('promotion_rebate_log')->insert([
                        'user_id' => $pid,
                        'player_uid' => $user_id,
                        'related_bet_ids' => $item['ids'],
                        'bet_amount' => $item['valid_amount'],
                        'rebate_amount' => $rebateAmount,
                        'create_time' => Date('Y-m-d H:i:s')
                    ]);
                }

                if ($subRebateAmountSurplus < 0.01) {
                    //没有剩余的返水金额了
                    break;
                }
                
            }

            if ($subRebateAmountSurplus >= 0.01) {
                //剩余的金额返给自己
                $user->changeMoney('rebate', $subRebateAmountSurplus, 'game_rebate', '游戏返水', 'game_bet', $item['ids'], false);
                Db::name('promotion_rebate_log')->insert([
                    'user_id' => $user_id,
                    'player_uid' => $user_id,
                    'related_bet_ids' => $item['ids'],
                    'bet_amount' => $item['valid_amount'],
                    'rebate_amount' => $subRebateAmountSurplus,
                    'create_time' => Date('Y-m-d H:i:s')
                ]);
            }

            //更新下注记录为已返水
            Db::name('game_bet')->where('id', 'in', $item['ids'])->update(['is_rebate' => 1]);
        }
        Db::commit();
    }
}
