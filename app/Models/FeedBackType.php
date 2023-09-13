<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FeedBackType
 *
 * @property int $id
 * @property string $type_name 类型名称
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBackType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBackType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBackType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBackType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBackType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBackType whereTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBackType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FeedBackType extends Model
{
    protected $title = '反馈类型';

    protected $table = 'feedback_types';
    protected $guarded = ['id'];
}
