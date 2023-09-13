<?php

namespace App;

use App\Models\Fbpay;
use App\Models\UserAssets;
use App\Models\UserConfig;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * App\User
 *
 * @property int $id
 * @property string $account 用户账号\邀请码
 * @property string $area_code 区域代码
 * @property string $phone 用户手机号
 * @property string $email 邮箱
 * @property string|null $name 姓名
 * @property string|null $avatar 用户头像
 * @property string $password 登陆密码
 * @property string|null $payment_password 资金密码
 * @property string $shoushiword
 * @property int $recommend_id 推荐人ID
 * @property int $jiantui_id 间推id
 * @property string|null $relationship 推荐关系-客户
 * @property int $deep 用户深度
 * @property int $authentication 认证状态0未认证1初级认证2高级认证待审核3高级认证通过4高级认证拒绝
 * @property int|null $stoped 用户状态 0待激活 1已激活
 * @property int|null $level 会员级别 1普通会员 2节点会员
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $nickname
 * @property float $total_usdt 团队总投资金额
 * @property int $is_robot 是否是机器人0否 1是
 * @property int $down 涨跌标准 0不涨不跌 1跌 2涨
 * @property int|null $is_deposit 是否允许提币 0否 1是
 * @property int|null $is_transfer 是否允许转账 0否 1是
 * @property string|null $invite_code
 * @property int $type  1账户  2操盘账号 3空点位账号 4水号
 * @property-read \App\User $agent
 * @property-read \App\Models\UserAssets|null $assets
 * @property-read \App\User $center
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read int|null $clients_count
 * @property-read \App\Models\UserConfig|null $config
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fbpay[] $pay
 * @property-read int|null $pay_count
 * @property-read \App\User $recommend
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $recommends
 * @property-read int|null $recommends_count
 * @property-read \App\User $staff
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 * @property-read int|null $tokens_count
 * @property-read \App\User $unit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereAreaCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereAuthentication($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereDeep($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereDown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereInviteCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereIsDeposit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereIsRobot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereIsTransfer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereJiantuiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePaymentPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereRecommendId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereShoushiword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereStoped($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereTotalUsdt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    const DEFAULT_USER_STATUS = 0; //正常用户
    const DISABLED_USER_STATUS = 1; //被禁用用户

    protected $guarded = ['id'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'account',
    //     'phone',
    //     'email',
    //     'password',
    //     'payment_password',
    //     'recommend_id',
    //     'relationship',
    //     'deep',
    //     'staff_id',
    //     'agent_id',
    //     'unit_id',
    //     'center_id',
    //     'total_gongmu',
    //     'has_gongmu',
    //     'nickname',
    //     'avatar',
    //     'name',
    //     'stoped',
    //     'shoushiword',
    //     'type',
    //     'shijian'
    // ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'payment_password',
        'recommend_id',
        'relationship',
        'deep'
    ];
    public function config()
    {
        return $this->hasOne(UserConfig::class, 'id', 'uid');
    }
    public function assets()
    {
        return $this->hasOne(UserAssets::class, 'id', 'uid');
    }
    public function recommend()
    {
        return $this->belongsTo(User::class, 'recommend_id', 'id');
    }

    public function recommends()
    {
        return $this->hasMany(User::class, 'id', 'recommend_id');
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id', 'id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(User::class, 'unit_id', 'id');
    }

    public function center()
    {
        return $this->belongsTo(User::class, 'center_id', 'id');
    }

    public function pay()
    {
        return $this->hasMany(Fbpay::class, 'uid', 'id');
    }

    public function getEmailAttribute($val)
    {
        if (!$val) {
            return '';
        } else {
            return $val;
        }
    }

    public function getPhoneAttribute($val)
    {
        if (!$val) {
            return '';
        } else {
            return $val;
        }
    }




}