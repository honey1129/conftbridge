<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\MinInfo
 *
 * @property int $id
 * @property int|null $pid 商品ID
 * @property string|null $code 股票id
 * @property string|null $name 股票类型
 * @property string|null $openingPrice 开盘价
 * @property string|null $closingPrice 收盘价
 * @property string|null $highestPrice 最高价
 * @property string|null $lowestPrice 最低价
 * @property string|null $volume 成交量
 * @property string|null $date 日期
 * @property string|null $time 时间
 * @property string|null $dateTime 日期时间
 * @property int|null $timestamp 时间戳
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereClosingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereHighestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereLowestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereOpeningPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MinInfo whereVolume($value)
 * @mixin \Eloquent
 */
class MinInfo extends Model {

	protected $title = '1分钟数据';
    public $timestamps = false;
    protected $table = 'xy_1min_info';
    protected $guarded = ['id'];


}