<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyCode;
use App\Models\EmailLog;
use App\Servers\PinDuoDuo\Exceptions\Exception;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use function __return;

class UserInfoController extends Controller
{
    /**
     * 注册用户（根据role_type来区分类型）
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function register(Request $request): array
    {
        $param = $request->all();
        $type  = $param['reg_type'] ?? '';
//        $roleType = $param['role_type'] ?? 0;
        $addData = [
            'password' => Hash::make($request['password'] ?? 123456),
            //            'role_type' => $roleType
        ];
        switch ($type) {
            case 0://普通用户
                //验证
                $validate = Validator::make($param, [
                    'account' => 'required',
                    'name'    => 'required',
                ]);
                if ($validate->fails()) {
                    $error = $validate->errors();
                    return __return($this->errStatus, $error->first());
                }
                $addData['role_type'] = 0;

                $addData['name']    = $param['name'] ?? '';
                $addData['avatar']  = $param['avatar'] ?? '';
                $addData['account'] = $param['account'];
                if (empty($param['email'])) $addData['email'] = $param['account'];
                $addData['country'] = $param['country'];

                break;
            case 1://品牌方
                //验证
                $validate = Validator::make($param, [
                    'account' => 'required',
                    'code'    => 'required｜min:4|max:4'
                ]);
                if ($validate->fails()) {
                    $error = $validate->errors();
                    return __return($this->errStatus, $error->first());
                }
                $captcha = cache()->get($param['account']);
                if ($param['code'] != $captcha) {
                    return __return($this->errStatus, '验证码错误');
                }
                $addData['role_type'] = 1;
                if (!empty($param['industry_type'])) $addData['industry_type'] = is_array($param['industry_type']) ? json_encode($param['industry_type']) : json_encode(explode(',', $param['industry_type']));
                $addData['name']             = $param['name'] ?? '';
                $addData['avatar']           = $param['avatar'] ?? '';
                $addData['product_type']     = $param['product_type'] ?? '';
                $addData['official_website'] = $param['official_website'] ?? '';
                $addData['blurb']            = $param['blurb'] ?? '';
                $addData['target_market']    = $param['target_market'] ?? '';
                $addData['account']          = $param['account'];
                if (empty($param['email'])) $addData['email'] = $param['account'];
                break;
            case 2://网红用户
                //验证
                $validate = Validator::make($param, [
                    'account' => 'required',
                    'name'    => 'required',
                ]);
                if ($validate->fails()) {
                    $error = $validate->errors();
                    return __return($this->errStatus, $error->first());
                }
                $addData['role_type'] = 2;

                $addData['name']    = $param['name'] ?? '';
                $addData['avatar']  = $param['avatar'] ?? '';
                $addData['account'] = $param['account'];
                if (empty($param['email'])) $addData['email'] = $param['account'];
                $addData['whole_fans_num'] = $param['whole_fans_num'];
                if (!empty($param['commercial_labels'])) $addData['commercial_labels'] = is_array($param['commercial_labels']) ? json_encode($param['commercial_labels']) : json_encode(explode(',', $param['commercial_labels']));
                $addData['country'] = $param['country'];
                break;
            default:
                return __return($this->errStatus, '类型错误');
                break;
        }
        if (!User::create($addData)) {
            return __return($this->errStatus, '注册失败');
        }
        return __return($this->successStatus, '创建成功');
    }


    /**
     * 发送验证码
     * @param Request $request
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function sendCode(Request $request): array
    {
        $type = $request->type ?? 1;

        $pattern     = "/^([0-9A-Za-z-_.]+)@([0-9a-z]+.[a-z]{2,3}(.[a-z]{2})?)$/i";
        $accountName = '邮箱';
        if ($type == 1) {
            $accountName = '手机号';
            $pattern     = '/^1[0-9]{10}$/';
            $validator   = Validator::make(
                $request->all(),
                [
                    'area_code' => 'required',
                    'account'     => 'required',
                ],
                [
                    'area_code.required' => '区号必须',
                    'account.required'     => '手机号必须',
                ]
            );
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                return __return($this->errStatus, $errors[0]);
            }
        }


        $account = $request->post('account');
        //验证
        if (empty($account)) return __return($this->errStatus, $accountName . '不能为空');
        if (!preg_match_all($pattern, $account)) return __return($this->errStatus, $accountName . '格式不正确');
        //获取缓存
        $time  = date('Y-m-d', cache()->get($account . '_time'));
        $count = cache()->get($account . '_count') ?: 0;
        //判断每天发送次数
        if ($count > 5) {
            return ['code' => 4000, 'msg' => '发送已达上限', 'data' => ''];
        }
        //当天的时间
        $newDay = date('Y-m-d', time());
        if ($newDay > $time) {
            $count = 1;
        } else {
            $count++;
        }
        $captcha = rand(1300, 9888);
        if ($type == 2) {
            $sign = config('system.VerifyCodeSign');
            Mail::to($account)->send(new VerifyCode($sign, $captcha));
        } else if ($type == 1) {
            $result = $this->doSendSms($request->phone, $request->ip(), $request->area_code);
            if ($result['code'] != 200) {
                return __return($this->errStatus, $result['msg']);
            }
        }
        //存储到缓存中
        cache()->set($account, $captcha, 300);             //5分钟后过期
        cache()->set($account . '_time', time(), 60 * 24); //记录最后一次发送时间
        cache()->set($account . '_count', $count, 60 * 24);

        return __return($this->successStatus, '验证码发送成功');
    }

    /**
     * 获取品牌方的信息
     * @param Request $request
     * @param User $user
     * @return array
     */
    public function getUserInfo(Request $request)
    {
        if (!empty($request->uid)) {
            $user = User::query()->where(['role_type' => 1])->find($request->uid);
        } else
            $user = $request->user;
        if (empty($user)) return __return($this->errStatus, '获取失败，品牌不存在');
        $data = [
            'name'             => $user['name'],
            'logo'             => $user['avatar'],
            'product_type'     => $user['product_type'],
            'official_website' => $user['official_website'],
            'email'            => $user['email'],
            'target_market'    => $user['target_market'],
            'blurb'            => $user['blurb'],
            'industry_type'    => $user['industry_type']
        ];
        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 实体产品信息
     * @param Request $request
     * @return array
     */
    public function getPhysicalProductInfoList(Request $request)
    {
        if (empty($request->uid)) {
            $user = $request->user;
            $uid  = $user->id;
        } else $uid = $request->uid;
        $data = [
            [
                'product_name'  => '测试',
                'product_code'  => '123344',//产品编码
                'product_price' => 1.22,//产品价格
                'product_image' => '',//产品图片
                'product_link'  => '',//产品链接
            ]
        ];
        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 联名NFT合作邀请
     * @param Request $request
     * @return array
     */
    public function getNftInvitation(Request $request): array
    {
        if (empty($request->uid)) {
            $user = $request->user;
            $uid  = $user->id;
        } else $uid = $request->uid;
        $data = [
            [
                'id'                => 1,
                'country'           => '中国',//国家
                'commercial_labels' => '测试',//商业标签
                'due_time'          => '2023-10-12 12:09:02',//邀请到期日
                'leave_a_message'   => '',//留言
                'user'              => [
                    'avatar' => ''
                ]
            ]
        ];
        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 获取网红页面主信息
     * @param Request $request
     * @return array
     */
    public function getInternetCelebrity(Request $request): array
    {
        if (!empty($request->uid)) {
            $user = User::query()->where(['role_type' => 2])->find($request->uid);
        } else
            $user = $request->user;
        if (empty($user)) return __return($this->errStatus, '获取失败，网红不存在');
        $data = [
            'name'              => $user['name'],
            'avatar'            => $user['avatar'],
            'product_type'      => $user['product_type'],
            'country'           => $user['country'],
            'email'             => $user['email'],
            'commercial_labels' => json_decode($user['commercial_labels'],true),
            'industry_type'     => $user['industry_type']
        ];
        return __return($this->successStatus, '获取成功', $data);
    }

    /**\
     * 获取会员主页信息
     * @param Request $request
     * @return array
     */
    public function getMemberInfo(Request $request): array
    {
        if (!empty($request->uid)) {
            $user = User::query()->where(['role_type' => 0])->find($request->uid);
        } else
            $user = $request->user;
        if (empty($user)) return __return($this->errStatus, '获取失败，网红不存在');
        $data = [
            'name'              => $user['name'],
            'avatar'            => $user['avatar'],
            'country'           => $user['country'],
            'email'             => $user['email'],
        ];
        return __return($this->successStatus, '获取成功', $data);
    }
}
