<?php

namespace app\admin\model;

use think\Db;
use think\Log;

// =================================================
// 商户后台👇
// https://login.api-bet.com/
// qwe9911
// Aa123456.
// SN:f65
// KEY:ar8h1S1kd0r0U843T1h7125j9XS28i51
// 👍NG官方频道（维护通知）：https://t.me/NewGaming
// 👍NG官方网站：https://www.NewGaming.com
// 👍NG接口文档：https://doc.api-bet.com/docs/api
// 👍API请求地址：https://ap.api-bet.net
// 👍NG演示站：https://ng-demo.com
// 👍商户后台：https://login.api-bet.com
// 👍商户后台手机版：https://login-m.api-bet.com

class AgApi
{
    const SN = 'f65';
    const SECRET_KEY = 'ar8h1S1kd0r0U843T1h7125j9XS28i51';
    const BASE_URL_AP = 'https://ap.api-bet.net';
    const BASE_URL_SA = 'https://sa.api-bet.net';
    const CURRENCY = 'CNY';


    /**
     * 用户转入/转出金额
     */
    public function transfer($playerId, $platType, $amount)
    {
        $res = $this->sendRequest('/api/server/transfer', [
            'playerId' => $playerId,
            'platType' => $platType,
            'amount' => abs($amount),
            'currency' => self::CURRENCY,
            'type' => ($amount > 0) ? '1' : '2',
            'orderId' => 'order_' . time()
        ]);

        if ($res['code'] != 10000) {
            throw new \Exception(json_encode([
                'msg' => (($amount > 0) ? '转入' : '转出') . '金额失败',
                'res' => $res,
            ]));
        }

        return $res;
    }

    /**
     * 获取余额
     */
    public function getBalance($playerId, $platType)
    {
        $res = $this->sendRequest('/api/server/balance', [
            'playerId' => $playerId,
            'platType' => $platType,
            'currency' => self::CURRENCY
        ]);

        if ($res['code'] != 10000) {
            throw new \Exception(json_encode([
                'msg' => '转入金额失败',
                'res' => $res,
            ]));
        }

        return $res;
    }
    /**
     * 转出所有,对应一键回收接口
     */
    public function transferAll($playerId, $currency = self::CURRENCY)
    {
        $res = $this->sendRequest('/api/server/transferAll', [
            'playerId' => $playerId,
            'currency' => $currency
        ]);

        if ($res['code'] != 10000) {
            throw new \Exception(json_encode([
                'msg' => '金额转出失败',
                'res' => $res,
            ]));
        }

        return $res;
    }

    /**
     * 发送接口请求
     * @param string $endpoint 接口路径（如 /user/create）
     * @param array $data 请求体数据
     * @param bool $isSouthAmerica 是否使用南美区域
     * @return array API响应结果
     */
    public static function sendRequest($endpoint, $data = [], $isSouthAmerica = false)
    {
        $baseUrl = $isSouthAmerica ? self::BASE_URL_SA : self::BASE_URL_AP;
        $url = $baseUrl . $endpoint;

        // 生成随机字符串（16-32位小写字母+数字）
        $random = bin2hex(random_bytes(16)); // 生成32位随机字符串
        $random = substr($random, 0, rand(16, 32)); // 截取16-32位

        // 生成签名
        $sign = md5($random . self::SN . self::SECRET_KEY);

        // 设置请求头
        $headers = [
            'sign: ' . $sign,
            'random: ' . $random,
            'sn: ' . self::SN,
            'Content-Type: application/json'
        ];

        // 发起请求
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false // 根据实际情况调整证书验证
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        //dd($response);
        Log::api("请求URL: " . $url . "\n请求头: " . implode("\n", $headers) . "\n请求体: " . json_encode($data) . "\n响应: " . $response);

        if ($error) {
            throw new \Exception("API请求失败: " . $error);
        }

        return json_decode($response, true);
    }

    public function getGames($platType)
    {

        $list = cache('games_' . $platType);

        if ($list) {
            return $list['data'];
        }

        $list = $this->sendRequest('/api/server/gameCode', ['platType' => $platType]);

        if ($list) {
            cache('games_' . $platType, $list, 86400);
        } else {
            return [];
        }

        return $list['data'] ?: [];
    }

