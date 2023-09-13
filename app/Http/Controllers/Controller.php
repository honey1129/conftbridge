<?php

namespace App\Http\Controllers;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\AssetRelease;
use App\Models\UserAssets;
use App\Models\UserPosition;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, WriteUserMoneyLog;

    public $successStatus      = 200;
    public $unauthorizedStatus = 401;
    public $unsecurityStatus   = 405;
    public $errStatus          = 500;
    public $disk               = 'public';//照片驱动存储方式 和 filesystems.php 的配置对应

    public function upload($file, $disk = 'public')
    {
        // 1.是否上传成功
        if (!$file->isValid()) {
            return ['code' => 500, 'msg' => '上传失败'];
        }

        // 2.是否符合文件类型 getClientOriginalExtension 获得文件后缀名
        $fileExtension = $file->getClientOriginalExtension();
        if (!in_array($fileExtension, ['png', 'PNG', 'jpg', 'JPG'])) {
            return ['code' => 500, 'msg' => '格式不正确'];
        }
        // 3.判断大小是否符合 2M
        $tmpFile = $file->getRealPath();
        if (filesize($tmpFile) >= 4096000) {
            return ['code' => 500, 'msg' => '文件大小大于2M'];
        }
        // 4.是否是通过http请求表单提交的文件
        if (!is_uploaded_file($tmpFile)) {
            return ['code' => 500, 'msg' => '请求方式错误'];
        }
        // 5.每天一个文件夹,分开存储, 生成一个随机文件名
        $fileName = 'images/' . date('Y_m_d') . '/' . md5(time()) . mt_rand(0, 9999) . '.' . $fileExtension;
        if (Storage::disk($disk)->put($fileName, file_get_contents($tmpFile))) {
            return ['code' => 200, 'data' => $fileName];
        }
    }

    public function base64Upload($base64_img, $disk = 'public')
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)) {
            $type = $result[2];
            if (!in_array($type, array('pjpeg', 'jpeg', 'jpg', 'gif', 'bmp', 'png'))) {
                return ['code' => 500, 'msg' => '格式不正确'];
            }
            $fileName = 'images/' . date('Y_m_d') . '/' . md5(time()) . mt_rand(0, 9999) . '.' . $type;
            if (!Storage::exists('images/' . date('Y_m_d'))) {
                Storage::disk($disk)->makeDirectory('images/' . date('Y_m_d'));
            }
            if (Storage::disk($disk)->put($fileName, base64_decode(str_replace($result[1], '', $base64_img)))) {
                return ['code' => 200, 'data' => $fileName];
            } else {
                return ['code' => 500, 'msg' => '图片上传失败'];
            }
        } else {
            return ['code' => 500, 'msg' => '文件错误'];
        }
    }

    /**
     * 私钥解密
     * @param $password
     * @return array
     */
    public function privateDecrypt($password)
    {
        try {
            $private_key  = config('system.RSA.RSA_PRIVATE_KEY');
            $encrypt_data = base64_decode($password);
            openssl_private_decrypt($encrypt_data, $decrypt_data, $private_key);
            if (!$decrypt_data) {
                return ['code' => 500, 'msg' => '密码解析失败'];
            }
            return ['code' => 200, 'data' => $decrypt_data];
        } catch (\Exception $exception) {
            return ['code' => 500, 'msg' => '密码解析失败'];
        }
    }

    /**
     * 更新团队奖励
     * @param
     * @return array
     */
    public function updateTeamAward($uid, $oid, $money, $ptype)
    {
        try {
            $inster_arr = array();
            $zt_one     = config('award.zt_one');
            $zt_two     = config('award.zt_two');
            $zt_three   = config('award.zt_three');
            $zt_four    = config('award.zt_four');
            $zt_five    = config('award.zt_five');
            $user_list  = UserPosition::where('uid', $uid)->orderBy('lay')->get()->toArray();
            if ($user_list) {
                foreach ($user_list as $key => $val) {
                    $lay_count = UserPosition::where(['pid' => $val['pid'], 'lay' => 1])->count() + 0;
                    if ($lay_count == 1) {
                        $zt_rate = $zt_one;
                    } else if ($lay_count == 2) {
                        $zt_rate = $zt_two;
                    } else if ($lay_count == 3) {
                        $zt_rate = $zt_three;
                    } else if ($lay_count == 4) {
                        $zt_rate = $zt_four;
                    } else {
                        $zt_rate = $zt_five;
                    }
                    $awar_money = number_format($money * $zt_rate * 0.01, '6', '.', '');
                    if ($ptype == 3) {
                        $memo       = 2;
                        $en_memo    = 2;
                        $type       = '39';
                        $order_type = '2';
                    }
                    if ($ptype == 4) {
                        $memo       = 3;
                        $en_memo    = 3;
                        $type       = '47';
                        $order_type = '3';
                    }
                    $inster_arr[] = [
                        'uid'        => $val['pid'],
                        'order_no'   => 'JL' . md5(md5(time() . $val['pid'] . mt_rand(1000, 9999))),
                        'order_type' => $order_type,
                        'money'      => $awar_money,
                        'status'     => 0,
                        'memo'       => $memo,
                        'en_memo'    => $en_memo,
                        'pid'        => 8,
                        'oid'        => $oid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $asset        = UserAssets::getBalance($val['pid'], 8, $ptype);
                    $this->writeBalanceLog($asset, 0, $awar_money, $type, $memo, $en_memo, 8, 'USDT', $ptype);
                }
            }
            if ($inster_arr) {
                AssetRelease::insert($inster_arr);
            }
            return ['code' => 200, 'data' => array()];
        } catch (\Exception $exception) {
            return ['code' => 500, 'msg' => '失败'];
        }
    }
}
