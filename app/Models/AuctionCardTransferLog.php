<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionCardTransferLog extends Model
{
    protected $title = '加速卡转账记录';

    protected $table = 'auction_card_transfer_logs';
    protected $guarded = ['id'];
}