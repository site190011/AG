<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Http;
use think\Db;
use think\Env;

/**
 * 支付接口
 */
class Pay extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function test(){
        dd($_SERVER);
    }
    /**
     * 获取TRC20地址
     */
    public function getTrc20Address(){
        $trc20_api = Env::get('pay.trc20_api');
        $trc20_token = Env::get('pay.trc20_token');
        $user_id = $this->auth->id;

        if (!$user_id){
            $this->error('请先登录');
        }

        $res = Http::get($trc20_api . "/", [
            'currency' => 'usdt',
            'network' => 'trc20',
            'user_id' => $user_id
        ], [
            CURLOPT_HTTPHEADER => [
                'x-api-token: ' . $trc20_token
            ]
        ]);

        if ($res['code'] == 200) {
            $this->success('成功', $res['data']);
        } else {
            $this->error($res['message']);
        }
    }

    /**
     * TRC20充值回调
     */
    public function trc20Callback(){
        $order_id = $this->request->post("order_id");
        $user_id = $this->request->post("user_id");
        $amount = $this->request->post("value");
        $signature = $this->request->post("signature");

        $trc20_token = Env::get('pay.trc20_token', false);
        
        $signature2 = md5($order_id.$user_id.$amount.$trc20_token);

        if($signature2 != $signature){
            return json(["code"=>1, "msg"=>"签名错误"]);
        }

        $exchange_rate = Db::name('bank')->where("bank_code", 'ustd_trc20')->value("exchange_rate");

        if (!$exchange_rate) {
            return json(["code"=>1, "msg"=>"汇率错误"]);
        }

        $cachekey = 'trc20Callback_' . $order_id;

        if (cache($cachekey)) {
            return json(["code"=>1, "msg"=>"订单已处理"]);
        }

        if ($user_id <= 0) {
            return json(["code"=>1, "msg"=>"用户错误"]);
        }

        $rmbAmount = $amount * $exchange_rate;

        $user = \app\common\model\User::get($user_id);

        $user->changeMoney('balance', $rmbAmount, 'recharge', '充值', 'user_recharge', $order_id);

        $runtimeDir = ROOT_PATH . 'runtime' . DS . 'log' . DS . 'pay';

        if (!is_dir($runtimeDir)) {
            mkdir($runtimeDir, 0755, true);
        }

        $logFile = $runtimeDir . DS . 'pay_' . date('Ymd') . '.log';
        $logData = [
            'user_id' => $user_id,
            'amount' => $amount,
            'exchange_rate' => $exchange_rate,
            'order_id' => $order_id
        ];

        file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . json_encode($logData) . "\r\n", FILE_APPEND);

        cache($cachekey, json_encode($logData), 86400 * 30);

        return json(["code"=>0, "msg"=>"success"]);


    }
}
