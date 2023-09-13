<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop\CartItem;
use App\Models\Shop\ProductSku;
use App\Models\Shop\Product;
use Auth;
class CartController extends Controller
{

    public function add(Request $request)
    {   
        $skuId = $request['sku_id'];
        $amount = $request['amount'];
        $sku = ProductSku::find($skuId);
        if (!$sku) {
            return __return($this->errStatus,'该商品不存在');
        }
        if (!Product::find($skuId)->on_sale) {
            return __return($this->errStatus,'该商品未上架');
        }
        if ($sku->stock === 0) {
            return __return($this->errStatus,'该商品已售完');
        }
        if ($request->input('amount') > 0 && $sku->stock < $request->input('amount')) {
            return __return($this->errStatus,'该商品库存不足');
        }

        if (empty($request->uid)) {
            $user = $request->user;
            $uid  = $user->id;
        } else $uid = $request->uid;

        
        // 从数据库中查询该商品是否已经在购物车中
        if ($item = CartItem::where([['product_sku_id', $skuId],['user_id', $uid]])->first()) {
            // 如果存在则直接叠加商品数量
            $item->update([
                'amount' => $item->amount + $amount,
            ]);
        } else {
            // 否则创建一个新的购物车记录
            $item = new CartItem(['amount' => $amount]);
            $item->user()->associate($user);
            $item->productSku()->associate($skuId);
            $item->save();
            unset($item['user']);
        }
        return __return($this->successStatus, '加入购物车成功!',$item);

    }
    public function remove($skuIds)
    {
        // 可以传单个 ID，也可以传 ID 数组
        if (!is_array($skuIds)) {
            $skuIds = [$skuIds];
        }
        CartItem::whereIn('product_sku_id', $skuIds)->delete();
    }
}
