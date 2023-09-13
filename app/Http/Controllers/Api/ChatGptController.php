<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\GptInfo;
use App\Models\UserAddress;
use App\Models\UserAssets;
use App\Models\UserChat;
use App\Models\UserNft;
use App\Service\BaiDuAi;
use App\Service\ImageService;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Storage;
use Log;
use Hash;
use App\Http\Traits\WriteUserMoneyLog;
use Redis;
use Intervention\Image\ImageManagerStatic as Image;

class ChatGptController extends Controller
{
    use WriteUserMoneyLog;




    // 百度生成圖片
    public function gpt1(Request $request)
    {
        $user = $request->user;

        $image = $request->input('image', '');
        if (empty($image)) {
            return __return($this->errStatus, '参数错误');
        }

        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        $needPiao = config('site.ai_fee');
        $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1);
        if ($userPiaoAsset->balance < $needPiao) {
            return __return($this->errStatus, '票余额不足');
        }

        $imageArrs = explode('.', $image);
        $extension = $imageArrs[1];

        if ($extension != 'png') {
            return __return($this->errStatus, '请上传png图片');
        }

        $imageUrl = 'https://' . config('filesystems.disks.oss.cdnDomain');
        $fileResource = file_get_contents($imageUrl . '/' . $image);

        $client = new Client(['verify' => false]);
        $headers = [
            'Authorization' => 'Bearer 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'
        ];
        $options = [
            'multipart' => [
                [
                    'name'     => 'image',
                    'contents' => $fileResource,
                    'filename' => $image
                ],
                [
                    'name'     => 'n',
                    'contents' => '1'
                ],
                [
                    'name'     => 'size',
                    'contents' => '1024x1024'
                ]
            ]
        ];
        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', 'https://api.openai.com/v1/images/variations', $headers);
            $response = $client->send($request, $options);
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['data'])) {
                $image = $result['data'][0]['url'];
                $fileName = 'images/' . date('Y_m_d') . '/' . md5(time()) . mt_rand(0, 9999) . '.' . $extension;
                GptInfo::create([
                    'userId'       => $user->id,
                    'userName'     => $user->email,
                    'userTime'     => date('Y-m-d H:i:s'),
                    'userQuestion' => $image,
                    'gptAnswer'    => json_encode($result),
                    'questionType' => 'image'
                ]);
                if (Storage::disk('oss')->put($fileName, file_get_contents($image))) {
                    UserNft::create([
                        'uid'   => $user->id,
                        'image' => $fileName,
                    ]);
                    DB::beginTransaction();
                    $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                    $this->writeBalanceLog($userPiaoAsset, 0, -$needPiao, 19, 'AI生成图片', 'AI生成图片', $userPiaoAsset->pid, $userPiaoAsset->pname);
                    DB::commit();
                    // return ['code' => 200, 'data' => $fileName];
                    return __return($this->successStatus, '操作成功', ['image' => $fileName]);
                } else {
                    return __return($this->errStatus, '操作失败');
                }
            } else {
                return __return($this->errStatus, '操作失败');
            }
        } catch (ClientException $e) {
            Log::error($e->getMessage());
            $message = $e->getMessage();
            $errMessage = json_decode($message)->message;
            return __return($this->errStatus, $errMessage);
        }

    }



    // chatgpt
    public function gpt(Request $request)
    {
        $user = $request->user;

        $image = $request->input('image', '');
        if (empty($image)) {
            return __return($this->errStatus, '请上传图片');
        }

        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        $needPiao = config('site.ai_fee');
        $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1);
        if ($userPiaoAsset->balance < $needPiao) {
            return __return($this->errStatus, '票余额不足');
        }

        $imageArrs = explode('.', $image);
        $extension = $imageArrs[1];

        if ($extension != 'png') {
            return __return($this->errStatus, '请上传png图片');
        }

        $imageUrl = 'https://' . config('filesystems.disks.oss.cdnDomain');
        $fileResource = file_get_contents($imageUrl . '/' . $image);

        $client = new Client(['verify' => false]);
        $headers = [
            'Authorization' => 'Bearer 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'
        ];
        $options = [
            'multipart' => [
                [
                    'name'     => 'image',
                    'contents' => $fileResource,
                    'filename' => $image
                ],
                [
                    'name'     => 'n',
                    'contents' => '1'
                ],
                [
                    'name'     => 'size',
                    'contents' => '1024x1024'
                ]
            ]
        ];
        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', 'https://api.openai.com/v1/images/variations', $headers);
            $response = $client->send($request, $options);
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['data'])) {
                $image = $result['data'][0]['url'];
                $fileName = 'images/' . date('Y_m_d') . '/' . md5(time()) . mt_rand(0, 9999) . '.' . $extension;
                GptInfo::create([
                    'userId'       => $user->id,
                    'userName'     => $user->email,
                    'userTime'     => date('Y-m-d H:i:s'),
                    'userQuestion' => $image,
                    'gptAnswer'    => json_encode($result),
                    'questionType' => 'image'
                ]);
                if (Storage::disk('oss')->put($fileName, file_get_contents($image))) {
                    UserNft::create([
                        'uid'   => $user->id,
                        'image' => $fileName,
                    ]);
                    DB::beginTransaction();
                    $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                    $this->writeBalanceLog($userPiaoAsset, 0, -$needPiao, 19, 'AI生成图片', 'AI生成图片', $userPiaoAsset->pid, $userPiaoAsset->pname);
                    DB::commit();
                    // return ['code' => 200, 'data' => $fileName];
                    return __return($this->successStatus, '操作成功', ['image' => $fileName]);
                } else {
                    return __return($this->errStatus, '操作失败');
                }
            } else {
                return __return($this->errStatus, '操作失败');
            }
        } catch (ClientException $e) {
            Log::error($e->getMessage());
            $message = $e->getMessage();
            $errMessage = json_decode($message)->message;
            return __return($this->errStatus, $errMessage);
        }

    }


    // 百度
    public function strGptGetImage(Request $request)
    {
        $user = $request->user;

        $content = $request->input('content', '');
        Log::info($content);
        $style = $request->input('style', '二次元');
        Log::info($style);
        if (empty($content)) {
            return __return($this->errStatus, '内容不能为空');
        }


        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }
        $needPiao = config('site.ai_fee');
        $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1);
        if ($userPiaoAsset->balance < $needPiao) {
            return __return($this->errStatus, '票余额不足');
        }


        try {
            $image = BaiDuAi::getImageByText($content, $style);
            if ($image) {
                $fileName = 'images/' . date('Y_m_d') . '/' . md5(time()) . mt_rand(0, 9999) . '.png';
                GptInfo::create([
                    'userId'       => $user->id,
                    'userName'     => $user->email,
                    'userTime'     => date('Y-m-d H:i:s'),
                    'userQuestion' => $content,
                    'gptAnswer'    => $image,
                    'questionType' => '字符串获取图片'
                ]);
                if (Storage::disk('oss')->put($fileName, file_get_contents($image))) {
                    UserNft::create([
                        'uid'   => $user->id,
                        'image' => $fileName,
                    ]);
                    DB::beginTransaction();
                    $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                    $this->writeBalanceLog($userPiaoAsset, 0, -$needPiao, 19, 'AI生成图片', 'AI生成图片', $userPiaoAsset->pid, $userPiaoAsset->pname);
                    DB::commit();
                    // return ['code' => 200, 'data' => $fileName];
                    return __return($this->successStatus, '操作成功', ['image' => $fileName]);
                } else {
                    return __return($this->errStatus, '操作失败');
                }
            } else {
                return __return($this->errStatus, '操作失败');
            }
        } catch (ClientException $e) {
            Log::error($e->getMessage());
            $message = $e->getMessage();
            $errMessage = json_decode($message)->message;
            return __return($this->errStatus, $errMessage);
        }
    }


    public function chat(Request $request)
    {
        $user = $request->user;

        $content = $request->input('content', '');
        if (empty($content)) {
            return __return($this->errStatus, '内容不能为空');
        }

        $needPiao = config('site.ai_chat_fee');
        $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1);
        if ($userPiaoAsset->balance < $needPiao) {
            return __return($this->errStatus, '票余额不足');
        }


        $client = new Client(['verify' => false]);
        $headers = [
            'Authorization' => 'Bearer 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'
        ];
        $options = [
            'json' => [
                'model'    => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => $content]]
            ]
        ];

        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', 'https://api.openai.com/v1/chat/completions', $headers);
            $response = $client->send($request, $options);
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['choices'])) {
                $message = $result['choices'][0]['message']['content'];
                GptInfo::create([
                    'userId'       => $user->id,
                    'userName'     => $user->email,
                    'userTime'     => date('Y-m-d H:i:s'),
                    'userQuestion' => $content,
                    'gptAnswer'    => json_encode($result),
                    'questionType' => '聊天'
                ]);

                UserChat::create([
                    'uid'           => $user->id,
                    'content'       => $content,
                    'reply_content' => $message,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);


                DB::beginTransaction();
                $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                $this->writeBalanceLog($userPiaoAsset, 0, -$needPiao, 19, 'AI聊天', 'AI聊天', $userPiaoAsset->pid, $userPiaoAsset->pname);
                DB::commit();
                // return ['code' => 200, 'data' => $fileName];
                return __return($this->successStatus, '操作成功', ['message' => $message]);

            } else {
                return __return($this->errStatus, '操作失败');
            }
        } catch (ClientException $e) {
            Log::error($e->getMessage());
            $message = $e->getMessage();
            $errMessage = json_decode($message)->message;
            return __return($this->errStatus, $errMessage);
        }
    }

    public function chatList(Request $request)
    {
        $user = $request->user;

        $userChats = UserChat::where(['uid' => $user->id])->orderBy('id', 'desc')->limit(10)->get();

        return __return($this->successStatus, '获取成功', $userChats);
    }

}