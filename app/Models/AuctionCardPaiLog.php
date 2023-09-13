<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionCardPaiLog extends Model
{
    protected $title = '加速卡拍卖记录';

    protected $table = 'auction_card_pai_logs';
    protected $guarded = ['id'];
}