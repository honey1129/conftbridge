<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FeedBack
 *
 * @property int $id
 * @property int $uid 用户id
 * @property int|null $type_id 问题类型Id
 * @property string|null $desc 反馈内容
 * @property string|null $reply 平台回复
 * @property string|null $user_tel 用户联系方式
 * @property string|null $reply_at 平台回复时间
 * @property int|null $reply_status 回复状态 0：未回复   1：已回复
 * @property string|null $file 文件
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FeedBackType|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereReply($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereReplyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereReplyStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FeedBack whereUserTel($value)
 * @mixin \Eloquent
 */
class FeedBack extends Model
{
    protected $title = '反馈列表';

    protected $table = 'feedbacks';
    protected $guarded = ['id'];

    public function type(){
        return $this->belongsTo(FeedBackType::class,'type_id','id');
    }
}
