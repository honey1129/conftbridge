<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Slides
 *
 * @property int $id
 * @property string $image 图片
 * @property string|null $href 跳转链接
 * @property string $locale 语言
 * @property int $position 位置 1首页2公告
 * @property int $type 1为APP 2为PC
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides whereHref($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Slides whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Slides extends Model
{
    //
}
