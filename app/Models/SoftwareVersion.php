<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SoftwareVersion
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $content
 * @property int $type 1Android 2IOS
 * @property int $uptype 1强制更新   2不强制更新
 * @property string|null $address 下载地址
 * @property string|null $vercode 版本号
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereUptype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SoftwareVersion whereVercode($value)
 * @mixin \Eloquent
 */
class SoftwareVersion extends Model
{
    protected $guarded = ['id'];
}
