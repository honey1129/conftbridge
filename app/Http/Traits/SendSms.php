<?php
namespace App\Http\Traits;

use App\Models\SmsLog;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
trait SendSms {

    /**
     * 发送短信
     * @param $phone
     * @param $account
     * @param $ip
     * @return array
     */
    public function doSendSms($phone,$ip,$area_code)
    {
        if (!isset($phone)) {
            return ['code' => 500, 'msg' => '手机号不能为空'];
        };
        $sms_log = SmsLog::where('phone',$phone)
            ->where('area_code',$area_code)
            ->where('used',0)
            ->orderBy('id','desc')
            ->first();
        if($sms_log){
            $cha = time()-strtotime($sms_log->created_at);
            if($cha<=60){
                return  [
                    'code' => 500,
                    'msg' => '一分钟内只能发送一条',
                ];
            }
        }
        $code = mt_rand(100000, 999999);
        try {
            $result = self::smsChinese($phone,$code,$area_code);
            if($result['returnstatus']=='Success'){
                $sms_log = new SmsLog;
                $sms_log->area_code = $area_code;
                $sms_log->phone = $phone;
                $sms_log->code = $code;
                $sms_log->content = '成功';
                $sms_log->ip = $ip;
                $sms_log->result ='成功';
                $sms_log->save();
                return ['code' => 200,'msg' =>'发送成功'];
            } else {
                return ['code' => 500,'msg' =>'发送失败'];
            }
        } catch (\Exception $e) {
            return ['code' => 500,'msg' => $e->getMessage().$e->getLine()];
        }
    }
    private static function smsChinese($mobile,$code,$area_code)
    {
        $mobile = $mobile[0]==0?substr($mobile,1):$mobile;
        if($area_code=='86'){//中国
            $account = 'ZZHN00243';
            $password =  strtoupper(md5('ZZHN0024343'));
            $msg= '【和氏通商城】您好，短信验证码为：'.$code.'，如非本人操作，请忽略此短信。';
            $sendurl = "https://dx.ipyy.net/smsJson.aspx?action=send&userid=61460&account=".$account."&password=".$password."&mobile=".$mobile."&content=".$msg;
            $duanxin = self::getHTTPS($sendurl);
            $duanxin = json_decode($duanxin,true);
            return $duanxin;
        }elseif($area_code=='886'){//台湾
            $mobile = $area_code.$mobile;
            $account = 'ZZ00821';
            $password =  strtoupper(md5('bsnvhm'));
            $msg= '【Nibiru】您好，簡訊驗證碼為：'.$code.'，如非本人操作，請忽略此簡訊。';
            $msg=strtoupper(bin2hex(iconv('utf-8','UCS-2BE',$msg)));
            $sendurl = "https://dx.ipyy.net/I18Nsms.aspx?action=send&userid=66001&account=".$account."&password=".$password."&mobile=".$mobile."&code=8&content=".$msg."&sendTime=&extno=";
        }elseif($area_code=='60'){//马来西亚
            $mobile = $area_code.$mobile;
            $account = 'ZZ00821';
            $password =  strtoupper(md5('bsnvhm'));
            $msg= '[Nibiru]Helo, kod pengesahan SMS adalah: '.$code.'.';
            $msg=strtoupper(bin2hex(iconv('utf-8','UCS-2BE',$msg)));
            $sendurl = "https://dx.ipyy.net/I18Nsms.aspx?action=send&userid=66001&account=".$account."&password=".$password."&mobile=".$mobile."&code=8&content=".$msg."&sendTime=&extno=";
        }else{//英语
            $mobile = $area_code.$mobile;
            $account = 'ZZ00821';
            $password =  strtoupper(md5('bsnvhm'));
            $msg= '[Nibiru] Hello, the SMS verification code is: '.$code.'.';
            $msg=strtoupper(bin2hex(iconv('utf-8','UCS-2BE',$msg)));
            $sendurl = "https://dx.ipyy.net/I18Nsms.aspx?action=send&userid=66001&account=".$account."&password=".$password."&mobile=".$mobile."&code=8&content=".$msg."&sendTime=&extno=";
        }
        $duanxin = self::getHTTPS($sendurl);
        $jieguo = json_decode(json_encode(simplexml_load_string($duanxin,'SimpleXMLElement',LIBXML_NOCDATA)),true);
        return $jieguo;
    }
    private static function getHTTPS($url){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_REFERER, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
    }
    /**
     * 检测短信验证码
     * @param $phone
     * @param $code
     * @return array
     */
    public function checkSmsCode($phone,$code)
    {
        $jieguo = DB::table('users')->where(['phone'=>$phone])->first();
        if($jieguo&&$jieguo->type==2){
            return  [
                'code' => 200,
                'msg' => '验证成功',
            ];
        }
        $sms_log = SmsLog::where('phone', $phone)
            ->where('code', $code)
            ->first();
        if (!$sms_log) {
            return  [
                'code' => 500,
                'msg' => '验证码错误',
            ];
        }
        if ($sms_log->used == 1) {
            return  [
                'code' => 500,
                'msg' => '短信验证码已失效, 请重新获取',
            ];
        }
        if (Carbon::now()->modify('-5 minutes')->gt($sms_log->created_at)) {
            return  [
                'code' => 500,
                'msg' => '短信验证码已过期,五分钟内有效,请重新获取',
            ];
        }
        // $sms_log->used = 1;
        // $sms_log->save();
        return  [
            'code' => 200,
            'msg' => '验证成功',
        ];
    }

}
