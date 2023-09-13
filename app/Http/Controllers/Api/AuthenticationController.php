<?php

namespace App\Http\Controllers\Api;

use App\Models\Authentication;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Validator;

class AuthenticationController extends Controller
{

    protected $title = '用户身份认证';

    public function index(Request $request)
    {
        $user = $request->user;
        $info = Authentication::where('uid', $user->id)
            ->orderBy('id', 'desc')
            ->first();

        return __return($this->successStatus, '获取成功', $info);
    }

    /**
     * 初级身份认证.
     *
     * @return array|\Illuminate\Http\Response
     */
    public function primaryCertification(Request $request)
    {
        $user = $request->user;

        $validator = Validator::make(
            $request->all(),
            [
                'name'    => 'required',
                'card_id' => 'required|max:42|min:8',
            ],
            [
                'name.required'    => '真实姓名必须',
                'card_id.required' => '身份证号码必须',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        $card = Authentication::where('card_id', $request->card_id)
            ->where('status', '>=', Authentication::PRIMARY_CHECK)
            ->first();
        if (!empty($card)) {
            return __return($this->errStatus, '该身份证已经被认证');
        }

        $log = Authentication::where('uid', $user->id)
            ->where('status', '>=', Authentication::PRIMARY_CHECK)
            ->first();
        if (!empty($log)) {
            return __return($this->errStatus, '不能重复认证');
        }

        //        try {
//            $result = $this->verifyIdcard($request->card_id,$request->name);
//            Log::info(1);
//            if(empty($result)){
//                return __return($this->errStatus,'实名认证失败');
//            }
//            Log::info(2);
//            if(!$result['result']['isok']){
//                return __return($this->errStatus,'实名认证失败');
//            }
//            Log::info(3);
//
//        } catch (\Exception $e) {
//            Log::info($e->getMessage().$e->getLine());
//            return __return($this->errStatus,$e->getMessage().$e->getLine());
//        }
        $result['result'] = 1;
        Authentication::create([
            'uid'              => $user->id,
            'name'             => $request->name,
            'card_id'          => $request->card_id,
            'status'           => Authentication::PRIMARY_CHECK,
            'real_name_result' => json_encode($result['result']),
        ]);

        $user->authentication = Authentication::PRIMARY_CHECK;
        $user->name = $request->name;
        $user->save();

        $config = $request->user_config;

        $config->security_level += 1;
        $config->save();

        return __return($this->successStatus, '成功');
    }

    public function verifyIdcard($card_id, $name)
    {
        $host = "http://aliyunverifyidcard.haoservice.com";
        $path = "/idcard/VerifyIdcardv2";
        $method = "GET";
        $appcode = "d1c51e88992a493b97162fc7635e2717";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $name = urlencode($name);
        $querys = "cardNo=" . $card_id . "&realName=" . $name;
        $bodys = "";
        $url = $host . $path . "?" . $querys;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($curl);
        curl_close($curl);
        Log::info($result);
        return json_decode($result, true);

    }

    /**
     * 高级身份认证
     * @param Request $request
     * @return array
     */
    public function advancedCertification(Request $request)
    {
        $user = $request->user;

        $validator = Validator::make(
            $request->all(),
            [
                'front_img'    => 'required',
                'back_img'     => 'required',
                'handheld_img' => 'required',
            ],
            [
                'front_img.required'    => '身份证正面必须',
                'back_img.required'     => '身份证反面必须',
                'handheld_img.required' => '手持身份证照必须'
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        $log = Authentication::where('uid', $user->id)->first();
        if (empty($log)) {
            return __return($this->errStatus, '请先进行初级认证');
        }
        if ($log['status'] == 2) {
            return __return($this->errStatus, '认证审核中');
        }
        if ($log['status'] == 3) {
            return __return($this->errStatus, '认证已通过');
        }
        if (!isset($request->front_img)) {
            return __return($this->errStatus, '请上传身份证正面照');
        }
        if (!isset($request->back_img)) {
            return __return($this->errStatus, '请上传身份证反面');
        }
        if (!isset($request->handheld_img)) {
            return __return($this->errStatus, '请上传手持身份证');
        }
        //更新认证表和会员表状态
        Authentication::where('uid', $user->id)
            ->update([
                'status'       => Authentication::ADVANCED_WAIT_CHECK,
                'front_img'    => $request->front_img,
                'back_img'     => $request->back_img,
                'handheld_img' => $request->handheld_img
            ]);

        $user->authentication = Authentication::ADVANCED_WAIT_CHECK;
        $user->save();

        $config = $request->user_config;
        $config->security_level += 1;
        $config->save();

        return __return($this->successStatus, '提交成功');
    }

    public function shangchuan(Request $request)
    {
        $user = $request->user;
        $validator = Validator::make(
            $request->all(),
            [
                'images' => 'required',
            ],
            [
                'images.required' => '图片必须',
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }
        if ($request->hasFile('images')) {
            $images_upload = $request->file('images');
            Log::info($images_upload);
            $images_upload_result = $this->upload($images_upload, 'oss');
            if ($images_upload_result['code'] != 200) {
                return __return($this->errStatus, '图片' . $images_upload_result['msg']);
            }
            $images = $images_upload_result['data'];
        }
        //BASE64形式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $request->images, $result)) {
            $images_upload_result = $this->base64Upload($request->images, 'oss');
            if ($images_upload_result['code'] != 200) {
                return __return($this->errStatus, '图片' . $images_upload_result['msg']);
            }
            $images = $images_upload_result['data'];
        }
        if (!isset($images)) {
            return __return($this->errStatus, '请上传图片');
        }
        $info['lujing'] = $images;
        return __return($this->successStatus, '提交成功', $info);
    }


}