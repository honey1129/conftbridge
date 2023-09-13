<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\FifteenMinInfo
 *
 * @property int $id
 * @property int|null $pid 商品iD
 * @property string|null $code 股票标识
 * @property string|null $name 股票名称
 * @property string|null $openingPrice 开盘价
 * @property string|null $closingPrice 收盘价
 * @property string|null $highestPrice 最高价
 * @property string|null $lowestPrice 最低价
 * @property string|null $volume 成交量
 * @property string|null $date 日期
 * @property string|null $time 时间
 * @property string|null $dateTime 日期时间
 * @property int|null $timestamp 时间戳
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereClosingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereHighestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereLowestPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereOpeningPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FifteenMinInfo whereVolume($value)
 * @mixin \Eloquent
 */
class FifteenMinInfo extends Model {

	protected $title = '15分钟数据';
    public $timestamps = false;
    protected $table = 'xy_15min_info';
    protected $guarded = ['id'];


}