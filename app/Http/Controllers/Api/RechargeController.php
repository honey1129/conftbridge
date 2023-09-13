<?php

namespace App\Http\Controllers\Api;


use App\Models\Chain;
use App\Models\ChainNetwork;
use App\Models\Recharge;
use App\Models\UserAddress;
use App\Models\WalletCode;
use App\Service\Wallet;
use http\Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use QrCode;

class RechargeController extends Controller
{
    /**
     *充值记录
     */
    public function index(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'pid' => 'required',

            ],
            [
                'pid.required' => '币种必须输入',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $user = $request->user;
        $list = Recharge::where(['uid' => $user->id, 'pid' => $request->pid, 'ptype' => 1])
            ->orderBy('id', 'desc')
            ->paginate(10);
        foreach ($list as $key => $value) {
            $list[$key]['pname'] = WalletCode::getCode($value['pid']);
            $list[$key]['network'] = Chain::getChain($value['cz_type']);
            $list[$key]['mark'] = Recharge::markLang('cn', $value['mark']);
            $list[$key]['en_mark'] = Recharge::markLang('en', $value['en_mark']);
        }
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     *全部
     */
    public function rechargeList(Request $request)
    {
        $user = $request->user;
        $list = Recharge::where(['uid' => $user->id, 'pid' => $request->pid])
            ->orderBy('id', 'desc')
            ->paginate(10);
        foreach ($list as $key => $value) {
            $list[$key]['pname'] = WalletCode::getCode($value['pid']);
            $list[$key]['network'] = Chain::getChain($value['cz_type']);
            $list[$key]['mark'] = Recharge::markLang('cn', $value['mark']);
            $list[$key]['en_mark'] = Recharge::markLang('en', $value['en_mark']);

        }
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     *充值记录筛选主网了
     * @param int $id
     */
    public function rechargeLog(Request $request)
    {
        $type = $request->get('type');
        $pid = $request->input('pid', 8);
        $query = Recharge::query();
        if (!empty($type)) {
            $query = $query->where('type', $type);
        }
        $query->where('pid', $pid);
        $list = $query->where('uid', $request->user->id)
            ->orderBy('id', 'desc')
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     * 客户钱包充值
     * @param Request $request
     * @return array
     */
    public function walletRecharge(Request $request)
    {
        $user = $request->user;
        // $pid = $request->pid;
        $type = $request->type;
        $chain = DB::table('chain_network')
            ->where('id', $type)
            ->first();
        if (empty($chain)) {
            return __return($this->errStatus, '通道测试中');
        }
        //查询地址是否存在
        $userAddr = UserAddress::where('uid', $user->id)
            ->where('type', $type)
            ->first();
        //如果地址为空就创建
        if (empty($userAddr)) {
            $address = Wallet::getPayAddress($user->id, $chain->type);
            if ($address != false) {
                //组装创建数据
                $saveData = array();
                $saveData['uid'] = $user->id;
                $saveData['address'] = $address;
                $saveData['salt'] = 123;
                $saveData['zjc'] = 456;
                $saveData['pid'] = 8;
                $saveData['type'] = $type;
                UserAddress::create($saveData);
            } else {
                $address = '';
            }
        } else {
            $address = $userAddr->address;
        }
        // if($type==1){
        //     $address = '0x4e80208eEC11f53c88b1f4C3d09A891dd934DE32';
        // }
        // if($type==2){
        //     $address = 'TQzSi9b3c3jowFMsufR5Cpne8fMMZ9QGGJ';
        // }
        // if ($type == 3) {
        //     $address = '3K11nSwxErveewEzURJ66UoxzRJgUGt6rR';
        // }
        // if ($type == 5) {
        //     //$address = '0xf69F023d825382beCBa2F9DC2CaE8099B57F90AB';
        // }
        if (!$address) {
            return __return($this->errStatus, '创建账户失败', []);
        }
        $qrcode = QrCode::format('png')
            ->encoding('UTF-8')
            ->size(368)->margin(0)->generate($address);
        $data['address'] = $address;
        $data['qrcode'] = 'data:image/png;base64,' . base64_encode($qrcode);
        return __return($this->successStatus, '创建账户成功', $data);
    }

    /**
     * 在线支付 往下所有方法未使用
     *
     */
    public function onlineRecharge(Request $request)
    {
        return __return($this->errStatus, '该交易暂未开启');
        $validator = Validator::make(
            $request->all(),
            [
                'money' => 'required|numeric|min:0',
                'phone' => 'required|integer',
                'name'  => 'required|string',

            ],
            [
                'money.required' => '数量必须',
                'money.numeric'  => '数量为数字',
                'phone.required' => '手机号必须',
                'phone.integer'  => '填写真实手机号',
                'name.required'  => '姓名必须',
                'name.string'    => '请填写真实姓名',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $user = $request->user;
        try {
            DB::beginTransaction();
            //创建充值订单
            $data['uid'] = $user->id;
            //            $data['rmb'] = $request->rmb;
            $data['usdt'] = $request->money;
            $data['mark'] = '客户线上入金';
            $data['type'] = Recharge::ONLINE_RECHARGE;
            $recharge = Recharge::create($data);

            $ordnum = 'RE' . date('YmdHis') . $recharge->id . mt_rand(1000, 9999);
            $recharge->ordnum = $ordnum;
            $recharge->save();
            $currpay = config('system.currpay'); //获取配置信息
            $pickupUrl = $currpay['pickupUrl'];
            $receiveUrl = $currpay['receiveUrl'];
            //获取时间
            list($usec, $sec) = explode(" ", microtime());
            $time = (int)((float)$usec + (float)$sec) * 1000;

            $params = array(
                "appkey"            => $currpay['appKey'],
                'kyc'               => 2,
                //$currpay['appKey'],
                'username'          => $request->name,
                'area_code'         => $user->area_code,
                'phone'             => $request->phone,
                "company_order_num" => $ordnum,
                //商家订单号，保证唯一性
                "coin_sign"         => 'USDT',
                //货币，人民币: CNY, 美元: USD,DOGE-[1:CNY,2:USD,3:HKD,4:BD]
                "coin_amount"       => $request->money,
                //最多接收四位小数，最大为5万人民币，以下单时单价计算
                "goods_name"        => $user->id . '充值',
                //商品标题
                "order_time"        => $time,
                //订单时间戳(北京时间)
                "sync_url"          => $pickupUrl,
                //同步通知回调地址
                "async_url"         => $receiveUrl, //异步通知回调地址
            );

            $request_url = $this->pay($params);

        } catch (\Exception $e) {
            Log::info($e);
            return __return($this->errStatus);
        }
        if ($request_url['success'] == true) {
            DB::commit();
            return __return($this->successStatus, '获取成功', $request_url['data']);
        } else {
            if (isset($request_url['data'])) { //有未完成订单
                DB::rollBack();
                return __return($this->successStatus, $request_url['msg'], $request_url['data']);
            } else {
                DB::rollBack();
                return __return($this->errStatus, $request_url['msg'], $request_url);
            }
        }

    }

    /**
     * @desc 扫码支付-
     * @scanType $string 扫码方式-[1:微信免签,2:支付宝免签,4:转卡支付,5:云闪付,6:支付宝红包,7:微信h5,8:当面付,9;支付宝wap]
     * @desc string $scanSubType 扫码支付子类型(选填)
     * @param string $merchantId 商户号（*）
     * @param string $orderSn 商家订单号（*）
     * @param int $type 支付方式（*）-[1:网银,2:扫码,3:快捷,4:代付]
     * @param int $currency 货币类型(*)-[1:CNY,2:USD,3:HKD,4:BD]
     * @param int $amount 订单的总额(*)-分作为单位
     * @param string $notifyUrl 异步通知回调地址(*)
     * @param string $extra 额外参数(选填)
     * @param string $goodsName 商品标题(*)
     * @param string $goodsDetail 商品描述(选填)
     * @param string $sign 签名-md5($merchantId+$orderSn+$currency+$amount+$notifyUrl+$tyep+$key)
     * @return json [code:状态码,message:提示文案,data:数据]
     */
    public function pay($params)
    {
        //组装签名字段获取签名签名
        $arg = $this->build_args($params);

        $currpay = config('system.currpay');
        $gopay = $currpay['appBaseUrl'] . $currpay['key']; //网关、快捷下单接口地址

        $response = $this->http($gopay, 'POST', $arg); //post参数提交，获取数据处理
        $result = json_decode($response, true);

        return $result;
    }

    //生成已签名参数未使用
    public function build_args($args = [], $secret = null)
    {
        $currpay = config('system.currpay');
        if (empty($secret)) {
            $secret = $currpay['appSecret'];
        }
        if (!isset($args['appkey'])) {
            $args['appkey'] = $currpay['appkey'];
        }
        $argstr = $this->build_argstr($args, $secret);
        $args['sign'] = $this->build_sign($argstr);
        return $args;
    }

    //获取待签名字符串未使用
    public function build_argstr($args = [], $secret = null)
    {
        ksort($args);
        reset($args);
        $argstr = null;
        foreach ($args as $key => $val) {
            $argstr .= $key . '=' . $val . '&';
        }
        if (get_magic_quotes_gpc()) {
            $argstr = stripslashes($argstr);
        }
        $argstr = $argstr . $secret;
        return $argstr;
    }

    //字符串进行签名未使用
    public function build_sign($argstr = null, $sign_type = 'md5')
    {
        $sign = hash($sign_type, $argstr);
        return $sign;
    }

    /**
     * @desc 支付成功回调未使用
     * @param string $merchantId 商户ID
     * @param string $orderSn 订单号
     * @param string $goodsName 商品名称
     * @param string $queryId 查询id
     * @param int $amount 订单金额（单位分）
     * @param int $status 订单状态 [3:支付成功,4:支付失败]
     * @param int $realMoney 实际支付金额（单位分）
     * @param int $message 提示信息
     * @param string $sign 签名-md5
     */

    public function notify()
    {
        $request = $request = file_get_contents("php://input"); //获取post方式的参数
        parse_str($request, $post);
        $currpay = config('system.currpay');
        $verify = $this->verify_sign($post, $currpay['appSecret']);
        if ($verify == false) {
            echo '验签失败';
            die;
        }

        if ($post['trade_status'] == '1') {
            //交易成功

        } else {
            //交易失败
            echo 'fail';
        }
    }

    /* post参数提交,可根据自己情况修改换成别的函数未使用 */
    public function http($url, $method = 'POST', $postData = array())
    {
        $data = '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $header = array(
            "User-Agent: $user_agent"
        );
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
                if (strtoupper($method) == 'POST') {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = '';
            }
        }
        return $data;
    }

    //验证签名未使用
    public function verify_sign($post = [], $secret = null)
    {
        if (empty($secret)) {
            $currpay = config('system.currpay');
            $secret = $currpay['appSecret'];
            if (empty($secret)) {
                Log::info('验证签名：密钥为空');
                return false;
            }
        }
        if (isset($post['sign'])) {
            $sign = $post['sign'];
            unset($post['sign']);
            $argstr = $this->build_argstr($post, $secret);
            $mysign = $this->build_sign($argstr);
            if ($sign === $mysign) {
                return true;
            }
        }
        return false;
    }

}