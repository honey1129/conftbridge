<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Products extends Model
{
    protected $connection = 'db_shop';
    protected $table = 'products';

    public function getImageAttribute($value)
    {
        
        if (Str::startsWith($this->attributes['image'], ['http://', 'https://'])) {
            return $this->attributes['image'];
        }
        return env('SHOP_IMG_URL').$value; 
    }

    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }
    
    public function list($where=[]){
        $list = self::where($where)
                         ->orderBy('sold_count','DESC')
                         ->get();
        return $list;
    }
}
