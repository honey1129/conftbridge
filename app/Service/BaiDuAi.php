<?php

namespace App\Service;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
use Log;

class BaiDuAi
{
    public static function getAccessToken()
    {
        $url = 'https://aip.baidubce.com/oauth/2.0/token?client_id=HYB6AyG0lEpEAnx3gBjEpGot&client_secret=UsGOf8qtA8NbDSxIi0XcEwG5n4olEXae&grant_type=client_credentials';
        $client = new Client(['verify' => false]);
        $request = new \GuzzleHttp\Psr7\Request('POST', $url);
        $response = $client->send($request);
        $result = json_decode($response->getBody()->getContents(), true);
        //         {
//     "refresh_token": "25.d112c10abfc20363dbc47e348018b288.315360000.2002356800.282335-34950734",
//     "expires_in": 2592000,
//     "session_key": "9mzdWBP0uhCXQy3zmDSGNSR9vfyh9E1TKR0PuelV3nCz0V4acvAlpBz2aWPaL752biy2WhPgx+V96uELjwIMg/fRMu/kuQ==",
//     "access_token": "24.ccf88ca4eb5f1534a71187fbb1470b52.2592000.1689588800.282335-34950734",
//     "scope": "public brain_all_scope brain_ernievilg_txt2img brain_rpc_ernievilg_v2 wise_adapt lebo_resource_base lightservice_public hetu_basic lightcms_map_poi kaidian_kaidian ApsMisTest_Test权限 vis-classify_flower lpq_开放 cop_helloScope ApsMis_fangdi_permission smartapp_snsapi_base smartapp_mapp_dev_manage iop_autocar oauth_tp_app smartapp_smart_game_openapi oauth_sessionkey smartapp_swanid_verify smartapp_opensource_openapi smartapp_opensource_recapi fake_face_detect_开放Scope vis-ocr_虚拟人物助理 idl-video_虚拟人物助理 smartapp_component smartapp_search_plugin avatar_video_test b2b_tp_openapi b2b_tp_openapi_online smartapp_gov_aladin_to_xcx",
//     "session_secret": "20a4dea188aab038ae0d096ab03d2a51"
// }
        if ($result['access_token']) {
            Redis::set('baidu_access_token', $result['access_token'], 'ex', $result['expires_in']);
        }
        return $result['access_token'];
    }


    public static function getImageByText($content, $style)
    {
        $accessToken = Redis::get('baidu_access_token');
        if (!$accessToken) {
            $accessToken = static::getAccessToken();
        }
        $url = "https://aip.baidubce.com/rpc/2.0/ernievilg/v1/txt2img?access_token={$accessToken}";
        $client = new Client(['verify' => false]);
        $headers = [];
        $options = [
            'json' => [
                'text'       => $content,
                'resolution' => '1024*1024',
                'style'      => $style,
                'num'        => 1
            ]
        ];
        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers);
            $response = $client->send($request, $options);
            $result = json_decode($response->getBody()->getContents(), true);
            Log::info($result);
            if (isset($result['log_id'])) {
                $taskId = $result['data']['taskId'];
                $imageResult = '';
                while (!$imageResult) {
                    $url = "https://aip.baidubce.com/rpc/2.0/ernievilg/v1/getImg?access_token={$accessToken}";
                    $options = [
                        'json' => [
                            'taskId' => $taskId
                        ]
                    ];
                    $request = new \GuzzleHttp\Psr7\Request('POST', $url);
                    $response = $client->send($request, $options);
                    $result = json_decode($response->getBody()->getContents(), true);
                    if (isset($result['data'])) {
                        $imageResult = $result['data']['img'];
                    } else {
                        sleep(1);
                    }
                }
                return $imageResult;
            }
            Log::error($result);
            return false;
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    public function imageGetImage($image, $content)
    {
        $accessToken = Redis::get('baidu_access_token');
        if (!$accessToken) {
            $accessToken = static::getAccessToken();
        }
        $url = "https://aip.baidubce.com/rpc/2.0/ernievilg/v1/txt2imgv2?access_token={$accessToken}";
        $client = new Client(['verify' => false]);
        $headers = [];
        $options = [
            'json' => [
                'prompt'        => '',
                'width'         => '1024',
                'height'        => '1024',
                'style'         => '写实风格',
                'num'           => 1,
                'image'         => $image,
                'change_degree' => 10
            ]
        ];
        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers);
            $response = $client->send($request, $options);
            $result = json_decode($response->getBody()->getContents(), true);
            Log::info($result);
            if (isset($result['log_id'])) {
                $taskId = $result['data']['taskId'];
                $imageResult = '';
                while (!$imageResult) {
                    $url = "https://aip.baidubce.com/rpc/2.0/ernievilg/v1/getImg?access_token={$accessToken}";
                    $options = [
                        'json' => [
                            'taskId' => $taskId
                        ]
                    ];
                    $request = new \GuzzleHttp\Psr7\Request('POST', $url);
                    $response = $client->send($request, $options);
                    $result = json_decode($response->getBody()->getContents(), true);
                    if (isset($result['data'])) {
                        $imageResult = $result['data']['img'];
                    } else {
                        sleep(1);
                    }
                }
                return $imageResult;
            }
            Log::error($result);
            return false;
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }
}