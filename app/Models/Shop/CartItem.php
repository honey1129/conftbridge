<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use App\User;
class CartItem extends Model
{
    protected $connection = 'db_shop';
    protected $fillable = ['amount'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productSku()
    {
        return $this->belongsTo(ProductSku::class);
    }
}
