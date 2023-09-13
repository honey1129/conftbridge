<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Chain
 *
 * @property int $id
 * @property string $chain 充值网络
 * @property int $type 网络类型
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain whereChain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Chain whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Chain extends Model
{
    protected $table = 'chain';

    /**
     * 交易所主网ID获取type
     * @param $chain_id
     */
    public static function getType($chain_id)
    {
        return self::where('id', $chain_id)->value('type');
    }

    /**
     * 交易所主网ID获取type
     * @param $chain_id
     */
    public static function getChain($chain_id)
    {
        return self::where('id', $chain_id)->value('chain');
    }


}
