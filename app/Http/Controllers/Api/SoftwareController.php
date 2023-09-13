<?php

namespace App\Http\Controllers\Api;

use App\Models\FeedBack;
use App\Models\FeedBackType;
use App\Models\Slides;
use App\Models\SoftwareVersion;
use App\Models\SystemAgree;
use App\Models\SystemPosts;
use App\Service\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Validator;
use QrCode;

class SoftwareController extends Controller
{

    /**
     *获取网站名称、客服等信息
     */
    public function content(Request $request)
    {
        $key = $request->key;
        $content = config('site.' . $key);
        return __return($this->successStatus, '获取成功', ['content' => $content]);
    }

    /**
     * 获取平台公告
     * @param Request $request
     * @return array
     */
    public function systemPosts(Request $request)
    {
        // 2 行情资讯,
        // 3 系统公告,
        // 4 弹窗公告,
        // 5 停盘公告,
        // 6 交易指南,
        $locale = $request->header('locale');
        $locale = $locale ?? 'zh-CN';

        $content = SystemPosts::where('locale', $locale)
            ->orderBy('is_zd', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $content);
    }

    /**
     * 公告详情
     * @param Request $request
     * @return array
     */
    public function postsInfo(Request $request)
    {
        $content = SystemPosts::find($request->posts_id);

        return __return($this->successStatus, '获取成功', $content);
    }

    /**
     * 获取轮播图
     * @param Request $request
     * @return array
     */
    public function slides(Request $request)
    {
        $slides = Slides::where('position', $request->position)
            ->select('id', 'image', 'locale')
            //                        ->where('type', $request->type)
            ->where('locale', $request->header('locale'))
            ->get()
            ->each(function ($item)
            {
                $item->image = ImageService::fullUrl($item->image);
            });
        return __return($this->successStatus, '获取成功', $slides);
    }

    /**
     * 获取平台协议
     * @param Request $request
     * @return array
     */
    public function systemAgree(Request $request)
    {
        //1 => '关于我们',
        //2 => '免责声明',
        //3 => '法律声明',
        //4 => '隐私条款',
        //5 => '服务协议',
        //6 => '关于反洗钱',
        $content = SystemAgree::where('locale', $request->header('locale'))
            ->where('type', $request->get('type'))
            ->first();

        return __return($this->successStatus, '获取成功', $content);
    }

    //新手教程
    public function course_list(Request $request)
    {
        $content = SystemAgree::where('locale', $request->header('locale'))
            ->select('id', 'title', 'locale')
            ->where(['type' => 15, 'state' => 1])->get()->toArray();
        return __return($this->successStatus, '获取成功', $content);
    }

    //协议详情
    public function course_info(Request $request)
    {
        $content = SystemAgree::where('locale', $request->header('locale'))
            ->where('id', $request->get('id'))
            ->first();
        return __return($this->successStatus, '获取成功', $content);
    }

