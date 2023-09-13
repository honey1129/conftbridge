<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SystemPostsTypes
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPostsTypes newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPostsTypes newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemPostsTypes query()
 * @mixin \Eloquent
 */
class SystemPostsTypes extends Model
{
    protected $title = '系统发布的公告类型';
	
    protected $table = 'system_posts_types';
    protected $guarded = ['id'];
}
