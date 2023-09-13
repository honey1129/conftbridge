<?php

namespace App\Http\Controllers\Api;

use App\Models\ApplyCoin;
use App\Models\AssetRelease;
use App\Models\AssetTransfer;
use App\Models\Product;
use App\Models\SmOrder;
use App\Models\SystemValue as SystemValueModel;
use App\Models\SystemValue;
use App\Models\UserAssets as UserAssetsModel;
use App\Models\UserPoolOrder;
use App\Models\UserPosition;
use App\Models\AgentUser;
use App\Models\Products;
use App\Models\EmailLog;
use App\Models\Slides;
use App\Models\UsersChildren;
use App\Models\WalletCode;
use App\Service\Wallet;
use App\Service\ImageService;
use App\User;
use Carbon\Carbon;
use App\Http\Traits\WriteUserMoneyLog;
use App\Models\UserAssets;
use App\Models\UserBank;
use App\Models\UserConfig;
use App\Models\Authentication;
use App\Models\UserAddress;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\SendSms;
use App\Http\Traits\SendEmail;
use App\Http\Traits\GoogleAuthenticator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Google;
use Mockery\Exception;
use Validator;
use QrCode;
use Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyCode;
use App\Models\UserMoneyLog;

class UserController extends Controller
{
    use SendSms, SendEmail, GoogleAuthenticator, WriteUserMoneyLog;

    protected $title = '用户信息管理控制器';


    protected $subscribe_redis;

    public function __construct()
    {
        $this->subscribe_redis = Redis::connection('subscribe');
    }

    public function test()
    {

        $model = \App\Models\UserWithdraw::where('uid', 180)->select('address')->get()->toArray();
        dd($model);
    }


