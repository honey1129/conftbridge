<?php

namespace App\Models;

use App\Service\ImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

/**
 * App\Models\WalletCode
 *
 * @property int $id
 * @property string|null $icon 图标
 * @property string $code 币种标识
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $pid
 * @property int $is_show 是否显示
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode whereIsShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WalletCode whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WalletCode extends Model
{
    //
    protected $table = 'wallet_code';

    public static function codeOption()
    {
        return self::where('pid', '>', 0)->pluck('code', 'pid');
    }

    public static function getCode($pid)
    {
        $cache_kek = 'ModelWalletCode:getCode' . $pid;
        $model     = Redis::get($cache_kek);
        if (empty($model)) {
            $model = self::where('pid', $pid)->value('code');
            Redis::setex($cache_kek, 300, $model);
        }
        return $model;
    }

    public static function codeGetPid($code)
    {
        $cache_kek = 'ModelWalletCode:codeGetPid' . $code;
        $model     = Redis::get($cache_kek);
        if (empty($model)) {
            $model = self::where('code', strtoupper($code))->value('pid');
            Redis::setex($cache_kek, 300, $model);
        }
        return $model;
    }

    public static function getImage($pid)
    {
        $icon = self::where('pid', $pid)->value('icon');
        return ImageService::fullUrl($icon);
    }


}
