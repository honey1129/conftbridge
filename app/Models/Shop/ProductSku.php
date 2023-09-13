<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;

class ProductSku extends Model
{
    protected $connection = 'db_shop';
    protected $table = 'product_skus';
    public function decreaseStock($amount)
    {
        if ($amount < 0) {
            return __return($this->errStatus,'减库存不可小于0');
        }

        return $this->where('id', $this->id)->where('stock', '>=', $amount)->decrement('stock', $amount);
    }
}
