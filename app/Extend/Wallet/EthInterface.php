<?php
namespace Extend\Wallet;

use Think\Log;

class EthInterface extends Base
{
    private $coin = 'ETH';

    const ACCOUNT_CREATE    = 'api/create_account';//创建账户
    const IMPORT_WORDS      = 'api/get_privkey_addr';//导入助记词
    const IMPORT_PRIVATEKEY = 'api/get_eth_addr';//导入以太坊私钥
    const USER_MONEY        = 'api/account_getBalance';//余额查询
    const CHECK_ALL         = 'api/account_getAllBalance';//查询地址所有币种余额
    const ETHERSCA          = 'api/etherscan';//查询交易单据状态接口
    const TRANSACTION       = 'api/account_transaction';//提币及转账交易
    const CHECK_ADDRESS     = 'api/check_eth_address'; //检查地址有效性
    const TXHASH            = '/api/get_txhash_Info'; //根据Tx_Hash获交易详情

    const CONTRACT = '';//检测合约地址



    //创建账户
    public function createAccount()
    {
        return $this->curl_get(self::ACCOUNT_CREATE, ['coin' => $this->coin]);
    }

    //助记词导入
    public function wordsImportAccount($words, $path)
    {

        return $this->curl_post(self::IMPORT_WORDS, ['seed' => $words, 'path' => $path]);
    }

    //私钥导入
    public function keyImportAccount($private_key)
    {
        return $this->curl_get(self::IMPORT_PRIVATEKEY, ['priv_key' => $private_key]);
    }

    //获得用户余额
    public function userMoney($address, $contract_address)
    {
        return $this->curl_post(self::USER_MONEY, ['address' => $address, 'coin' => $this->coin, 'contract_address' => $contract_address]);
    }

    //获得用户余额
    public function userMoney_coin($address, $coin, $contract_address)
    {
        return $this->curl_post(self::USER_MONEY, ['address' => $address, 'coin' => $coin, 'contract_address' => $contract_address]);
    }

    //获得用户所有余额
//    public function userAllMoney($address)
//    {
//        return $this->curl_get(self::CHECK_ALL, ['address' => $address]);
//    }

    //交易查询
    public function etherscan($tx_hash)
    {
        return $this->curl_get(self::ETHERSCA, ['tx_hash' => $tx_hash]);
    }

    // 检查地址有效性
    public function check_eth_address($address)
    {
        return $this->curl_get(self::CHECK_ADDRESS, ['address' => $address]);
    }

    //查询交易信息
    public function query_trans($tx_hash)
    {
        return $this->curl_get(self::TXHASH, ['tx_hash' => $tx_hash]);
    }

    //提币及转账交易
    public function transaction($from_address, $to_address, $price, $coin, $contract_address, $salt, $gas_limit = 30000, $gas_price = 12)
    {
        $data['from_address']     = $from_address; //发起交易者地址
        $data['to_address']       = $to_address; //接收交易者地址
        $data['value']            = $price; //提币或转账金额
        $data['coin']             = $coin; //币种符合（如：ETH、EAE）
        $data['contract_address'] = $contract_address; //合约地址
        $data['salt']             = $salt; //私钥
        $data['gas_limit']        = $gas_limit;  //gasLimit 消耗GAS的上限值
        $data['gas_price']        = $gas_price; //gasPrice发起者自定义价格

        $ethjc = $this->curl_post(self::TRANSACTION, $data);
        //\Log::info(json_encode($ethjc));
        if ($ethjc['status'] == 200) {
            return $ethjc['data']['tx_hash'];
        } else {
            $log = [
                'from_address' => $from_address,
                'to_address' => $to_address,
                'amount'=> $price,
                'res' => $ethjc,
                'time' => date('Y-m-d H:i:s')
            ];
           // \Log::info(json_encode($log));
            return false;
        }
    }

    //检测合约地址
    public function contract_address($address)
    {
        return $this->curl_get(self::CHECK_ADDRESS, ['address' => $address]);
    }
}