    // 密码生成地址
    public function generateAddress(Request $request)
    {
        $user = $request->user;
        $type = $request->input('type', 5); // bsc链
        $password = $request->input('password', ''); //钱包密码 
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
                $saveData['pid'] = 1;
                $saveData['type'] = $type;
                $saveData['password'] = md5($password);
                UserAddress::create($saveData);
            } else {
                $address = '';
            }
        } else {
            $address = $userAddr->address;
        }

        return __return($this->successStatus, '创建账户成功', ['address' => $address]);
    }



    // 获取助记词
    public function getWords(Request $request)
    {
        $user = $request->user;
        $words = Wallet::getWords($user->id);
        if (!$words) {
            return __return($this->errStatus, '操作失败');
        }
        $data = decrypt($words);
        return __return($this->successStatus, '获取成功', ['words' => $data]);
    }


    public function importAddress(Request $request)
    {
        $user = $request->user;
        // 1 助记词 2 私钥
        $opType = $request->input('op_type', 1);
        $chainType = $request->input('chain_type', 5); // 链类型，默认币安
        $content = $request->input('content', '');
        if (empty($content)) {
            return __return($this->errStatus, '参数错误');
        }

        if (!in_array($chainType, [1, 2, 5])) {
            return __return($this->errStatus, '参数错误');
        }

        $userAddr = UserAddress::where('uid', $user->id)
            ->where('type', 5)
            ->first();

        if ($userAddr) {
            return __return($this->errStatus, '操作失败');
        }

        $data = [
            'content'    => $content,
            'op_type'    => $opType,
            'chain_type' => $chainType
        ];

        $result = Wallet::importAddress($user->id, $data);

        if ($result) {
            $saveData = array();
            $saveData['uid'] = $user->id;
            $saveData['address'] = $result['address'];
            $saveData['salt'] = 123;
            $saveData['zjc'] = 456;
            $saveData['pid'] = 1;
            $saveData['type'] = 5;
            $saveData['password'] = '';
            UserAddress::create($saveData);
            return __return($this->successStatus, '操作成功');
        } else {
            return __return($this->errStatus, '操作失败');
        }
    }

    /**
     * login api
     *
     */
    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'username' => 'required|max:60',
                'password' => 'required|min:6',
            ],
            [
                'username.required' => '账户必须',
                'password.required' => '密码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = User::where('email', $request->username)->first();
        if (empty($user)) {
            return __return($this->errStatus, '账号不存在');
        }
        if (!Hash::check($request->password, $user->password)) {
            return __return($this->errStatus, '密码输入错误');
        }
        $key = 'denglu' . $user->id;
        $token = Redis::get($key);
        if ($token) {
            $xinxi = DB::table('oauth_access_tokens')->where('user_id', $user->id)->first();
            if (empty($xinxi)) {
                $token = $user->createToken('api')->accessToken;
            }
            $success['token'] = $token;
            $success['secret'] = 0;
            // Redis::set($key, $token);
        } else {
            $success['token'] = $user->createToken('api')->accessToken;
            $success['secret'] = 0;
            // Redis::set($key, $success['token']);
        }
        //记录登陆日志
        write_login_log($user->id, $request->ip());
        return __return($this->successStatus, '登录成功', $success);
    }

    public function yanzheng(Request $request)
    {
        $input = $request->all();
        $result = $this->checkEmailCode($input['name'], $input['code']);
        if (empty($result)) {
            return __return($this->errStatus, '缺少手机号或邮箱');
        }
        if ($result['code'] != 200) {
            return __return($this->errStatus, $result['msg']);
        }
        return __return($this->successStatus, '验证成功');
    }

    /**
     * 用户退出登录
     * @return [type]           [description]
     */
    public function logout()
    {
        if (Auth::guard('api')->check()) {
            Auth::guard('api')->user()->token()->delete();
        }
        return __return($this->successStatus, '退出成功');
    }

    /**
     * 登录日志
     * @param Request $request
     * @return array
     */
    public function loginHistory(Request $request)
    {
        $logs = DB::table('user_login_history')
            ->where('uid', $request->user->id)
            ->orderBy('id', 'desc')
            ->paginate(10);

        return __return($this->successStatus, '获取成功', $logs);
    }

    /**
     * @param Request $request
     * @return array
     */
    function check(Request $request)
    {

        $account = $request->input('account'); //如果为null

        if (!$account)
            return __return($this->errStatus, '参数残缺');

        $phone = User::where('phone', $account)->first();
        if ($phone)
            return __return($this->successStatus, '手机已注册');

        $email = User::where('email', $account)->first();
        if ($email)
            return __return($this->successStatus, '邮箱已注册');

        return __return($this->errStatus, '手机号或者邮箱未注册');
    }

    public function register(Request $request)
    {
        $local = $request->local;
        $validator = Validator::make(
            $request->all(),
            [
                'email'                 => 'required',
                'code'                  => 'required',
                'password'              => 'required|min:6',
                'password_confirmation' => 'required|min:6',
                'recommend'             => 'required|min:6',
            ],
            [
                'email.required'                 => '邮箱必须',
                'code.required'                  => '验证码错误',
                'password.required'              => '密码必须',
                'password_confirmation.required' => '确认密码必须',
                'recommend.required'             => '邀请码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $is_exist = User::where('email', $request->email)->first();
        if ($is_exist) {
            return __return($this->errStatus, '该账号已经存在,请您重新输入');
        }
        $input = $request->all();

        $result = $this->checkEmailCode($input['email'], $input['code']);
        if (empty($result)) {
            return __return($this->errStatus, '邮箱必须');
        }
        if ($result['code'] != 200) {
            return __return($this->errStatus, $result['msg']);
        }
        if ($request->password != $request->password_confirmation) {
            return __return($this->errStatus, '两次密码输入不一致');
        }
        if (empty($request->recommend)) {
            return __return($this->errStatus, '邀请码必须');
        }
        //$request->recommend = $request->recommend ?? '4980941586';
        $users_recommend = User::where('account', $request->recommend)->first();
        if (empty($users_recommend)) {
            return __return($this->errStatus, '推荐人不存在');
        }
        try {
            DB::beginTransaction();
            $deep = 0;
            if ($users_recommend) {
                $recommend = $users_recommend;
                $deep = $recommend->deep + 1;
                $recommend_id = $recommend->id;
            }

            $input['recommend_id'] = $users_recommend ? $recommend_id : 0;
            $input['relationship'] = $users_recommend->relationship ? $users_recommend->relationship . ',' . $users_recommend->id : $users_recommend->id;
            $input['account'] = acID();
            $input['deep'] = isset($deep) ? $deep : 0;
            $input['nickname'] = $request->email;
            $input['name'] = $request->email;
            $input['avatar'] = 'images/2023_06_30/d9827e1b925c117bb97a7597765ad7204000.png';
            $input['password'] = Hash::make($request->password);
            $user = User::create($input);
            //推荐关系
            if ($users_recommend) {
                $this->relationUser($recommend_id, $user->id);
            }
            //写入个人配置信息
            $user->config()->create([
                'uid' => $user->id
            ]);
            $created_at = now();
            $updated_at = now();
            $inster_arr = array();

            $products_info = Products::where('state', 1)->select('pid', 'pname')->get()->toArray();
            foreach ($products_info as $key => $val) {
                $code = $this->coin_cut($val['pname']);
                $inster_arr[] = [
                    'uid'        => $user->id,
                    'pid'        => $val['pid'],
                    'pname'      => $code[0],
                    'ptype'      => 1,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                ];
            }
            if ($inster_arr) {
                UserAssets::insert($inster_arr);
            }

            $config = UserConfig::where('uid', $user->id)->first();
            $config->email_verify_at = now();
            $config->email_bind = 1;
            $config->save();
            $token = $user->createToken('api')->accessToken;
            DB::commit();
            return __return($this->successStatus, '注册成功', ['token' => $token]);
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }

    }


    /**
     * details api
     */
    public function details(Request $request)
    {
        $user = $request->user;
        $user->shijian = time();
        $user->save();
        $user = $request->user;
        $authentication = Authentication::where('uid', $user->id)
            ->where('status', '>=', Authentication::PRIMARY_CHECK)
            ->orderBy('id', 'desc')
            ->first();

        if (empty($authentication)) {
            $user->refuse_reason = null;
            $user->card_id = null;
        } else {
            $user->refuse_reason = $authentication->refuse_reason;
            $user->card_id = $authentication->card_id;
        }
        $user->config = $request->user_config;
        $user->ai_fee = config('site.ai_fee');
        $user->withdraw_fee = config('trans.withdraw_fee');
        return __return($this->successStatus, '请求成功', $user);
    }
    //    public function details(Request $request)
    //    {
    //        $user = $request->user;
    //        $authentication = Authentication::where('uid', $user->id)
    //            ->where('status', '>=', Authentication::PRIMARY_CHECK)
    //            ->orderBy('id', 'desc')
    //            ->first();
    //
    //        if (empty($authentication)) {
    //            $user->refuse_reason = null;
    //            $user->card_id = null;
    //        } else {
    //            $user->refuse_reason = $authentication->refuse_reason;
    //            $user->card_id = $authentication->card_id;
    //        }
    //
    //        $bank = UserBank::where('uid', $user->id)->first();
    //        if (!empty($bank)) {
    //            $user->bank = $bank;
    //        }
    //
    //        $user->config = $request->user_config;
    //
    //        $withdrawFee = config('tibi.fee');
    //        //用户等级
    //        // $user->level = $this->getUserLevel($user);
    //        $userinfo = User::where('id', $user->id)->first();
    //        $user['gesturePwd'] = $userinfo['password'];
    //        $user['isCapitalPwordSet'] = $userinfo['payment_password'];
    //        $user['realName'] = $user->name;
    //        $user['isOpenMobileCheck'] = $user->config->phone_bind?? 0;
    //        $user['googleStatus'] = 0;
    //        $user['accountStatus'] = 0;
    //        $user['inviteCode'] = $user->account;
    //        $user['lastLoginIp'] = '127.0.0.1';
    //        $user['authLevel'] = $userinfo['level'];
    //        $user['notPassReason'] = '';
    //        $user['withdraw_fee'] = $withdrawFee+0;
    //        $user['zhuan_usdtfee'] = config('site.transfer_fee');
    //        //团队激活
    //        $tuandui = DB::table('user_position')->where(['pid'=>$user->id,'lay'=>1])->orderBy('lay','asc')->get()->toArray();
    //        $num1 = 0;//待激活人数
    //        $zhitui = 0;
    //        foreach ($tuandui as $key => $value){
    //            $xinxi = DB::table('users')->where(['id'=>$value->uid])->first();
    //            if($xinxi){
    //                $zhitui++;
    //                if($xinxi->stoped==0){
    //                    $num1++;
    //                }
    //            }
    //        }
    //        $user['zhitui'] = $zhitui;
    //        $user['daijihuo'] = $num1;
    //
    //        return __return($this->successStatus, '请求成功', $user);
    //    }

    //获取用户等级
    public function getUserLevel($user)
    {
        $self_asset = UserAssets::getBalance($user->id, 8, 1);
        $conf = $this->getConf();
        $self_level = 0;

        /*if ($self_asset->balance > $conf['commission.v1_dispose']['0']) {
            $self_level = 1;
        }

        if ($self_asset->balance > $conf['commission.v2_dispose']['0']) {
            $self_level = 2;
        }

        if ($self_asset->balance > $conf['commission.v3_dispose']['0']) {
            $self_level = 3;
        }

        if ($self_asset->balance > $conf['commission.v4_dispose']['0']) {
            $self_level = 4;
        }

        if ($self_asset->balance > $conf['commission.v5_dispose']['0']) {
            $self_level = 5;
        }*/

        return $self_level;

    }

    //获取配置信息
    public function getConf()
    {
        $res = DB::table('admin_config')->where('name', 'like', 'commission.v%')->pluck('value', 'name');
        $data = [];
        foreach ($res as $k => $v) {
            $data[$k] = explode(',', $v);
        }
        return $data;
    }

    /**
     * 发送短信验证码
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function sendSms(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'area_code' => 'required',
                'phone'     => 'required',
            ],
            [
                'area_code.required' => '区号必须',
                'phone.required'     => '手机号必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $result = $this->doSendSms($request->phone, $request->ip(), $request->area_code);
        if ($result['code'] != 200) {
            return __return($this->errStatus, $result['msg']);
        }
        return __return($this->successStatus, '发送成功');
    }

    /**
     * 发送邮件
     * @param Request $request
     * @return array
     */
    public function sendEmail(Request $request)
    {
        $email = $request->email;
        $return = $this->send_email($email, $request->ip());
        return __return($return['code'], $return['msg']);
    }

    public function send_email($email, $ip)
    {
        if (!isset($email)) {
            return ['code' => 500, 'msg' => '邮箱必须'];
        }
        ;
        $email_log = EmailLog::where('email', $email)
            ->where('used', 0)
            ->orderBy('id', 'desc')
            ->first();
        if ($email_log) {
            $cha = time() - strtotime($email_log->created_at);
            if ($cha <= 60) {
                return __return($this->errStatus, '一分钟内只能发送一条');

            }
        }
        $sign = config('system.VerifyCodeSign');
        $code = mt_rand(100000, 999999);
        $email_log = new EmailLog;
        $email_log->email = $email;
        $email_log->code = $code;
        $email_log->ip = $ip;
        $email_log->save();
        $aa = Mail::to($email)->send(new VerifyCode($sign, $code));
        return __return($this->successStatus, '发送成功');

    }

    /**
     * 推荐链接
     * @param Request $request
     * @return mixed
     */
    public function registerLink(Request $request)
    {
        $user = $request->user;


        $type = $request->input('type', 1);

        if ($type == 1) {
            // h5
            $url = config('app.url') . 'h5/#/pages/logon/register';
            $querys = '';
            $querys .= '?recommend=' . $user->account;

            $fileName = 'images/register/h5/' . $user->account . '.png';
            //判断存放邀请图目录是否存在
            if (!Storage::exists('images/register/h5')) {
                Storage::disk($this->disk)->makeDirectory('images/register/h5');
            }
            //获取后台设置的海报
            //$data['haibao'] = ImageService::fullUrl(config('site.hiabao'));
            $data['account'] = $user->account;
            $data['url'] = $url . $querys;
            //生成邀请链接的二维码
            $qrcode = QrCode::format('png')
                ->size(190)->encoding('UTF-8')
                ->color(49, 16, 97)
                ->margin(0)->generate($data['url']);
            //文件不存在就生成
            if (!file_exists($fileName)) {
                Storage::disk($this->disk)->put($fileName, $qrcode);
                //$fileName = ImageService::inviteImg($fileName, $qrcode, $data['haibao']);
            }
            $data['inviteImg'] = ImageService::appUrl($fileName);
            $data['qrcode'] = 'data:image/png;base64,' . base64_encode($qrcode);
            return __return($this->successStatus, '获取成功', $data);
        } else if ($type == 2) {
            // app
            $url = config('app.url') . 'h5/#/pages/logon/registers';
            $querys = '';
            $querys .= '?recommend=' . $user->account;

            $fileName = 'images/register/app/' . $user->account . '.png';
            //判断存放邀请图目录是否存在
            if (!Storage::exists('images/register/app')) {
                Storage::disk($this->disk)->makeDirectory('images/register/app');
            }
            //获取后台设置的海报
            //$data['haibao'] = ImageService::fullUrl(config('site.hiabao'));
            $data['account'] = $user->account;
            $data['url'] = $url . $querys;
            //生成邀请链接的二维码
            $qrcode = QrCode::format('png')
                ->size(190)->encoding('UTF-8')
                ->color(49, 16, 97)
                ->margin(0)->generate($data['url']);
            //文件不存在就生成
            if (!file_exists($fileName)) {
                Storage::disk($this->disk)->put($fileName, $qrcode);
                //$fileName = ImageService::inviteImg($fileName, $qrcode, $data['haibao']);
            }
            $data['inviteImg'] = ImageService::appUrl($fileName);
            $data['qrcode'] = 'data:image/png;base64,' . base64_encode($qrcode);
            return __return($this->successStatus, '获取成功', $data);
        }

    }

    /**
     * 更新用户头像
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function updateAvatar(Request $request)
    {
        $user = $request->user;
        $nickName = $request->input('nickname', '');
        $avatar = $request->input('avatar', '');
        // if ($request->hasFile('avatar')) {
        //     $avatar_upload = $request->file('avatar');
        //     $avatar_upload_result = $this->upload($avatar_upload, 'oss');
        //     if ($avatar_upload_result['code'] != 200) {
        //         return __return($this->errStatus, '頭像' . $avatar_upload_result['msg']);
        //     }
        //     $avatar = $avatar_upload_result['data'];
        // }
        // if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $request->avatar, $result)) {
        //     $avatar_upload_result = $this->base64Upload($request->avatar, 'oss');
        //     if ($avatar_upload_result['code'] != 200) {
        //         return __return($this->errStatus, '头像' . $avatar_upload_result['msg']);
        //     }
        //     $avatar = $avatar_upload_result['data'];
        // }
        if ($nickName) {
            $user->nickname = $nickName;
        }
        if ($avatar) {
            $user->avatar = $avatar;
        }

        $user->save();
        return __return($this->successStatus, '操作成功');
    }

    /**
     * 验证登录密码和资金密码是否正确
     * @param Request $request
     * @return array
     */
    public function verifyPassword(Request $request)
    {
        $user = $request->user;
        if (!in_array($request->type, ['password', 'payment'])) {
            return __return($this->errStatus, '修改类型不正确');
        }
        if ($request->type == 'password') {
            if (!Hash::check($request->old_password, $user->password)) {
                return __return($this->errStatus, '原登录密码输入错误');
            }
        }
        if ($request->type == 'payment') {
            if (!Hash::check($request->old_password, $user->payment_password)) {
                return __return($this->errStatus, '原资金密码输入错误');
            }
        }
        return __return($this->successStatus, '验证成功');

    }

    /**
     * 修改用户登录密码
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function resetPassword(Request $request)
    {
        $user = $request->user;
        $validator = Validator::make(
            $request->all(),
            [
                'code'                  => 'required',
                'password'              => 'required',
                'password_confirmation' => 'required'
            ],
            [

                'code.required'                  => '验证码必须',
                'password.required'              => '密码必须',
                'password_confirmation.required' => '确认密码必须',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        if ($request->password != $request->password_confirmation) {
            return __return($this->errStatus, '两次密码输入不一致');
        }
        $password = Hash::make($request->password);
        //检测验证码
        $result = $this->checkEmailCode($user->email, $request->code);
        if ($result['code'] != 200) {
            return __return($this->errStatus, $result['msg']);
        }

        $user->password = $password;
        $user->save();

        return __return($this->successStatus, '修改成功');
    }

    /**
     * 修改用户资金密码
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function resetPaymentPassword(Request $request)
    {
        $user = $request->user;
        $validator = Validator::make(
            $request->all(),
            [
                //                'phone' => 'sometimes|required|regex:/^1[3456789][0-9]{9}$/',
                //                'email' => 'sometimes|required|email',
                'code'                  => 'required',
                'password'              => 'required',
                'password_confirmation' => 'required'
            ],
            [
                //                'phone.required' => '手机号不能为空',
                //                'phone.regex' => '手机号格式不正确',
                //                'email.required' => '邮箱不能为空',
                'code.required'                  => '验证码必须',
                'password.required'              => '密码必须',
                'password_confirmation.required' => '确认验证码不能为空',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        if ($request->password != $request->password_confirmation) {
            return __return($this->errStatus, '两次密码输入不一致');
        }
        $password = Hash::make($request->password);

        //检测验证码
        $result = $this->checkEmailCode($user->email, $request->code);
        if ($result['code'] != 200) {
            return __return($this->errStatus, $result['msg']);
        }

        $user->payment_password = $password;
        $user->save();
        return __return($this->successStatus, '修改成功');
    }


    /**
     * 忘记登录密码
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function forgetPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'account'               => 'required',
                'code'                  => 'required',
                'password'              => 'required|min:6',
                'password_confirmation' => 'required|min:6',
            ],
            [
                'account.required'               => '邮箱必须',
                'code.required'                  => '验证码必须',
                'password.required'              => '密码必须',
                'password_confirmation.required' => '确认密码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $is_exist = User::where('email', $request->account)->first();
        if (empty($is_exist)) {
            return __return($this->errStatus, '账号不存在');
        }
        $input = $request->all();
        $result = $this->checkEmailCode($input['account'], $input['code']);
        if (empty($result)) {
            return __return($this->errStatus, '邮箱必须');
        }
        if ($result['code'] != 200) {
            return __return($this->errStatus, $result['msg']);
        }
        if ($request->password != $request->password_confirmation) {
            return __return($this->errStatus, '两次密码输入不一致');
        }
        $password = Hash::make($request->password);

        $is_exist->password = $password;
        $is_exist->save();
        return __return($this->successStatus, '设置成功');
    }

    /**
     * 创建资金密码
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function createPaymentPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'code'                  => 'required',
                'password'              => 'required',
                'password_confirmation' => 'required'
            ],
            [
                'code.required'                  => '验证码必须',
                'password.required'              => '密码必须',
                'password_confirmation.required' => '确认密码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $user = $request->user;
        $config = $request->user_config;

        if ($request->password != $request->password_confirmation) {
            return __return($this->errStatus, '两次密码输入不一致');
        }
        //检测验证码
        //$result1 = $this->checkSmsCode($user->phone, $request->code);
        $result2 = $this->checkEmailCode($user->email, $request->code);
        if ($result2['code'] != 200) {
            return __return($this->errStatus, '验证码错误');
        }
        $user->payment_password = Hash::make($request->password);
        $user->save();
        $config->payment_password_set = 1;
        $config->save();
        return __return($this->successStatus, '设置成功');

    }

    /**
     * 绑定手机号
     * @param Request $request
     * @return array
     */
    public function phoneBind(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'code'  => 'required',
                'phone' => 'required',
            ],
            [
                'code.required'  => '验证码必须',
                'phone.required' => '手机号必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $config = $request->user_config;
        $user = $request->user;

        /*if ($config->phone_bind) {
            return __return($this->errStatus, '已经绑定过了');
        }*/

        $post = $request->post();
        if (!isset($post['code'])) {
            return __return($this->errStatus, '验证码必须');
        }

        if (!isset($post['phone'])) {
            return __return($this->errStatus, '手机号错误');
        }

        $phone = $post['phone'];
        $bind = User::where('phone', $phone)->count() + 0;
        if ($bind >= 5) {
            return __return($this->errStatus, '手机号注册数已经超出要求');
        }

        // 验证验证码
        $result = $this->checkSmsCode($phone, $post['code']);
        if ($result['code'] == 200) {
            $config->phone_verify_at = now();
            $config->phone_bind = 1;
            $config->save();

            $user->phone = $phone;
            $user->save();

            if ($config->email_bind && $config->phone_bind) {
                $config->security_level += 1;
                $config->save();
            }

            return __return($this->successStatus, '绑定成功');
        } else {
            return __return($this->errStatus, $result['msg']);
        }
    }

    /**
     * 绑定邮箱
     * @param Request $request
     * @return array
     */
    public function emailBind(Request $request)
    {
        $user = $request->user;
        $config = $request->user_config;
        /*if ($config->email_bind) {
            return __return($this->errStatus, '已经绑定过了');
        }*/
        $post = $request->post();
        if (!isset($post['code'])) {
            return __return($this->errStatus, '验证码必须');
        }

        if (!isset($post['email'])) {
            return __return($this->errStatus, '邮箱错误');
        }
        $email = $post['email'];
        $exists = User::where('email', $email)->count() + 0;
        if ($exists >= 5) {
            return __return($this->errStatus, '邮箱注册数已经超出要求');
        }

        // 验证验证码
        $result = $this->checkEmailCode($email, $post['code']);
        if ($result['code'] == 200) {
            $config->email_verify_at = now();
            $config->email_bind = 1;
            $config->save();

            $user->email = $email;
            $user->save();

            if ($config->email_bind && $config->phone_bind) {
                $config->security_level += 1;
                $config->save();
            }
            return __return($this->successStatus, '绑定成功');
        } else {
            return __return($this->errStatus, $result['msg']);
        }
    }

    /**
     * 创建谷歌验证码
     * @return [type] [description]
     */
    public function createGoogleSecret()
    {
        $createSecret = $this->doCreateSecret();
        // 自定义参数，随表单返回
        $parameter = [];
        return __return(
            $this->successStatus,
            '请求成功',
            ['createSecret' => $createSecret, "parameter" => $parameter]
        );
    }

    /**
     * 绑定谷歌验证码
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function authenticatorBind(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'google_code'   => 'required',
                'google_secret' => 'required',
            ],
            [
                'google_code.required'   => '谷歌验证码必须',
                'google_secret.required' => '谷歌密钥必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $user = $request->user;
        $config = $request->user_config;
        if ($config->google_bind) {
            return __return($this->errStatus, '已经绑定过了');
        }

        $post = $request->post();
        if (empty($post['google_code']) && strlen($post['google_code']) != 6) {
            return __return($this->errStatus, '谷歌验证码错误');
        }
        /*//检测验证码
        $result1 = $this->checkSmsCode($user->phone, $request->code);
        $result2 = $this->checkEmailCode($user->email, $request->code);
        if ($result1['code'] != 200 && $result2['code'] != 200) {
            return __return($this->errStatus, '验证码错误');
        }*/
        $google = $post['google_secret'];
        // 验证验证码和密钥是否相同
        if ($this->CheckCode($google, $post['google_code'])) {
            $config->google_secret = $post['google_secret'];
            $config->google_bind = 1;
            $config->google_verify = 1;
            $config->save();
            return __return($this->successStatus, '绑定成功');
        } else {
            return __return($this->errStatus, '谷歌验证码错误');
        }
    }

    /**
     * 开启或关闭谷歌验证
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function googleVerifyStart(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'key'         => 'required',
                'google_code' => 'required',
                //                'payment_password' => 'required'
            ],
            [
                'key.required'         => '开关必须',
                'google_code.required' => '谷歌验证码必须',
                //                'payment_password.required' => '资金密码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $config = $request->user_config;
        $user = $request->user;
        //验证是否绑定
        if ($config->google_bind == 0) {
            return __return($this->errStatus, '没有绑定谷歌验证码');
        }
        // 验证验证码和密钥是否相同
        if (!$this->CheckCode($config->google_secret, $request->google_code)) {
            return __return($this->errStatus, '谷歌验证码错误');
        }
        //开启谷歌验证
        if ($request->key == 'start') {
            //            if (!Hash::check($request->payment_password, $user->payment_password)) {
//                return __return($this->errStatus, '资金密码错误');
//            }
            $config->google_verify = 1;
            $config->save();
            return __return($this->successStatus, '开启成功');
        }
        //关闭谷歌验证
        if ($request->key == 'stop') {
            $input = $request->all();
            $result = $this->checkEmailCode($user->email, $input['code']);
            if (empty($result)) {
                return __return($this->errStatus, '邮箱必须');
            }
            if ($result['code'] != 200) {
                return __return($this->errStatus, $result['msg']);
            }
            $config->google_verify = 0;
            $config->save();
            return __return($this->successStatus, '关闭成功');
        }
    }

    public function yanzhenggg(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'google_code' => 'required',
            ],
            [
                'google_code.required' => '谷歌验证码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $config = $request->user_config;
        $user = $request->user;
        //验证是否绑定
        if ($config->google_bind == 0) {
            return __return($this->errStatus, '没有绑定谷歌验证码');
        }
        // 验证验证码和密钥是否相同
        if (!$this->CheckCode($config->google_secret, $request->google_code)) {
            return __return($this->errStatus, '谷歌验证码错误');
        }
        return __return($this->successStatus, '验证成功');
    }

    public function yzmobile(Request $request)
    {
        $config = $request->user_config;
        $user = $request->user;
        //验证是否绑定
        if ($config->phone_bind == 0) {
            return __return($this->errStatus, '没有绑定手机');
        }

        if ($request->key == 'stop') {
            if ($config->google_verify == 0) {
                return __return($this->errStatus, '短信验证和谷歌验证至少开启一个');
            }
        }
        //开启谷歌验证
        if ($request->key == 'start') {
            $config->sms_verify = 1;
            $config->save();
            return __return($this->successStatus, '开启成功');
        }

        //关闭谷歌验证
        if ($request->key == 'stop') {
            $config->sms_verify = 0;
            $config->save();
            return __return($this->successStatus, '关闭成功');
        }
    }


    /**
     * 私募列表
     * @param Request $request
     * @return array
     */
    public function launchList(Request $request)
    {
        $list = ApplyCoin::whereIn('status', array(1, 3))->orderBy('id', 'desc')->paginate(10);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['image'] = ImageService::fullUrl($list[$key]['image']);
            }
        }
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     * 私募列表
     * @param Request $request
     * @return array
     */
    public function slideShow(Request $request)
    {
        $list = ApplyCoin::select('id', 'code', 'image', 'fxunit', 'status')->where('status', 1)->orderBy('id', 'desc')
            ->get()->toArray();
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['image'] = ImageService::fullUrl($list[$key]['image']);
            }
        }
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     * 我的私募
     * @param Request $request
     * @return array
     */
    public function myLaunch(Request $request)
    {
        $user = $request->user;
        $list = SmOrder::leftJoin('apply_coin', 'apply_coin.id', 'sm_order.pid')
            ->select('sm_order.*', 'apply_coin.image', 'apply_coin.fxweb', 'apply_coin.fxbook', 'apply_coin.memo', 'apply_coin.memo')
            ->where('sm_order.uid', $user->id)
            ->orderBy('sm_order.id', 'desc')->paginate(10);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['image'] = ImageService::fullUrl($list[$key]['image']);
            }
        }
        return __return($this->successStatus, '获取成功', $list);
    }


    /**
     * 获取推荐人列表
     * @param Request $request
     * @return array
     */
    public function recommends(Request $request)
    {

        $user = $request->user;


        $childs = User::where(['recommend_id' => $user->id])->select(['id', 'email', 'nickname', 'per_yj', 'team_yj', 'today_add_yj', 'created_at'])->get();

        if ($childs->count() > 1) {
            $yjs = [];
            foreach ($childs as $child) {
                $yjs[$child->id] = $child->per_yj + $child->team_yj;
            }

            $maxYj = max($yjs);
            $maxUid = array_search($maxYj, $yjs);
            unset($yjs[$maxUid]);
            $minYj = array_sum($yjs);

            $maxChilds = User::whereRaw("find_in_set({$maxUid}, relationship)")->get();

            $maxAddYj = $maxChilds->pluck('today_add_yj')->sum();
            $maxUserAddYj = User::where(['id' => $maxUid])->value('today_add_yj');
            $maxAddYj += $maxUserAddYj;

            $minAddYj = 0;

            foreach ($yjs as $uid => $yj) {
                $minChilds = User::whereRaw("find_in_set({$uid}, relationship)")->get();
                $smallYj = User::where(['id' => $uid])->value('today_add_yj');
                $chilAddYj = $minChilds->pluck('today_add_yj')->sum();
                $minAddYj = $minAddYj + $chilAddYj + $smallYj;
            }

        } else {
            $maxYj = 0;
            $minYj = 0;

            $maxAddYj = 0;
            $minAddYj = 0;
        }


        // $query = User::whereRaw("find_in_set({$user->id},relationship)");

        // $recommends = $query->select(['id', 'email', 'nickname', 'created_at'])->paginate(10);
        $childs->each(function ($user)
        {
            $user->team_yj += $user->per_yj;
            // $user->per_yj = $user->today_add_yj;
        });
        $data = [
            'max_yj'       => $maxYj,
            'max_add_yj'   => $maxAddYj,
            'small_yj'     => $minYj,
            'small_add_yj' => $minAddYj,
            'recommends'   => $childs
        ];

        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 获取推荐的人数+获取的佣金
     * @param Request $request
     * @return array
     */
    public function recommendInfo(Request $request)
    {
        $user = $request->user;

        $query = User::whereRaw("find_in_set({$user->id},relationship)");

        $data['recommends'] = $query->count();
        $data['commission'] = 0;
        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 我的佣金
     * @param Request $request
     * @return array
     */
    public function commissionDetails(Request $request)
    {
        $user = $request->user;
        $details = AssetRelease::where('uid', $user->id)->whereIn('order_type', [2, 3])
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $details);

    }

    public function randFloat($min = 0, $max = 1, $sep = 2)
    {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return floatval(number_format($rand, $sep));
    }

    /**
     * 用户资产表
     *
     */
    public function asset_info(Request $request)
    {
        $list = array();
        $user_info = $request->user;
        $user = User::where('id', $user_info['id'])->first();
        $list['id'] = $user->id;
        $list['mobile_number'] = $user->mobile_number;
        $list['email'] = $user->email;
        $list['auth_level'] = $user->auth_level;
        $list['nickname'] = $user->nickname;
        $str = 'vb:indexTickerAll:usd2cny';
        $exrate = json_decode(Redis::get($str), true);
        if (empty($exrate)) {
            return __return($this->errStatus, '获取USDT最新价格失败');
        }
        $cny = $exrate['USDT'];
        $ycy_usdt = UserAssets::where(['uid' => $user->id, 'pid' => '8', 'ptype' => 2])->first();
        if (empty($ycy_usdt)) {
            $data = array();
            $data['uid'] = $user->id;
            $data['pid'] = '8';
            $data['pname'] = Products::where('pid', 9)->value('code');
            $data['balance'] = 0;
            $data['frost'] = 0;
            $data['ptype'] = 2;
            UserAssets::create($data);
            $ycy_usdt = UserAssets::where(['uid' => $user->id, 'pid' => '8', 'ptype' => 2])->first();
        }
        $list['usdt_balance'] = floor($ycy_usdt->balance * 1000000) / 1000000;
        $list['cusdt_balance'] = floor($ycy_usdt->balance * $cny * 10000) / 10000;

        $ycy_dev = UserAssets::where(['uid' => $user->id, 'pid' => '9', 'ptype' => 2])->first();
        if (empty($ycy_dev)) {
            $data = array();
            $data['uid'] = $user->id;
            $data['pid'] = '9';
            $data['pname'] = Products::where('pid', 9)->value('code');
            $data['balance'] = 0;
            $data['frost'] = 0;
            $data['ptype'] = 2;
            UserAssets::create($data);
            $ycy_dev = UserAssets::where(['uid' => $user->id, 'pid' => '9', 'ptype' => 2])->first();
        }

        $dev_price = Redis::get('vb:ticker:newprice:c20t_usdt');
        if (empty($dev_price)) {
            return __return($this->errStatus, '获取最新C20T价格信息失败');
        }

        $list['dev_balance'] = floor($ycy_dev->balance * 10000) / 10000;
        $list['cdev_balance'] = floor($ycy_dev->balance * $cny * $dev_price * 10000) / 10000;
        $list['cny'] = $cny;
        $list['dev_price'] = $dev_price;
        $list['dev_frozen'] = floor($ycy_dev->frost * 10000) / 10000;
        $list['cdev_frozen'] = floor($ycy_dev->frost * $dev_price * $cny * 100) / 100;

        $jys_usdt = UserAssets::where(['uid' => $user->id, 'pid' => '8', 'ptype' => 1])->first();
        if (empty($jys_usdt)) {
            $data = array();
            $data['uid'] = $user->id;
            $data['pid'] = '8';
            $data['pname'] = Products::where('pid', 9)->value('code');
            $data['balance'] = 0;
            $data['frost'] = 0;
            $data['ptype'] = 1;
            UserAssets::create($data);
            $jys_usdt = UserAssets::where(['uid' => $user->id, 'pid' => '8', 'ptype' => 2])->first();
        }
        $list['usdt_money'] = floor($jys_usdt->balance * 10000) / 10000;
        $list['cusdt_money'] = floor($list['usdt_money'] * $cny * 100) / 100;

        $jys_dev = UserAssets::where(['uid' => $user->id, 'pid' => '9', 'ptype' => 1])->first();
        if (empty($jys_dev)) {
            $data = array();
            $data['uid'] = $user->id;
            $data['pid'] = '9';
            $data['pname'] = Products::where('pid', 9)->value('code');
            $data['balance'] = 0;
            $data['frost'] = 0;
            $data['ptype'] = 1;
            UserAssets::create($data);
            $jys_dev = UserAssets::where(['uid' => $user->id, 'pid' => '9', 'ptype' => 1])->first();
        }
        $list['dev_money'] = floor($jys_dev->balance * 10000) / 10000;
        $list['cdev_money'] = floor($jys_dev->balance * $dev_price * $cny * 100) / 100;

        $list['level'] = 0;
        if ($user->total_usdt >= 30000) {
            $list['level'] = 1;
        }
        if ($user->total_usdt >= 150000) {
            $list['level'] = 2;
        }
        if ($user->total_usdt >= 400000) {
            $list['level'] = 3;
        }
        if ($user->otal_usdt >= 1000000) {
            $list['level'] = 4;
        }
        if ($user->total_usdt >= 2000000) {
            $list['level'] = 5;
        }
        return __return($this->successStatus, '获取成功', $list);
    }




    public function transfer(Request $request)
    {
        $user = $request->user;
        $pid = $request->input('pid', 1);

        if ($user->stoped) {
            return __return($this->errStatus, '您已被冻结，请联系管理员');
        }
        $validator = Validator::make(
            $request->all(),
            [
                'email'            => 'required',
                'pid'              => 'required',
                'money'            => 'required|numeric|min:1',
                'payment_password' => 'required',
            ],
            [
                'email.required'            => '对方用户不能为空',
                'pid.required'              => '钱包类型不能为空',
                'money.required'            => '金额不能为空',
                'money.numeric'             => '金额错误',
                'money.min'                 => '最小金额为1',
                'payment_password.required' => '支付密码不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }
        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '交易密码输入错误');
        }

        $product = Product::where(['pid' => $request->pid])->first();
        if (empty($product)) {
            return __return($this->errStatus, '资产类型错误');
        }

        $userAsset = UserAssets::where(['pid' => $pid, 'uid' => $user->id])->first();
        if (!$userAsset) {
            return __return($this->errStatus, '个人资产不存在');
        }
        $otherUser = User::where(['phone' => $request->email])->orWhere(['email' => $request->email])->first();

        if (!$otherUser) {
            return __return($this->errStatus, '对方用户信息不存在');
        }
        if ($otherUser->id == $user->id) {
            return __return($this->errStatus, '不能给自己转账');
        }
        $otherUserAsset = UserAssets::where(['pid' => $pid, 'uid' => $otherUser->id])->first();
        if (!$otherUserAsset) {
            return __return($this->errStatus, '对方资产不存在');
        }

        if ($request->pid == 8) {
            // 互转U需是10的倍数
            if ($request->money % 10 != 0) {
                return __return($this->errStatus, '转U数量需是10的倍数');
            }
        }


        $totalMoney = $request->money;
        // 手续费是门票
        $shouxufei = config('trans.transfer_fee');
        if ($pid == 4) {
            // 转的是门票
            $totalMoney = $totalMoney + $shouxufei;
        } else {
            $piaoAsset = UserAssets::getBalance($user->id, 4);
            if ($piaoAsset->balance < $shouxufei) {
                return __return($this->errStatus, '票余额不足');
            }
        }


        if ($userAsset->balance < $totalMoney) {
            return __return($this->errStatus, '该钱包剩余额度不足');
        }

        DB::beginTransaction();
        try {
            $bi = Products::where(['pid' => $request->pid])->first();
            $data = array();
            $data['ordnum'] = 'HZ' . get_rand_str(8);
            $data['uid'] = $user->id;
            $data['price'] = $request->money;
            $data['other_uid'] = $otherUser->id;
            $data['memo'] = '转出:' . ($otherUser->email ? $otherUser->email : $otherUser->phone);
            $data['en_memo'] = 'transfer out:' . ($otherUser->email ? $otherUser->email : $otherUser->phone);
            $data['ptype'] = 1;
            $data['pid'] = $request->pid;
            $data['type'] = 3;
            $re1 = AssetTransfer::create($data);
            $user_asset = UserAssets::where(['uid' => $user->id, 'pid' => $request->pid])->first();

            $enMemo = 'transfer out: ' . ($otherUser->email ? $otherUser->email : $otherUser->phone);
            $twMemo = '轉出: ' . ($otherUser->email ? $otherUser->email : $otherUser->phone);
            //$asset, $order_id, $money, $type, $mark, $en_mark, $pid = 8, $pname = 'usdt', $ptype = 1
            $this->writeBalanceLog($user_asset, $re1->id, -$data['price'], 3, $data['memo'], $enMemo, $request->pid, $user_asset->pname);
            if ($shouxufei > 0) {
                $piaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                $this->writeBalanceLog($piaoAsset, $re1->id, -$shouxufei, 19, '转账手续费', 'transfer fee', $piaoAsset->pid, $piaoAsset->pname);
                //平台收银台begin
                // $pingtai = DB::table('users')->where(['id' => 1])->first();
                // $ptzhanghu = UserAssets::where(['uid' => $pingtai->id, 'pid' => $request->pid])->first();
                // $this->writeBalanceLog($ptzhanghu, 0, 1, $shouxufei, 2, '转账手续费', 'trnsfer fee', '轉帳手續費');
                //平台收银台end
            }


            $data = array();
            $data['ordnum'] = 'HZ' . get_rand_str(8);
            $data['uid'] = $otherUser->id;
            $data['price'] = $request->money;
            $data['other_uid'] = $user->id;

            $enMemo = 'transfer in: ' . ($user->email ? $user->email : $user->phone);
            $twMemo = '轉入: ' . ($user->email ? $user->email : $user->phone);
            $data['memo'] = '转入:' . ($user->email ? $user->email : $user->phone);
            $data['en_memo'] = 'transfer in:' . ($user->email ? $user->email : $user->phone);
            $data['ptype'] = 1;
            $data['pid'] = $request->pid;
            $data['type'] = 3;
            $re2 = AssetTransfer::create($data);
            $other_asset = UserAssets::where(['uid' => $otherUser->id, 'pid' => $request->pid])->first();
            $this->writeBalanceLog($other_asset, $re2->id, $data['price'], 3, $data['memo'], $data['en_memo'], $request->pid, $other_asset->pname, $data['ptype']);

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return __return($this->errStatus, '请求失败');
        }
    }




    /**
     * 转账
     *
     */
    public function transfer1(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'pid'   => 'required',
                'type'  => 'required',
                'money' => 'required|numeric',
                //                'pay_password' => 'required',
            ],
            [
                'pid.required'   => '币种不能为空',
                'type.required'  => '划转类型不能为空',
                'money.required' => '划转金额不能为空',
                //                'pay_password.required' => '支付密码不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user_info = $request->user;
        if ($user_info->stoped == 0) {
            return __return($this->errStatus, '该账号已经被冻结');
        }
        $user = User::where('id', $user_info['id'])->first();
        //        if (empty($user->payment_password)) {
//            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
//        }
//        if (!Hash::check($request->pay_password, $user->payment_password)) {
//            return __return($this->errStatus, '支付密码错误');
//        }
        $ptype = floor($request->type / 10);
        $other_ptype = floor($request->type % 10);

        //        $pmemo       = '';
//        $other_pmemo = '';
//        switch ($request->type) {
//            case 12:
//                $pmemo       = '资金账户划转到币币账户';
//                $other_pmemo = 'Transfer the funds account to the currency account';
//                break;
//            case 13:
//                $pmemo       = '资金账户划转到合约账户';
//                $other_pmemo = 'Transfer funds account to contract account';
//                break;
//            case 14:
//                $pmemo       = '资金账户划转到期权账户';
//                $other_pmemo = 'Transfer funds account to options account';
//                break;
//            case 21:
//                $pmemo       = '币币账户划转到资金账户';
//                $other_pmemo = 'Transfer currency account to capital account';
//                break;
//            case 23:
//                $pmemo       = '币币账户划转到合约账户';
//                $other_pmemo = 'Transfer currency account to contract account';
//                break;
//            case 24:
//                $pmemo       = '币币账户划转到期权账户';
//                $other_pmemo = 'Transfer currency account to option account';
//                break;
//            case 31:
//                $pmemo       = '合约账户划转到资金账户';
//                $other_pmemo = 'Transfer the contract account to the capital account';
//                break;
//            case 32:
//                $pmemo       = '合约账户划转到币币账户';
//                $other_pmemo = 'Transfer the contract account to the currency account';
//                break;
//            case 34:
//                $pmemo       = '合约账户划转到期权账户';
//                $other_pmemo = 'Transfer of contract account to option account';
//                break;
//            case 41:
//                $pmemo       = '期权账户划转到资金账户';
//                $other_pmemo = 'Transfer option account to capital account';
//                break;
//            case 42:
//                $pmemo       = '期权账户划转到币币账户';
//                $other_pmemo = 'Transfer option account to currency account';
//                break;
//            case 43:
//                $pmemo       = '期权账户划转到合约账户';
//                $other_pmemo = 'Transfer option account to contract account';
//                break;
//            default:
//                break;
//        }
        $user_account = UserAssets::where(['uid' => $user->id, 'pid' => $request->pid, 'ptype' => $ptype])->first();
        if ($user_account->balance < $request->money) {
            return __return($this->errStatus, '该钱包剩余额度不足');
        }
        DB::beginTransaction();
        try {
            $pname = WalletCode::getCode($request->pid);
            $data = array();
            $data['ordnum'] = 'HZ' . md5(md5(time() . $user->id . '1' . mt_rand(1000, 9999)));
            $data['uid'] = $user->id;
            $data['price'] = -$request->money;
            $data['memo'] = $request->type;
            $data['en_memo'] = $request->type;
            $data['ptype'] = $ptype;
            $data['pid'] = $request->pid;
            $data['type'] = $request->type;
            $re1 = AssetTransfer::create($data);
            $user_asset = UserAssets::where([
                'uid'   => $user->id,
                'pid'   => $request->pid,
                'ptype' => $data['ptype']
            ])->first();
            $this->writeBalanceLog($user_asset, $re1->id, $data['price'], $request->type, $data['memo'], $data['en_memo'], $request->pid, $pname, $data['ptype']);
            $other_data = array();
            $other_data['price'] = $request->money;
            $other_data['memo'] = $request->type;
            $other_data['en_memo'] = $request->type;
            $other_data['ptype'] = $other_ptype;
            $re2 = AssetTransfer::create($data);
            $other_asset = UserAssets::where([
                'uid'   => $user->id,
                'pid'   => $request->pid,
                'ptype' => $other_data['ptype']
            ])->first();
            $this->writeBalanceLog($other_asset, $re2->id, $other_data['price'], $request->type, $other_data['memo'], $other_data['en_memo'], $request->pid, $pname, $other_data['ptype']);
            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return __return($this->errStatus, '请求失败');
        }
    }

    /**
     * 指定代币转账单
     * 转账记录
     *
     */
    public function transfer_list(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'pid' => 'required',
            ],
            [
                'pid.required' => '币种不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $user = $request->user;
        $pid = $request->pid;
        $lists = AssetTransfer::where(['uid' => $user->id, 'pid' => $pid])->orderBy('id', 'desc')
            ->paginate(10);
        $locale = $request->header('locale', 'zh-CN');
        foreach ($lists as $list) {
            if (strpos($list->memo, '转入') !== false) {
                $list->price = '+' . $list->price;
            } else {
                $list->price = '-' . $list->price;
            }
            # code...
            if ($locale == 'en') {
                $list->memo = strtr($list->memo, ['转入' => 'transfer in', '转出' => 'transfer out']);
            } else if ($locale == 'zh-TW') {
                $list->memo = strtr($list->memo, ['转入' => '轉入', '转出' => '轉出']);
            }
        }
        return __return($this->successStatus, '获取成功', $lists);
    }

    /**
     * 所有代币转账
     * 转账记录
     *
     */
    public function transfer_lists(Request $request)
    {
        $user = $request->user;
        $list = AssetTransfer::where(['uid' => $user->id])
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     * 用户资金明细
     * @param Request $request
     * @return array
     */
    public function userMoneyLog(Request $request)
    {
        $locale = $request->header('locale', 'zh-CN');
        $validator = Validator::make(
            $request->all(),
            [
                'pid' => 'required',
            ],
            [
                'pid.required' => '币种不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;
        $logs =
            UserMoneyLog::select('id', 'uid', 'money', 'nmoney', 'type', 'mark', 'en_mark', 'pid', 'pname', 'created_at', 'updated_at')
                ->where('uid', $user->id)
                ->where('pid', $request->pid)
                ->where('ptype', 1)
                ->orderBy('id', 'desc')
                ->paginate(10);
        foreach ($logs as $log) {
            if ($log->type == 3) {
                if ($locale == 'en') {
                    $log->type = strtr($log->mark, ['转入' => 'transfer in', '转出' => 'transfer out']);
                } else if ($locale == 'zh-TW') {
                    $log->type = strtr($log->mark, ['转入' => '轉入', '转出' => '轉出']);
                } else {
                    $log->type = $log->mark;
                }
            } else {
                $type = $log->type;
                if ($locale == 'zh-CN') {
                    $log->type = $log->mark;
                } else if ($locale == 'en') {
                    $log->type = $log->en_mark;
                } else {
                    $log->type = UserMoneyLog::logTypeLang($request->header('locale'), $log->type);
                }
                if (!$log->type) {
                    $log->type = UserMoneyLog::logTypeLang($request->header('locale'), $type);
                }
            }
        }
        return __return($this->successStatus, '获取成功', $logs);
    }

    /**
     * 用户资金明细
     * @param Request $request
     * @return array
     */
    public function userMoneyList(Request $request)
    {
        $user = $request->user;
        $logs =
            UserMoneyLog::select('id', 'uid', 'money', 'ptype', 'type', 'mark', 'en_mark', 'mark3', 'mark4', 'mark5', 'mark6', 'pid', 'pname', 'created_at', 'updated_at')
                ->where('uid', $user->id)
                ->orderBy('id', 'desc')
                ->paginate(20);
        return __return($this->successStatus, '获取成功', $logs);
    }

    /**
     * 释放记录 矿池
     *
     */
    public function release_list(Request $request)
    {
        $user_info = $request->user;
        $user = User::where('id', $user_info['id'])->first();
        $list = AssetRelease::where(['uid' => $user->id, 'order_type' => 1])
            ->orderBy('id', 'desc')
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $list);
    }

    public function releaseCfa(Request $request)
    {
        $user = $request->user;

        $todayRelease = SystemValue::where(['name' => 'cfa_release_num'])->value('value');
        $todayRelease -= 1;
        $market = $todayRelease * config('release.market') * 0.01;
        $superNode = $todayRelease * 0.15;
        $develop = $todayRelease * 0.05;
        $company = $todayRelease * 0.1;

        $data = [
            'today'      => $todayRelease,
            'market'     => (string)round($market, 6),
            'super_node' => (string)round($superNode, 6),
            'develop'    => (string)round($develop, 6),
            'company'    => (string)round($company, 6)
        ];

        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 我的團隊
     */
    public function my_team(Request $request)
    {
        $user = $request->user;

        $query = User::whereRaw("find_in_set({$user->id},relationship)");

        $num = $query->count();
        $users = $query->select(['id', 'email', 'nickname', 'created_at'])->paginate(10);
        foreach ($users as $user) {
            $user->email = substr_cut($user->email);
        }
        return __return($this->successStatus, '获取成功', ['total' => $num, 'users' => $users]);
    }

    /**
     * 团队列表 废弃
     *
     * @return \Illuminate\Http\Response
     */
    public function team_list(Request $request)
    {
        $user_info = $request->user;
        $user = User::where('id', $user_info['id'])->first();
        $list = UserPosition::leftJoin('users', 'users.id', '=', 'user_position.uid')
            ->where(['user_position.pid' => $user->id, 'user_position.lay' => 1])
            ->select(DB::raw('user_position.uid as id,users.account,users.phone,users.email,users.total_usdt'))
            ->orderBy('users.id')->paginate(20);
        //->orderBy('user_position.lay')->paginate(20);
        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['usdt_num'] = floor($list[$key]['total_usdt']);
                $list[$key]['usdt_money'] = AssetOrder::where('uid', $val['id'])->sum('usdt_num') + 0;
                $list[$key]['usdt_money'] = floor($list[$key]['usdt_money']);
                $firstStr = mb_substr($list[$key]['account'], 0, 3);
                $lastStr = mb_substr($list[$key]['account'], -4);
                //str_repeat — 重复一个字符串
                $list[$key]['account'] = $firstStr . '***' . $lastStr;
                if ($val['email']) {
                    $list[$key]['email'] = substr_replace($val['email'], '****', 3, 4);
                }
            }
        }
        // $list = array();
        return __return($this->successStatus, '获取成功', $list);
    }

    #推荐节点关系
    public function relationUser($pid, $uid)
    {
        $list = UserPosition::where(['uid' => $pid])->get();
        $time = date('YmdHis');
        $newList = [];
        $newList[0]['uid'] = $uid;
        $newList[0]['pid'] = $pid;
        $newList[0]['lay'] = 1;
        $newList[0]['created_at'] = $time;
        if ($list) {
            foreach ($list as $k => $v) {
                $newList[$k + 1]['uid'] = $uid;
                $newList[$k + 1]['pid'] = $v['pid'];
                $newList[$k + 1]['lay'] = $v['lay'] + 1;
                $newList[$k + 1]['created_at'] = $time;
            }
        }
        $re = UserPosition::insert($newList);
        return $re;
    }

    /**
     * 币种截取
     * @param $str
     * @return array|bool|string
     */
    function coin_cut($str)
    {
        $arr = explode('_', $str);
        return $arr;
    }

    public function nicheng(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'nickname' => 'required',
            ],
            [
                'nickname.required' => '昵称必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;
        $user->nickname = $request->nickname;
        $user->save();
        return __return($this->successStatus, '修改昵称成功');
    }

    //设置手势按钮 未使用
    public function createshoushiPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'password'              => 'required',
                'password_confirmation' => 'required'
            ],
            [
                'password.required'              => '手势不能为空',
                'password_confirmation.required' => '确认收拾密码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $user = $request->user;
        $config = $request->user_config;

        if ($request->password != $request->password_confirmation) {
            return __return($this->errStatus, '两次密码输入不一致');
        }
        //检测验证码
        $user->shoushiword = Hash::make($request->password);
        $user->save();
        $config->shoushi_set = 1;
        $config->save();
        return __return($this->successStatus, '设置成功');
    }

    //手势按钮开关 未使用
    public function shoushi_but(Request $request)
    {
        $config = $request->user_config;
        $user = $request->user;
        //验证是否绑定
        if ($config->shoushi_set == 0) {
            return __return($this->errStatus, '没有设置手势密码');
        }
        //开启谷歌验证
        if ($request->key == 'start') {
            $config->shoushi_open = 1;
            $config->save();
            return __return($this->successStatus, '开启成功');
        }
        //关闭谷歌验证
        if ($request->key == 'stop') {
            $config->shoushi_open = 0;
            $config->save();
            return __return($this->successStatus, '关闭成功');
        }
    }

    //验证手势密码 未使用
    public function shoushi_check(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id'       => 'required',
                'password' => 'required',
            ],
            [
                'id.required'       => '参数错误',
                'password.required' => '手势密码不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = User::where('id', $request->id)->first();
        if (empty($user)) {
            return __return($this->errStatus, '用户不存在');
        }
        $config = DB::table('user_config')->where(['uid' => $user->id])->first();
        //验证是否绑定
        if ($config->shoushi_set == 0) {
            return __return($this->errStatus, '没有设置手势密码');
        }
        if ($config->shoushi_open == 0) {
            return __return($this->errStatus, '没有开启手势密码');
        }
        if (!Hash::check($request->password, $user->shoushiword)) {
            return __return($this->errStatus, '手势密码错误');
        }
        $success['token'] = $user->createToken('api')->accessToken;
        $success['secret'] = 0;
        return __return($this->successStatus, '登录成功', $success);
        // return __return($this->successStatus, '验证成功');
    }

    //未使用
    public function zhangben(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'pid' => 'required',
            ],
            [
                'pid.required' => '币种编号不能为空',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        if (!in_array($request->pid, array(8, 9, 10))) {
            return __return($this->errStatus, '钱包类型错误');
        }
        $user = $request->user;
        $list = UserMoneyLog::where(['uid' => $user->id, 'pid' => $request->pid])->orderBy('id', 'desc')
            ->paginate(10);
        $usdt2CnyRedis =
            $this->subscribe_redis->get('vb:indexTickerAll:usd2cny'); // 1usdt = 7.08 cny
        if ($usdt2CnyRedis) {
            $usdt2CnyRedisArr = json_decode($usdt2CnyRedis, true);
            $usdtToCnyRate = $usdt2CnyRedisArr['USDT'];
        } else {
            $usdtToCnyRate = 6.535;
        }

        //$reoToUsdtRate = 0.5; // 1 REO = 0.15 USDT
        $reoToUsdtRate = $this->subscribe_redis->get('vb:ticker:newprice:ero_usdt');
        if (empty($reoToUsdtRate)) {
            $reoToUsdtRate = 0.5;
        }
        //$reoToUsdtRate=0.5;
        $tcToCny =
            config('site.tc_price'); // 单位为元，人民币
        $tcToUsdtRate = $tcToCny / $usdtToCnyRate;
        foreach ($list as $key => $val) {
            $list[$key]['balance'] = UserAssets::where(['uid' => $user->id, 'pid' => $request->pid])
                ->value('balance') + 0;
            if ($request->pid == 8) {
                $list[$key]['cny'] = round($list[$key]['balance'] * $usdtToCnyRate, 4);
            } elseif ($request->pid == 9) {
                $list[$key]['cny'] = round($list[$key]['balance'] * $reoToUsdtRate * $usdtToCnyRate, 4);
            } elseif ($request->pid == 10) {
                $list[$key]['cny'] = round($list[$key]['balance'] * $tcToCny, 4);
            }
        }
        return __return($this->successStatus, '获取成功', $list);
    }

    //未使用
    public function wanshan(Request $request)
    {
        return __return($this->successStatus, '获取成功', []);
        $shuju = DB::table('users')->orderBy('id', 'asc')->get()->toarray();
        foreach ($shuju as $key => $value) {
            $peizhi = DB::table('user_config')->where(['uid' => $value->id])->first();
            if (empty($peizhi)) {
                $data = array();
                $data['uid'] = $value->id;
                $data['created_at'] = now();
                $data['updated_at'] = now();
                $res = DB::table('user_config')->insert($data);
            }
        }
        return __return($this->successStatus, '获取成功', $shuju);

    }

    /**
     * 资产信息
     * @param Request $request
     * @return array
     */
    public function assetInfo(Request $request)
    {
        $user = $request->user;
        $locale = $request->header('locale');
        $userAssets = UserAssets::where(['uid' => $user->id])->select(['id', 'pid', 'pname', 'balance', 'frost'])->get();
        foreach ($userAssets as $userAsset) {
            $product = Product::where(['pid' => $userAsset->pid])->first();
            $userAsset->image = $product->image;
            if ($userAsset->pid == 3) {
                // USDT(收益)
                if ($locale == 'en') {
                    $userAsset->pname = 'USDT(balance)';
                } else if ($locale == 'zh-TW') {
                    $userAsset->pname = 'USDT(收益)';
                }
            }
            if ($userAsset->pid == 6) {
                // CFT
                if ($locale == 'en') {
                    $userAsset->pname = 'CFT(hire)';
                } else if ($locale == 'zh-TW') {
                    $userAsset->pname = 'CFT(租)';
                }
            }
        }
        return __return($this->successStatus, '获取成功', $userAssets);
    }

    //获取划转可用余额
    public function assetInfoBalance(Request $request)
    {
        $user = $request->user;
        $ptype = $request->type;
        $pid = $request->pid ?? 8;
        $balance = UserAssets::where(['uid' => $user->id, 'ptype' => $ptype, 'pid' => $pid])
            ->value('balance');
        return __return($this->successStatus, '划转余额', ['balance' => $balance]);

    }

    /**
     * 资产总览接口
     * @param Request $request
     */
    public function assetInfoAll(Request $request)
    {
        $user = $request->user;
        $user_assets = UserAssets::where(['uid' => $user->id])->get()->toArray();
        $asset1 = 0; //资金账户
        $asset2 = 0; //币币账户
        $asset3 = 0; //合约账户
        $asset4 = 0; //期权账户
        $asset5 = 0; //矿池账户
        $asset_total = 0; //总账户
        foreach ($user_assets as $key => $val) {
            //根据pid获取响应code
            $code = Product::pidGetField($val['pid'], 'code');
            $str = 'vb:ticker:newprice:' . $code;
            $newprice = Redis::get($str);
            if (empty($newprice)) {
                $newprice = 1;
            }
            if ($val['ptype'] == 1) {
                $asset1 += $val['balance'] * $newprice;
                $asset1 += $val['frost'] * $newprice;
            } else if ($val['ptype'] == 2) {
                $asset2 += $val['balance'] * $newprice;
                $asset2 += $val['frost'] * $newprice;
            } else if ($val['ptype'] == 3) {
                $asset3 += $val['balance'] * $newprice;
                $asset3 += $val['frost'] * $newprice;
            } else if ($val['ptype'] == 4) {
                $asset4 += $val['balance'] * $newprice;
                $asset4 += $val['frost'] * $newprice;
            } else if ($val['ptype'] == 5) {
                $asset5 += $val['balance'] * $newprice;
                $asset5 += $val['frost'] * $newprice;
            }
            //总数量增加
            $asset_total += $val['balance'] * $newprice;
        }
        $data = [
            'asset1'      => $asset1,
            'asset2'      => $asset2,
            'asset3'      => $asset3,
            'asset4'      => $asset4,
            'asset5'      => $asset5,
            'asset_total' => $asset_total,
        ];
        return __return($this->successStatus, '资产总览', $data); // 下单成功

    }

    /**
     * 私募下单
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id'               => 'required',
                'buynum'           => 'required|numeric|min:1',
                'payment_password' => 'required',
            ],
            [
                'id.required'               => '私募ID必须',
                'buynum.required'           => '购买数量必须',
                'payment_password.required' => '支付密码必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $user = $request->user;
        $buynum = $request->buynum;
        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }
        DB::beginTransaction();
        try {
            $coin = ApplyCoin::select('id', 'code', 'fxnum', 'fxprice', 'soldnum', 'status', 'endtime')
                ->where(['id' => $request->id, 'status' => 1])->first();
            if (empty($coin)) {
                return __return($this->errStatus, '该私募已经结束，请确认');
            }
            if ($coin->endtime <= now()) {
                ApplyCoin::where('id', $coin->id)->update(['status' => 3]);
                DB::commit();
                return __return($this->errStatus, '该私募已经结束，请确认');
            }
            if ($buynum + $coin->soldnum >= $coin->fxnum) {
                $buynum = $coin->fxnum - $coin->soldnum;
                if ($buynum <= 0) {
                    return __return($this->errStatus, '该私募数量不足，请确认');
                }
            }
            $total_price = number_format($buynum * $coin->fxprice, '6', '.', '');
            $sm_fee = config('site.sm_fee');
            $fee = number_format($total_price * $sm_fee * 0.01, '6', '.', '');
            $asset = UserAssets::getBalance($user->id, 8, 1, true);
            if ($asset->balance < floatval($total_price) + floatval($fee)) {
                return __return($this->errStatus, '账户余额不足');
            }

            $res1 = ApplyCoin::where('id', $request->id)->increment('soldnum', $buynum);
            $res2 =
                $this->writeBalanceLog($asset, $request->id, -$total_price, 15, '私募交易', 'Private placement transaction', 8, 'USDT', 1);
            if ($fee > 0) {
                $res3 =
                    $this->writeBalanceLog($asset, $request->id, -$fee, 16, '私募交易手续费', 'Private equity transaction fee', 8, 'USDT', 1);
            } else {
                $res3 = 1;
            }
            $p_id = Products::where('launch_id', $request->id)->value('pid');
            $passet = UserAssets::getBalance($user->id, $p_id, 2, true);
            $res4 =
                $this->writeFrostLog($passet, $request->id, $total_price, 15, '私募交易', 'Private placement transaction', $p_id, $coin->code, 2);
            $res5 = SmOrder::create([
                'uid'        => $user->id,
                'account'    => $user->account,
                'pid'        => $request->id,
                'pname'      => $coin->code,
                'price'      => $coin->fxprice,
                'num'        => $buynum,
                'totalprice' => $total_price,
                'fee'        => $fee,
            ]);
            if ($res1 && $res2 && $res3 && $res4 && $res5) {
                DB::commit();
                return __return($this->successStatus, '下单成功'); // 下单成功
            } else {
                DB::rollBack();
                return __return($this->errStatus, '下单失败'); // 下单失败
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return __return($this->errStatus, '下单失败'); // 下单失败
        }
    }



    // 团队节点
    public function teamNodes(Request $request)
    {
        $user = $request->user;
        $nodes = User::where('node_level', '>', 0)->whereRaw("find_in_set({$user->id},relationship)")->select(['id', 'email', 'nickname', 'node_level'])->paginate(10);
        $wangNum = User::where(['node_level' => 1])->whereRaw("find_in_set({$user->id},relationship)")->count();
        $zhiNum = User::where(['node_level' => 1])->whereRaw("find_in_set({$user->id},relationship)")->count();
        $daNum = User::where(['node_level' => 3])->whereRaw("find_in_set({$user->id},relationship)")->count();
        foreach ($nodes as $node) {
            if ($node->node_level == 1) {
                $node->node_name = '王者';
            } else if ($node->node_level == 2) {
                $node->node_name = '至尊';
            } else if ($node->node_level == 3) {
                $node->node_name = '大赢家';
            }
        }
        $data = [];
        $data['wang_num'] = $wangNum;
        $data['zhi_num'] = $zhiNum;
        $data['da_num'] = $daNum;
        $data['nodes'] = $nodes;
        return __return($this->successStatus, '获取成功', $data);
    }

    public function myRecommends(Request $request)
    {
        $user = $request->user;
        $childs = User::where(['recommend_id' => $user->id])->select(['id', 'email', 'nickname', 'is_pooler'])->orderBy('is_pooler', 'desc')->paginate(10);
        $recommendNum = User::where(['recommend_id' => $user->id])->count();

        $teamNum = User::whereRaw("find_in_set({$user->id},relationship)")->count();
        // 人数  是否是池主
        foreach ($childs as $child) {
            $chilsTeamNum = User::whereRaw("find_in_set({$child->id}, relationship)")->count();
            $child->email = substr_cut($child->email);
            $chilsTeamNum += 1;
            $child->team_num = $chilsTeamNum;
        }

        $data = [];
        $data['recommend_num'] = $recommendNum;
        $data['team_num'] = $teamNum;
        $data['users'] = $childs;

        return __return($this->successStatus, '获取成功', $data);
    }



}