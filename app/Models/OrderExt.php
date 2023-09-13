<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\WriteUserMoneyLog;

/**
 * App\Models\OrderExt
 *
 * @property int $id
 * @property int|null $o_oid
 * @property int|null $c_oid
 * @property string|null $b_cjnum
 * @property string|null $time
 * @property string|null $b_cjprice1
 * @property string|null $b_cjprice2
 * @property string|null $type
 * @property int|null $pid
 * @property string|null $fee
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereBCjnum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereBCjprice1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereBCjprice2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereCOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereOOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OrderExt whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrderExt extends Model
{
    use WriteUserMoneyLog;

    protected $title = '币币交易';

    public $timestamps = false;

    protected $table = 'xy_order_ext';

}
