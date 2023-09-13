<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use App\Models\UserAssets;
use App\Service\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinController extends Controller
{
    public function coinLst()
    {
        $lst = DB::table('wallet_code')
            ->where('is_show', 1)
            ->get()
            ->each(function ($item)
            {
                $item->icon = ImageService::fullUrl($item->icon);

            });
        return __return($this->successStatus, '币种列表', $lst);

    }

    public function chainLst(Request $request)
    {
        $pid = $request->input('pid', 8);
        $pay_or_withdraw = $request->input('pay_or_withdraw', '');
        $lst = DB::table('chain_coin')
            ->join('chain', 'chain.id', '=', 'chain_coin.chain_id')
            ->where('pid', $pid)
            ->where('chain_coin.status', 1);
        if ($pay_or_withdraw == 'pay') {
            $lst = $lst->where('is_pay', 1);
        } else if ($pay_or_withdraw == 'withdraw') {
            $lst = $lst->where('is_withdraw', 1);
        } else {
            return __return($this->successStatus, '通道列表', []);
        }
        $balance = UserAssets::getBalance($request->user->id, $pid)->balance;
        $lst = $lst->select(['chain', 'type', 'pid', 'withdraw_min', 'withdraw_max', 'withdraw_fee'])
            ->get()
            ->each(function ($item) use ($balance)
            {
                $item->balance = $balance;
            });
        return __return($this->successStatus, '通道列表', $lst);
    }

    public function chainNetworkLst()
    {
        $lst = DB::table('chain_network')
            ->whereIn('id', [1, 2, 5])
            ->where('state', 1)
            ->get();
        return __return($this->successStatus, '通道列表', $lst);
    }

}