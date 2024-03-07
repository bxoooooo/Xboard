<?php

namespace App\Payments;

use \Curl\Curl;

class Alpha {
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'alpha_api_url' => [
                'label' => 'API地址',
                'description' => '',
                'type' => 'input',
            ],
            'alpha_app_id' => [
                'label' => 'APPID',
                'description' => '',
                'type' => 'input',
            ],
            'alpha_app_secret' => [
                'label' => 'AppSecret',
                'description' => '',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $params = [
            'app_id' => $this->config['alpha_app_id'],
            'out_trade_no' => $order['trade_no'],
            'total_amount' => $order['total_amount'],
            'notify_url' => $order['notify_url'],
            'return_url' => $order['return_url']
        ];
        ksort($params);
        $str = http_build_query($params);
        $params['sign'] = strtolower(md5($str .  $this->config['alpha_app_secret']));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->config['alpha_api_url'] . '/api/v1/tron');
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['User-Agent: Alpha']);
        $res = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($res, true);
        if (!$result) {
            abort(500, '网络异常');
        }
        if ($result['code'] === 0) {
            abort(500, '接口请求失败');
        }
        return [
            'type' => 1, // 0:qrcode 1:url
            'data' => $result['url']
        ];
    }

    public function notify($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        ksort($params);
        $str = strtolower(http_build_query($params) . $this->config['alpha_app_secret']);
        if ($sign !== md5($str)) {
            return false;
        }
        return [
            'trade_no' => $params['out_trade_no'],
            'callback_no' => $params['trade_no']
        ];
    }
}
