<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuctionCard extends Model
{
    protected $title = '加速卡拍卖';

    protected $table = 'auction_card';
    protected $guarded = ['id'];
}