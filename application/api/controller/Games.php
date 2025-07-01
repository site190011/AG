<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

/**
 * 游戏接口
 */
class Games extends Api
{
    protected $noNeedLogin = ['getGameList', 'getPlatTypeList'];
    protected $noNeedRight = ['*'];

    public function getGameList()
    {
        $platType = $this->request->request('platType');
        $gameType = $this->request->request('gameType');
        $gameType2 = $this->request->request('gameType2');
        $isRecommend = $this->request->request('isRecommend');
        $keyword = $this->request->request('keyword');
        $gameModel = new \app\admin\model\Games();

        $list = $gameModel->getGameList($platType, $gameType, $gameType2, $isRecommend, $keyword);

        $this->success('success', $list);
    }

    /**
     * 平台类型列表
     *
     */
    public function getPlatTypeList()
    {
        $list = Db::name('game_plat')->select();

        $this->success('success', $list);
    }

    public function getGameEntryUrl()
    {
        try {

            $gameId = $this->request->request('gameId');
            $return_url = $this->request->request('return_url');

            $gameModel = new \app\admin\model\Games();
            $game = $gameModel->where('id', $gameId)->where('is_enable', '1')->find();

            if (!$game) {
                throw \Exception('游戏不存在或已禁用');
            }

            $user = $this->auth->getUser();
            // $wallet = $user->getWallet();

            if ($user['money'] <= 0) {
                throw \Exception('余额不足，请充值');
            }

            $agapi = new \app\admin\model\AgApi();
            $platType = $game['plat_type'];
            $playerId = $agapi->getPlayerId($user, $platType);

            if (!$playerId) {
                $playerId = $agapi->createPlayer($user, $platType);
                $playerIds = json_decode($user['playerIds'] ?? '[]', true);
                $user['playerIds'] = json_encode(array_merge($playerIds, [$platType => $playerId]));
            }

            $inGames = json_decode($user['in_games'] ?? '[]', true);

            if (!empty($inGames)) {
                // 用户上次进入的游戏没有正常退出,尝试转出里面的余额
                foreach ($inGames as $k => $v) {
                    $res = $agapi->getBalance($playerId, $k);
                    $balance = $res['data']['balance'] ?? 0;
                    if ($balance > 0) {
                        $agapi->transfer($playerId, $k, -$balance);
                    }
                    unset($inGames[$k]);
                }
            }

            $inGames[$platType] = [
                'money' => $user['money'],
                'gameId' => $gameId,
                'time' => time()
            ];

            $user['in_games'] = json_encode($inGames);

            $user->save();

            $agapi->transfer($playerId, $platType, $user['money']);
            $res = $agapi->getGameEntryUrl($playerId, $game, $return_url);

        } catch (\Throwable $e) {
            $this->error('无法获入口地址', json_decode($e->getMessage(), true) ?: $e->getMessage());
        }

        if ($res && ($res['code'] == 10000)) {
            $this->success('success', $res);
        } else {
            $this->error('获入口地址失败', $res);
        }
    }

    public function getBalance(){
        $agapi = new \app\admin\model\AgApi();
        $user = $this->auth->getUser();
        $platType = $this->request->request('platType');
        $playerId = $agapi->getPlayerId($user, $platType);

        $res = $agapi->getBalance($playerId, $platType);

        $balance = $res['data']['balance'] ?? null;

        if ($balance !== null) {
            $this->success('success', $balance);
        } else {
            $this->error('获取余额失败', $res);
        }
    }
}
