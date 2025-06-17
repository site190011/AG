<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Http;
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

        //TODO

        return json(["code"=>0, "msg"=>"success"]);


    }
}
