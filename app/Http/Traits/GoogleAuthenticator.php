<?php
namespace App\Http\Traits;

use Google;
use QrCode;

trait GoogleAuthenticator
{
    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */

    public static function CheckCode($secret,$oneCode)
    {
        $checkResult = Google::CheckCode($secret,$oneCode);//对传入的参数进行校验
        if ($checkResult) return true;//校验成功
        return false;//校验失败

    }

    public static function doCreateSecret()
    {
        $array = Google::CreateSecret();//创建一个Secret
//        $qrCodeUrl="otpauth://totp/".config("google.authenticatorname")."?secret=".$array['secret'];//二维码中填充的内容
        $qrCodeUrl="otpauth://totp/".config("system.VerifyCodeSign")."?secret=".$array['secret']."&issuuer=google";
        $qrcode = QrCode::encoding('UTF-8')->format('png')->size(200)->margin(1)
            ->generate($qrCodeUrl);
        $codeurl = 'data:image/png;base64,'.base64_encode($qrcode);
        $googlesecret = array('secret' => $array['secret'] ,'codeurl' => $codeurl);
        return $googlesecret;
    }

}
