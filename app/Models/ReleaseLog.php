<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ReleaseLog
 *
 * @property int $id
 * @property int $status 1静态释放 2动态收益 3绩差收益 4平级收益 5质押算力分红
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReleaseLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReleaseLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReleaseLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReleaseLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReleaseLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReleaseLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ReleaseLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReleaseLog extends Model
{
    protected $title = '释放记录';

    protected $table = 'release_log';
    protected $guarded = ['id'];
}
