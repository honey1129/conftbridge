<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SystemAgree
 *
 * @property int $id
 * @property string $title 标题
 * @property string $content 内容
 * @property string $locale 语言 1中文 2英文
 * @property int $type 协议类型 见配置system.php
 * @property int|null $state 状态  0不显示 1显示
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SystemAgree whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SystemAgree extends Model
{
    protected $title = '平台协议';

    protected $table = 'system_agree';
    protected $guarded = ['id'];
}
