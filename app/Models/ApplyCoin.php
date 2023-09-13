<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ApplyCoin
 *
 * @property int $id
 * @property string $code 代币名称
 * @property string|null $image
 * @property string $fxunit 发行单位名称
 * @property int $type 发行类型
 * @property float $fxnum 发行数量
 * @property float $fxprice 发行价格
 * @property float $soldnum 已售数量
 * @property string $fxweb 发行网站
 * @property string $fxbook 白皮书地址
 * @property string|null $memo 描述
 * @property string $fxtime 发行时间
 * @property string $endtime 结束时间
 * @property int $status 1进行中 2已发布 3被拒绝
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $en_memo
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereEnMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereEndtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereFxbook($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereFxnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereFxprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereFxtime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereFxunit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereFxweb($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereSoldnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ApplyCoin whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ApplyCoin extends Model {

	protected $title = '私募列表';
    protected $table = 'apply_coin';
    protected $guarded = ['id'];


}