<?php

use Illuminate\Support\Facades\Redis;

function __return($code, $msg = '获取成功', $data = null, $replace = [])
{
    $request = app('request');
    $locale = $request->header('locale', 'zh-CN');
    if ($msg) {
        $msg = __($locale . '.' . $msg, $replace, $locale);
    }

    if (empty($data)) {
        $data = [];
    }
    return [
        'code' => $code,
        'data' => $data,
        'msg'  => $msg
    ];
}

function __return1($code, $msg = '获取成功', $data = null, $replace = [])
{
    $request = app('request');
    $locale = $request->header('locale', 'zh-CN');
    $msg = __($locale . '.' . $msg, $replace, $locale);

    return [
        'code' => $code,
        'data' => $data,
        'msg'  => $msg
    ];
}

function __return2($code, $msg = '获取成功', $data = null)
{
    return [
        'code' => $code,
        'data' => $data,
        'msg'  => $msg
    ];
}

function acID()
{
    $autoID = mt_rand(1, 550000);
    $autoCharacter = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E");
    $len = 7 - ((int)log10($autoID) + 1);
    $i = 1;
    $numberID = mt_rand(1, 2) . mt_rand(1, 4);

    for ($i; $i <= $len - 1; $i++) {
        $numberID .= $autoCharacter[mt_rand(1, 13)];
    }
    $id = base_convert($numberID . "E" . $autoID, 16, 10); //--->这里因为autoid永远不可能为E所以使用E来分割保证不会重复
    $user = \App\User::where('account', $id)->first();
    if (empty($user)) {
        return $id;
    } else {
        acID();
    }
}

function getHTTPS($url)
{
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

function check_email($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    } else {
        return false;
    }
}

function write_login_log($uid, $ip)
{
    $agent = new \Jenssegers\Agent\Agent();
    \Illuminate\Support\Facades\DB::table('user_login_history')->insert([
        'uid'        => $uid,
        'log_ip'     => $ip,
        'agent_info' => $agent->device(),
        'status'     => 1,
        'created_at' => now(),
    ]);
}


function format_price($price, $code = 'usdt')
{
    $config = config('system.decimal_places');
    if (isset($config[$code])) {
        $fix = $config[$code];
    } else {
        $fix = 8;
    }

    return number_format($price, $fix, '.', '');

}

function curl_request($url, $post = '', $cookie = '', $returnCookie = 0)
{


    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
    if ($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if ($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);

    if (curl_errno($curl)) {
        return curl_error($curl);
    }

    curl_close($curl);

    if ($returnCookie) {
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie'] = substr($matches[1][0], 1);
        $info['content'] = $body;
        return $info;
    } else {
        return json_decode($data, true);
    }
}

// 随机编号
function get_rand_str($randLength = 6, $addtime = 0, $includenumber = 1)
{
    if ($includenumber) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQEST123456789';
    } else {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
    }
    $len = strlen($chars);
    $randStr = '';
    for ($i = 0; $i < $randLength; $i++) {
        $randStr .= $chars[rand(0, $len - 1)];
    }
    $tokenvalue = $randStr;
    if ($addtime) {
        $tokenvalue = $randStr . time();
    }
    return $tokenvalue;
}
function substr_cut($user_name)
{
    //获取字符串长度
    $strlen = strlen($user_name);
    //如果字符串长度小于3，不做任何处理
    if ($strlen < 3) {
        return $user_name;
    } else {
        $xLen = ceil($strlen * 0.3); // 5.1 => 6

        $lastLen = $strlen - $xLen; //10
        $startLen = intval($lastLen / 2); //5
        //mb_substr — 获取字符串的部分
        $firstStr = substr($user_name, 0, $startLen);
        $lastStr = substr($user_name, $startLen + $xLen);
        //str_repeat — 重复一个字符串
        return $firstStr . str_repeat('*', 4) . $lastStr;
    }

}

function curl_request1($url, $param = '', $httpMethod = 'GET')
{
    $ch = curl_init();
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    }
    if ($httpMethod == 'GET') {
        if (empty($param)) {
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if (stripos($url, "?") !== FALSE) {
                curl_setopt($ch, CURLOPT_URL, $url . "&" . http_build_query($param));
            } else {
                curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($param));
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    } else {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    $sContent = curl_exec($ch);
    $aStatus = curl_getinfo($ch);
    curl_close($ch);
    if (intval($aStatus["http_code"]) == 200) {
        return $sContent;
    } else {
        return FALSE;
    }
}

//有key禁点
function disablePoint($method, $user)
{
    $key = 'click:' . $method . ':' . $user->id;
    $result = Redis::get($key);
    if (!$result) {
        Redis::set($key, 1, 'ex', 2);
        return true;
    }
    // false 禁止点击
    return false;
}