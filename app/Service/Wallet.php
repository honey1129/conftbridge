<?php

namespace App\Service;

use Log;

class Wallet
{
    private static $api_url = 'https://cfac.cnsunys.com';
    private static $token = '1bc7d973ee0fd72e743277c11b202a8d';
    /**
     * 获取充值地址
     * @param $sub_id string 用户ID
     * @param $chain_id string 主网ID
     */
    public static function getPayAddress($user_id, $chain_type)
    {
        $route = '/api/address/getUserAddress';
        $url = self::$api_url . $route;
        $data = [
            'user_id'    => $user_id,
            'chain_type' => $chain_type
        ];
        $data = json_encode($data);
        $headers = ['user-token:' . self::$token];
        $result = HttpService::send_post($url, $data, $headers);
        $result = json_decode($result, true);
        if (isset($result['code'])) {
            if ($result['code'] == 200) {
                return $result['data']['address'];
            }
        }
        return false;
    }

    // 获取地址助记词
    public static function getWords($user_id)
    {
        $route = '/api/address/getUserWords';
        $url = self::$api_url . $route;
        $data = [
            'user_id' => $user_id
        ];
        $data = json_encode($data);
        $headers = ['user-token:' . self::$token];
        $result = HttpService::send_post($url, $data, $headers);
        $result = json_decode($result, true);
        if (isset($result['code'])) {
            if ($result['code'] == 200) {
                return $result['data']['words'];
            }
        }
        return false;
    }

    /**
     * @param int $user_id
     * @param string $content
     * @param int $type
     */
    public static function importAddress($user_id, $data)
    {
        $route = '/api/address/getUserWords';
        $url = self::$api_url . $route;
        $data = [
            'user_id' => $user_id,
            'data'    => encrypt($data),
        ];
        $data = json_encode($data);
        $headers = ['user-token:' . self::$token];
        $result = HttpService::send_post($url, $data, $headers);
        $result = json_decode($result, true);
        if (isset($result['code'])) {
            if ($result['code'] == 200) {
                return $result['data']['address'];
            }
        }
        return false;
    }

}