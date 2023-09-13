<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionYuLog extends Model
{
    protected $title = '加速卡拍卖预约记录';

    protected $table = 'auction_card_yu_logs';
    protected $guarded = ['id'];
}