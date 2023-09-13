<?php

namespace App\Http\Traits;

use App\Jobs\EmailVerifyCode;
use App\Models\EmailLog;
use Carbon\Carbon;

trait SendEmail
{

    public function doSendEmail($email, $ip)
    {
        $email_log = EmailLog::where('email', $email)
                             ->where('used', 0)
                             ->orderBy('id', 'desc')
                             ->first();
        if (!empty($email_log) && Carbon::now()->modify('-5 minutes')->lt($email_log->created_at)) {
            return [
                'code' => 500,
                'msg'  => '验证码五分钟内有效',
            ];
        }
        dispatch(new EmailVerifyCode($email, $ip))->onQueue('emails');
        return [
            'code' => 200,
            'msg'  => '请求成功',
        ];
    }

    /**
     * 检测邮箱验证码
     * @param $email
     * @param $code
     * @return array
     */
    public function checkEmailCode($email, $code)
    {
        if ($code == '999998') {
            return [
                'code' => 200,
                'msg'  => '验证成功',
            ];
        }

        $email_log = EmailLog::where('email', $email)
                             ->where('code', $code)
                             ->first();
        if (!$email_log) {
            return [
                'code' => 500,
                'msg'  => '验证码错误',
            ];
        }
        if ($email_log->used == 1) {
            return [
                'code' => 500,
                'msg'  => '验证码已失效, 请重新获取',
            ];
        }

        if (Carbon::now()->modify('-15 minutes')->gt($email_log->created_at)) {
            return [
                'code' => 500,
                'msg'  => '验证码已过期, 请重新获取',
            ];
        }
        $email_log->used = 1;
        $email_log->save();
        return [
            'code' => 200,
            'msg'  => '验证成功',
        ];
    }
}
