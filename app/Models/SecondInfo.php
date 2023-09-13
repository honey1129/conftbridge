<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\SecondInfo
 *
 * @property int $id
 * @property int|null $pid 商品id
 * @property string|null $code 商品代码
 * @property string|null $pname 商品名称
 * @property float|null $price 实时价格
 * @property float|null $volume 交易量
 * @property int|null $addtime 添加时间
 * @property string|null $ctime 当前日期
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo whereAddtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo whereCtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo wherePname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SecondInfo whereVolume($value)
 * @mixin \Eloquent
 */
class SecondInfo extends Model {

	protected $title = '交易实时数据';
    public $timestamps = false;
    protected $table = 'xy_second_info_token';
    protected $guarded = ['id'];


}