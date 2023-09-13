<?php

namespace Extend\Wallet;

class BtcInterface
{

    private $user;
    private $pwd;
    private $ip;
    private $port;
    private $qianbao_key;
    public $bitcoin;

    function __construct()
    {
        $this->user        = env('OMNIUSER');
        $this->pwd         = env('OMNIPWD');
        $this->ip          = env('OMNIHOST');
        $this->port        = env('OMNIPORT');
//        $this->qianbao_key = env('OMNIKEY');

        $this->bitcoin = new Bitcoin($this->user, $this->pwd, $this->ip, $this->port);
//        var_dump($this->bitcoin);die;

    }

    /**
     * 获取新的充值钱包地址
     * @param $account
     * @return mixed
     */
    public function getnewaddress($account)
    {
        $address = $this->bitcoin->getnewaddress($account);
//        if (!$address) {//钱包解锁
//            $this->bitcoin->walletpassphrase($this->qianbao_key, 20);
//            $this->bitcoin->keypoolrefill(2000);
//            $address = $this->bitcoin->getnewaddress($account);
//        }
        return $address;
    }

    /**
     * BTC发起交易
     * @param $address
     * @param $money
     * @return mixed
     */
    public function qianbao_tibi($address, $money)
    {
        $this->bitcoin->walletlock();//强制上锁
        $this->bitcoin->walletpassphrase($this->qianbao_key, 20);//钱包解锁
        $id = $this->bitcoin->sendtoaddress($address, $money);
        $this->bitcoin->walletlock();
        return $id;
    }

    /**
     * 检查钱包地址有效性
     * @param $url
     * @return bool
     */
    public function validateaddress($url)
    {
        $address = $this->bitcoin->validateaddress($url);
        if ($address['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * USDT记录
     * @param $address
     * @param $count
     * @return mixed
     */
    public function usdt_trans($address, $count = 20)
    {
        $list = $this->bitcoin->omni_listtransactions($address, $count);
        return $list;
    }

    /**
     *  BTC记录
     * @param $account
     * @param int $count
     * @return mixed
     */
    public function listtransactions($account, $count = 20)
    {
        $list = $this->bitcoin->listtransactions($account, $count);
        return $list;
    }

}