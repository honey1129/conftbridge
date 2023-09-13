<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ChainNetwork
 *
 * @property int $id
 * @property string|null $name 网络名称
 * @property int $state 状态
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $deleted_at
 * @property string $type 网络类型/配合钱包
 * @property string $en_name
 * @property string|null $color 网络颜色
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereEnName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChainNetwork whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ChainNetwork extends Model
{
    //
    protected $table      = 'chain_network';
    protected $dateFormat = 'U';

    public static function getName($id)
    {
        return self::where('id', $id)->value('name');
    }

    public static function getEnName($id)
    {
        return self::where('id', $id)->value('en_name');
    }
    public static function typeGetID($type)
    {
        return self::where('type', $type)->value('id');
    }
}
