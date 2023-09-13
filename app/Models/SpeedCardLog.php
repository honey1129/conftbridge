<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpeedCardLog extends Model
{
    protected $title = '加速卡日志';

    protected $table = 'auction_card_transfer_logs';
    protected $guarded = ['id'];


    public static function writeLog($fromUser, $toUser, $cardId)
    {
        static::create([
            'from_uid' => $fromUser->id,
            'to_uid'   => $toUser->id,
            'card_id'  => $cardId
        ]);
    }
}