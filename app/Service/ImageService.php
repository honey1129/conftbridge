<?php

namespace App\Service;

//use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManagerStatic as Image;

class ImageService
{
    public static $disk = 'public';

    //后台图片
    public static function fullUrl($path)
    {
        $url = rtrim(Env('IMG_URL'), '/') . '/' . ltrim($path, '/');
        return $url;
    }

    public static function appUrl($path)
    {
        $url = rtrim(Env('APP_URL'), '/') . '/' . ltrim($path, '/');
        return $url;
    }

    /**
     * 生成邀请海报
     * @param $fileName string 邀请海报存储url
     * @param $qrcode string 邀请维码
     * @param $poster string 背景图
     */
    public static function inviteImg($fileName, $qrcode, $poster)
    {
        $image     = Image::make($poster)->resize(1024, 1920);
        $save_path = \Storage::disk(self::$disk)->path($fileName);
        $image->insert($qrcode,'bottom-left', 85, 113);
        $image->save($save_path);
        //写入图片
        \Storage::disk(self::$disk)->put($fileName, $image);
        return $fileName;
    }
}