    /**
     * @link https://doc.api-bet.com/docs/api/api-1e9845d8k15v6
     */
    public function getPlatTypeList()
    {
        return [
            "ag" => "亚游",
            "bbin" => "宝盈",
            "bg" => "大游",
            "boya" => "博雅",
            "cq9" => "CQ9",
            "db2" => "多宝老虎机",
            "db6" => "多宝捕鱼",
            "db7" => "多宝棋牌",
            "fc" => "FC",
            "fg" => "FG",
            "jdb" => "夺宝",
            "joker" => "Joker",
            "km" => "KingMaker",
            "ky" => "开元",
            "leg" => "乐游",
            "lgd" => "LGD",
            "mg" => "MG",
            "mt" => "美天",
            "mw" => "大满贯",
            "nw" => "新世界",
            "pg" => "PG",
            "pgs" => "pgs",
            "pp" => "王者",
            "pt" => "PT",
            "t1" => "T1game",
            "vg" => "财神棋牌",
            "wl" => "瓦力",
            "ww" => "双赢",
            "xgd" => "高登",
            "yoo" => "云游"
        ];
    }


    /**
     * 同步所有游戏列表
     */
    public function syncGamesToDB()
    {
        $list = Db::name('game_plat')->where('api_scope', 'fetch_api_game_list')->select();
        foreach ($list as $item) {
            $platType = $item['key'];
            $platTypeName = $item['name'];
            $Games = $this->getGames($platType);
            $count = count($Games);

            echo "加载 {$platType}:{$platTypeName} / count: {$count} \n";

            if (!$Games) {
                continue;
            }

            $gameTypes = [];

            foreach ($Games as $game) {

                if (!isset($gameTypes[$game['gameType']])) {
                    $gameTypes[$game['gameType']] = 0;
                }

                $gameTypes[$game['gameType']]++;

                $gameModel = new \app\admin\model\Games();
                if ($gameModel->where('plat_type', $game['platType'])->where('game_code', $game['gameCode'])->find()) {
                    continue;
                }

                $gameName = $game['gameName']['zh-hans'] ?? $game['gameName']['en'] ?? current($game['gameName']) ?? '-';
                $gameModel->save([
                    'plat_type' => $platType,
                    'game_type' => $game['gameType'],
                    'game_code' => $game['gameCode'],
                    'ingress' => $game['ingress'],
                    'game_name' => $gameName,
                    'is_enable' => 0
                ]);

                echo "{$platType}:{$gameName} 添加成功\n";
            }

            echo 'gameType数量统计：' . json_encode($gameTypes) . "\n";
        }
    }

    public function getGameEntryUrl($playerId, $game, $return_url)
    {

        $lang = $this->getLang($game['plat_type']);
        $args = [
            'playerId' => $playerId,
            'platType' => $game['plat_type'],
            'gameType' => $game['game_type'],
            'currency' => self::CURRENCY,
            'returnUrl' => $return_url,
            'ingress' => $game['ingress'] == 3 ? 1 : $game['ingress'],
        ];

        $game_code = $game['game_code'];

        if ($game_code) {
            $args['gameCode'] = $game_code;
        }

        if ($lang) {
            $args['lang'] = $lang;
        }

        $res = $this->sendRequest('/api/server/gameUrl', $args);

        if ($res['code'] != 10000) {
            throw new \Exception(json_encode([
                'msg' => '获取游戏入口地址失败',
                'res' => $res,
                'args' => $args,
            ]));
        }

        $res['args'] = $args;

        return $res;
    }

    public function generatePlayerId($user)
    {
        $playerId = 'p' . str_pad($user['id'], 5, "0", STR_PAD_LEFT);

        return $playerId;
    }
    public function getPlayerId($user, $plat_type = null)
    {
        $playerIds = json_decode($user['playerIds'] ?? '[]', true);
        return $plat_type ? ($playerIds[$plat_type] ?? null) : $this->generatePlayerId($user);
    }