    /**
     * 反馈列表
     */
    public function feedBackList(Request $request)
    {
        $user = $request->user;
        $list = FeedBack::where('uid', $user->id)
            ->with('type')
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $list);
    }

    /**
     * 反馈类型
     */
    public function feedBackType()
    {
        $filed = array('id', 'type_name');
        $types = FeedBackType::all($filed);
        return __return($this->successStatus, '获取成功', $types);
    }

    /**
     * 创建反馈内容 包含上传文件接口，停用
     */
    public function feedBack(Request $request)
    {
        die();
        $user = $request->user;
        //        $exists = Feedback::where('uid',$user->id)
        //            ->where('reply_status',0)
        //            ->first();
        //        if(!empty($exists)){
        //            return __return($this->errStatus,'有未处理的反馈');
        //        }
        $validator = Validator::make(
            $request->all(),
            [
                'type_id'  => 'required|numeric|min:1',
                'desc'     => 'required|string|max:255',
                'user_tel' => 'required|string|max:32',
            ],
            [
                'type_id.required'  => '反馈类型必须',
                'desc.required'     => '用户反馈内容必须',
                'user_tel.required' => '用户联系方式必须',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return __return($this->errStatus, $errors[0]);
        }

        ##############################################################
        //二进制文件形式
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $file_upload_result = $this->upload($file);
            if ($file_upload_result['code'] != 200) {
                return __return($this->errStatus, '文件' . $file_upload_result['msg']);
            }
            $file = $file_upload_result['data'];
        }

        //BASE64形式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $request->file, $result)) {
            $file_upload_result = $this->base64Upload($request->file);
            if ($file_upload_result['code'] != 200) {

                return __return($this->errStatus, '文件' . $file_upload_result['msg']);
            }
            $file = $file_upload_result['data'];
        }

        $feedback = Feedback::create([
            'uid'      => $user->id,
            'type_id'  => $request->type_id,
            'desc'     => $request->desc,
            'user_tel' => $request->user_tel,
        ]);

        if (isset($file)) {
            $feedback->file = $file;
            $feedback->save();
        }

        return __return($this->successStatus, '反馈成功');
    }

    /**
     * 下载链接
     */
    public function downloadLink()
    {
        $config = DB::table('admin_config')
            ->where('name', 'site.software_link')
            ->first();
        $link = $config->value;
        $anzhuo = DB::table('admin_config')->where('name', 'site.anzhuo')->value('value');
        $pingguo = DB::table('admin_config')->where('name', 'site.pingguo')->value('value');
        $qrcode = QrCode::encoding('UTF-8')->format('png')->size(368)->margin(0)
            ->generate($link);
        $data['qrcode'] = 'data:image/png;base64,' . base64_encode($qrcode);
        $data['link'] = $link;
        $data['anzhuo'] = $anzhuo;
        $data['pinguo'] = $pingguo;
        $data['update_at'] = $config->updated_at;
        return __return($this->successStatus, '获取成功', $data);
    }

    /**
     * 查询软件是否更新
     * @param Request $request [description]
     * @return [type]           [description]
     */
    public function softwareUpdate(Request $request)
    {
        $clientVersion = $request->version;
        $type = $request->type;
        if (!in_array($type, array(1, 2, 3))) {
            return __return2($this->errStatus, '');
        }
        $version = SoftwareVersion::where('type', $type)->orderBy('id', 'desc')->first();
        if (is_null($version)) {
            return __return($this->errStatus, '');
        }
        if ($version->vercode != $clientVersion) {
            return __return($this->successStatus, '有新版本', $version);
        } else {
            return __return($this->errStatus, '');
        }
    }

    //轮播公告同时请求
    public function lunbogonggao(Request $request)
    {
        $lunbo = Slides::where('type', 1)
            ->where('position', 1)
            ->where('locale', $request->header('locale'))
            ->get()
            ->each(function ($item)
            {
                $item->image = ImageService::fullUrl($item->image);
            });
        $gonggao = SystemPosts::where('type', 1)
            ->where('locale', $request->header('locale'))
            ->where('display', 1)
            ->limit(10)
            ->orderBy('id', 'desc')
            ->get();
        $zixun = SystemPosts::where('type', 2)
            ->where('locale', $request->header('locale'))
            ->where('display', 1)
            ->limit(10)
            ->orderBy('id', 'desc')
            ->get();
        $neirong = array();
        $neirong['lunbo'] = $lunbo;
        $neirong['gonggao'] = $gonggao;
        $neirong['zixun'] = $zixun;
        return __return($this->successStatus, '获取成功', $neirong);
    }

    public function wenzhanglist(Request $request)
    {
        $content = SystemPosts::where('type', $request->type)
            ->where('locale', $request->header('locale'))
            ->where('display', 1)
            ->orderBy('id', 'desc')
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $content);
    }

    //手机区号，未使用
    public function quhao(Request $request)
    {
        $content = DB::table('system_value')
            ->where('name', 'quhao')
            ->where('locale', $request->header('locale'))
            ->orderBy('id', 'asc')
            ->paginate(10);
        return __return($this->successStatus, '获取成功', $content);
    }

    //获取邮箱地址和在线客服链接
    public function emailAddress(Request $request)
    {
        $data['emal_address'] = config('site.emal_address');
        $data['online_service'] = config('site.online_service');
        $data['sk'] = config('site.sk');
        $data['tg'] = config('site.tg');
        $data['emal_address1'] = config('site.emal_address1');
        $data['kefu'] = config('site.kefu');
        return __return($this->successStatus, '获取成功', $data);
    }

}