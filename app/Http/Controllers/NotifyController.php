<?php

namespace App\Http\Controllers;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\Chain;
use App\Models\ChainCoin;
use App\Models\ChainNetwork;
use App\Models\Recharge;
use App\Models\UserAddress;
use App\Models\UserAssets;
use App\Models\WalletCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyController extends Controller
{
    use WriteUserMoneyLog;

    public function zzWalletPay(Request $request)
    {
        $timestamp = $request->header('timestamp');
        $nonce = $request->header('nonce');
        $sign = $request->header('sign');
        $data = [
            'user_id'      => $request->input('user_id'),
            'chain_type'   => $request->input('chain_type'),
            'from_address' => $request->input('from_address'),
            'to_address'   => $request->input('to_address'),
            'amount'       => $request->input('amount'),
            'hash'         => $request->input('hash'),
            'index'        => (int)$request->input('index'),
            'token'        => $request->input('token'),
        ];
        Log::info(json_encode($data));
        ksort($data);
        $json_data = json_encode($data, true);
        $sign_str = trim($json_data) . $timestamp . $nonce;
        $request_sign = md5(md5($sign_str));
        if ($request_sign == $sign) {

            $chain_id = ChainNetwork::typeGetID($data['chain_type']);
            $user_address = UserAddress::where('address', $data['to_address'])
                ->where('uid', $data['user_id'])
                ->where('type', $chain_id)
                ->first();
            if (empty($user_address) || empty($chain_id)) {
                echo '地址错误或网络';
                return;
            }
            $model = Recharge::where('ordnum', $data['hash'])->first();
            if (!empty($model)) {
                echo '数据已存在';
                return;
            }
            var_dump($chain_id, $data['token']);
            $chain_coin = ChainCoin::getInfo($chain_id, $data['token']);
            if (empty($chain_coin)) {
                echo 'PID查询失败';
                return;
            }
            DB::beginTransaction();
            try {
                $model = new Recharge();
                $model->uid = $user_address->uid;
                $model->ordnum = $data['hash'];
                $model->wallet_address = $user_address->address;
                $model->usdt = $data['amount'];
                $model->status = 2;
                $model->mark = '链上充值';
                $model->type = 2;
                $model->pid = $chain_coin->pid;
                $model->arrival_at = now(); //充值时间
                $model->ptype = 1; //充值到资金账户
                $model->cz_type = $chain_coin->chain_id;
                $model->en_mark = '链上充值';
                $model->save();
                $pid = $model->pid;
                $ptype = $model->ptype;
                $money = $model->usdt;
                $mark = $model->mark;
                $en_mark = $model->en_mark;
                $id = $model->id;
                $asset = UserAssets::where('uid', $model->uid)
                    ->where('pid', $pid)
                    ->where('ptype', $ptype)->first();
                $this->writeBalanceLog($asset, $id, $money, 8, $mark, $en_mark, $pid, $asset->pname, $ptype);
                DB::commit();

            } catch (\Error $e) {
                DB::rollBack();
                var_dump($e->getMessage());
            } catch (\Throwable $e) {
                DB::rollBack();
                var_dump($e->getMessage());
            }
        } else {
            $ip = $request->ip();
            Log::info('回掉存在违规请求' . $ip);
            echo 'err';
        }

    }
}