    public function createPlayer($user, $plat_type)
    {
        $playerId = $this->generatePlayerId($user);
        $sendData = [
            'playerId' => $playerId,
            'platType' => $plat_type,
            'currency' => self::CURRENCY,
        ];

        $res = $this->sendRequest('/api/server/create', $sendData);

        if ($res['code'] != 10000 && $res['code'] != 10002) {
            throw new \Exception(json_encode([
                'msg' => '注册玩家账号失败',
                'sendData' => $sendData,
                'res' => $res,
            ]));
        }

        return $playerId;
    }

    /**
     * 历史记录
     * @link https://doc.api-bet.com/docs/api/api-1em4bi0bu86rh
     */
    public function getRecordHistory($startTime, $endTime, $pageNo = 1, $pageSize = 2000)
    {

        $sendData = [
            'currency' => self::CURRENCY,
            'startTime' => $startTime ?: Date("Y:m:d H:i:s", time() - 86400),
            'endTime' => $endTime ?: Date("Y:m:d H:i:s", time()),
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = $this->sendRequest('/api/server/recordHistory', $sendData);

        if ($res['code'] != 10000) {
            throw new \Exception(json_encode([
                'msg' => '获取游戏记录失败',
                'sendData' => $sendData,
                'res' => $res,
            ]));
        }

        return $res;
    }

    /**
     * 实时记录
     * @link https://doc.api-bet.com/docs/api/api-1e413a2k1a23l
     */
    public function recordAll($pageNo = 1, $pageSize = 2000)
    {
        $sendData = [
            'currency' => self::CURRENCY,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];
        $res = $this->sendRequest('/api/server/recordAll', $sendData);
        if ($res['code'] != 10000) {
            throw new \Exception(json_encode([
                'msg' => '获取游戏记录失败',
                'sendData' => $sendData,
                'res' => $res,
            ]));
        }

        return $res;
    }

    public function getLang($plat_type = null)
    {
        $langs = [
            "ag" => "1",
            "allbet" => "zh_CN",
            "ap" => "",
            "bbin" => "zh-cn",
            "bg" => "zh_CN",
            "boya" => "",
            "cmd" => "zh-CN",
            "cq9" => "China",
            "cr" => "zh-cn",
            "crown" => "ch",
            "db1" => "1",
            "db2" => "CN",
            "db3" => "1",
            "db5" => "cn",
            "db6" => "CN",
            "db7" => "",
            "dg" => "cn",
            "esb" => "ZH-CN",
            "evo" => "zh",
            "fb" => "CMN",
            "fc" => "2",
            "fg" => "zh-cn",
            "ftg" => "zh",
            "gb" => "cn",
            "gw" => "cn",
            "hb" => "zh-CN",
            "ig" => "CN",
            "im" => "ZH-CN",
            "jdb" => "cn",
            "jili" => "zh-CN",
            "joker" => "zh",
            "ka" => "zh",
            "km" => "zh-CN",
            "ky" => "zh-CN",
            "leg" => "ly_lang=zh_cn",
            "lgd" => "zh_CN",
            "mg" => "ZH-CN",
            "mt" => "ZH-CN",
            "mw" => "cn",
            "newbb" => "zh-cn",
            "nw" => "",
            "og" => "zh",
            "panda" => "zh",
            "pg" => "zh",
            "png" => "zh_hans",
            "pp" => "zh",
            "ps" => "zh-CN",
            "pt" => "ZH-CN",
            "r88" => "zh-CN",
            "saba" => "ch",
            "sbo" => "zh-cn",
            "sexy" => "cn",
            "sg" => "",
            "sgwin" => "",
            "ss" => "2",
            "t1" => "zh-CN",
            "tcg" => "CN",
            "tf" => "zh",
            "v8" => "en_us",
            "vg" => "1",
            "vr" => "",
            "we" => "zh_cn",
            "wl" => "",
            "wm" => "0",
            "ww" => "zh-CN",
            "xgd" => "zh",
            "xj" => "",
            "yoo" => "zh-CN"
        ];

        if ($plat_type){
            return $langs[$plat_type] ?? null;
        }

        return $langs;
    }
}
