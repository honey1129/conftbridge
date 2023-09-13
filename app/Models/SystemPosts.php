<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SystemPosts
 *
 * @property int $id
 * @property string $title 标题
 * @property string|null $jianjie 简介
 * @property string $content 内容
 * @property string|null $image 图片
 * @property string $locale 语言 1中文 2英文
 * @property int $type 1公告 2咨询
 * @property int $is_zd 是否置顶
 * @property int $display 1开启 2关闭
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereIsZd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereJianjie($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPosts whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SystemPosts extends Model
{
    protected $title = '平台公告';

    protected $table = 'system_posts';
    protected $guarded = ['id'];
}
