<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\OrdersRequest;
use App\Models\Shop\ProductSku;
use App\Models\Shop\Orders as Order;
use App\Http\Controllers\Api\Shop\CartController;
// use App\Exceptions\InvalidRequestException;
class OrdersController extends Controller
{
    public function store(Request $request)
    {

        $user    = $request->user();
        $remark = $request->input('remark');
        $items = $request->input('items');
        if (empty($request->uid)) {
            $user = $request->user;
            $uid  = $user->id;
        } else $uid = $request->uid;

        // $address = UserAddress::find($request->input('address_id'));
        $coupon  = null;

        // 如果用户提交了优惠码
        // if ($code = $request->input('coupon_code')) {
        //     $coupon = CouponCode::where('code', $code)->first();
        //     if (!$coupon) {
        //         throw new CouponCodeUnavailableException('优惠券不存在');
        //     }
        // }
        // 参数中加入 $coupon 变量
        // return $orderService->store($user, $address, $request->input('remark'), $request->input('items'), $coupon);
        // if ($coupon) {
        //     $coupon->checkAvailable($user);
        // }
        $order = \DB::transaction(function () use ($user, $remark, $items, $coupon) {
            // 更新此地址的最后使用时间
            // $address->update(['last_used_at' => Carbon::now()]);
            // 创建一个订单
            $order   = new Order([
                // 'address'      => [ // 将地址信息放入订单中
                //     'address'       => $address->full_address,
                //     'zip'           => $address->zip,
                //     'contact_name'  => $address->contact_name,
                //     'contact_phone' => $address->contact_phone,
                // ],
                'remark'       => $remark,
                'total_amount' => 0,
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // if (empty($request->uid)) {
            //     $user = $request->user;
            //     $uid  = $user->id;
            // } else $uid = $request->uid;
            // 写入数据库
            $order->save();

            $totalAmount = 0;
            // 遍历用户提交的 SKU
            foreach ($items as $data) {
                $sku  = ProductSku::find($data['sku_id']);
                // 创建一个 OrderItem 并直接与当前订单关联
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price'  => $sku->price,

                ]);
                $item->order_id =$item->orders_id;
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    // throw new InvalidRequestException('该商品库存不足');
                    return __return($this->errStatus,'该商品库存不足');
                }
            }
            // if ($coupon) {
            //     // 总金额已经计算出来了，检查是否符合优惠券规则
            //     $coupon->checkAvailable($user, $totalAmount);
            //     // 把订单金额修改为优惠后的金额
            //     $totalAmount = $coupon->getAdjustedPrice($totalAmount);
            //     // 将订单与优惠券关联
            //     $order->couponCode()->associate($coupon);
            //     // 增加优惠券的用量，需判断返回值
            //     if ($coupon->changeUsed() <= 0) {
            //         // throw new CouponCodeUnavailableException('该优惠券已被兑完');
            //         return __return($this->errStatus,'该优惠券已被兑完');
            //     }
            // }

            // 更新订单总金额
            $order->update(['total_amount' => $totalAmount]);

            // 将下单的商品从购物车中移除
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartController::class)->remove($skuIds);

            return $order;
        });

        // 这里我们直接使用 dispatch 函数
        // dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;


    }
}
