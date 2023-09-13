<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CfaPool;
use App\Models\CfaPoolOrder;
use App\Models\ChildPool;
use App\Models\MasterPool;
use App\Models\Nodes;
use App\Models\SystemValue;
use App\Models\UserAssets;
use App\Models\UserMoneyLog;
use App\Models\UserPoolOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Log;
use App\User;
use Hash;

class PoolController extends Controller
{

    public function getPools(Request $request)
    {
        $user = $request->user;
        $masterPool = MasterPool::paginate(10);
        return __return($this->successStatus, '获取成功', $masterPool);
    }

    public function searchPool(Request $request)
    {
        $user = $request->user;
        $searchContent = $request->input('search_content', '');
        if (empty($searchContent)) {
            return __return($this->errStatus, '内容不能为空');
        }

        $pools = MasterPool::where('pool_name', 'like', $searchContent . '%')->paginate(10);
        return __return($this->successStatus, '获取成功', $pools);
    }

    // 创建池子
    public function createPool(Request $request)
    {
        $user = $request->user;

        if (!disablePoint(__METHOD__, $user)) {
            return __return($this->errStatus, '点击过快');
        }
        $validator = validator($request->post(), [
            'type'             => 'required|integer|gte:1',
            'pool_name'        => 'required|min:1',
            'pool_title'       => 'required|min:1',
            'pool_desc'        => 'required|min:1',
            'pool_image'       => 'required|min:1',
            'payment_password' => 'required'
        ]);

        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }
        try {
            DB::beginTransaction();

            // 1 消耗门票  2 消耗CFA
            $type = $request->post('type', 1);
            $poolName = $request->post('pool_name', 1);
            $poolTitle = $request->post('pool_title', 1);
            $poolDesc = $request->post('pool_desc', 1);
            $poolImage = $request->post('pool_image', 1);
            if ($type == 1) {
                $piaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                if ($piaoAsset->balance < 10) {
                    DB::rollBack();
                    return __return($this->errStatus, '票余额不足');
                }

                $this->writeBalanceLog($piaoAsset, 0, -10, 19, '创建池子', 'create pool', $piaoAsset->pid, $piaoAsset->pname);
            } else if ($type == 2) {
                // 消费CFA
                $cfaAsset = UserAssets::getBalance($user->id, 1, 1, true);
                if ($cfaAsset->balance < 10) {
                    DB::rollBack();
                    return __return($this->errStatus, 'CFA余额不足');
                }

                $this->writeBalanceLog($cfaAsset, 0, -10, 4, '创建池子', 'create pool', $cfaAsset->pid, $cfaAsset->pname);
            }

            $masterPool = MasterPool::create([
                'uid'        => $user->id,
                'pool_name'  => $poolName,
                'pool_title' => $poolTitle,
                'pool_desc'  => $poolDesc,
                'pool_image' => $poolImage,
            ]);

            for ($i = 1; $i <= 6; $i++) {
                switch ($i) {
                    case 1:
                        $str = 'Ⅰ';
                        $rate = config('pool.one_balance');
                        $name = $poolName . '-' . $str;
                        break;
                    case 2:
                        $str = 'Ⅱ';
                        $rate = config('pool.two_balance');
                        $name = $poolName . '-' . $str;
                        break;
                    case 3:
                        $str = 'Ⅲ';
                        $rate = config('pool.three_balance');
                        $name = $poolName . '-' . $str;
                        break;
                    case 4:
                        $str = 'Ⅳ';
                        $rate = config('pool.four_balance');
                        $name = $poolName . '-' . $str;
                        break;
                    case 5:
                        $str = 'Ⅴ';
                        $rate = config('pool.five_balance');
                        $name = $poolName . '-' . $str;
                        break;
                    case 6:
                        $str = 'Ⅵ';
                        $rate = config('pool.six_balance');
                        $name = $poolName . '-' . $str;
                        break;
                }
                ChildPool::create([
                    'uid'          => $user->id,
                    'master_id'    => $masterPool->id,
                    'pool_name'    => $name,
                    'pool_image'   => $poolImage,
                    'balance_rate' => $rate,
                    'level'        => $i,
                    'status'       => 0
                ]);
            }
            // 创建池子不是池主，点亮池子时是池主
            // $user->is_pooler = 1;
            // $user->save();
            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return __return($this->errStatus, '操作失败');
        }
    }


    public function getChildPools(Request $request)
    {
        $user = $request->user;
        $validator = validator($request->all(), [
            'master_pool_id' => 'required|integer|gt:0'
        ]);
        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        $masterPool = MasterPool::where(['id' => $request->master_pool_id])->first();
        if (!$masterPool) {
            return __return($this->errStatus, '池子不存在');
        }
        $childPools = ChildPool::where(['master_id' => $request->master_pool_id])->where('status', '<=', 2)->orderBy('status', 'desc')->get();
        foreach ($childPools as $childPool) {
            $level = $childPool->level;
            switch ($level) {
                case 1:
                    $needMinBen = config('pool.one_ben');
                    break;
                case 2:
                    $needMinBen = config('pool.two_ben');
                    break;
                case 3:
                    $needMinBen = config('pool.three_ben');
                    break;
                case 4:
                    $needMinBen = config('pool.four_ben');
                    break;
                case 5:
                    $needMinBen = config('pool.five_ben');
                    break;
                case 6:
                    $needMinBen = config('pool.six_ben');
                    break;
            }
            $half = $needMinBen / 2;
            $childPool->pay_mode = $needMinBen . '算力(' . $half . 'U+' . $half . 'CFT)';
            $childPool->mode1 = $needMinBen;
        }

        return __return($this->successStatus, '获取成功', $childPools);
    }

    public function getChildPoolDetail(Request $request)
    {
        $user = $request->user;
        $validator = validator($request->all(), [
            'child_pool_id' => 'required|integer|gt:0'
        ]);
        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        $childPoolId = $request->input('child_pool_id', 0);
        $childPool = ChildPool::where(['id' => $childPoolId])->first();
        $orders = UserPoolOrder::where(['child_pool_id' => $childPool->id, 'status' => 1])->select(['uid', 'price', 'join_at'])->orderBy('price', 'desc')->limit(10)->get();
        $data = [];
        foreach ($orders as $order) {
            $user = User::where(['id' => $order->uid])->first();
            $joinTime = date('H:i:s', strtotime($order->join_at));
            $joinDate = date('Y-m-d 00:00:00', strtotime($order->join_at));
            if ($joinTime > '00:10:00') {
                $begin = date('Y-m-d 00:10:00', strtotime($joinDate) + 24 * 3600);
            } else {
                $begin = date('Y-m-d 00:10:00', strtotime($joinDate));
            }
            // Log::info($begin);
            // dump($order->join_at);
            // dump($begin);
            $days = (strtotime(date('Y-m-d 00:10:00')) - strtotime($begin)) / (24 * 3600);
            $days += 1;
            $data[] = [
                'username' => substr_cut($user->email),
                'money'    => round($order->price, 6) . '',
                'days'     => $days . '',
                'level'    => $user->level
            ];
        }
        $childPool['users'] = $data;
        $childPool['limit_money'] = config('pool.user_limit_money');
        return __return($this->successStatus, '获取成功', $childPool);
    }


    public function joinPool(Request $request)
    {
        $user = $request->user;
        if (!disablePoint(__METHOD__, $user)) {
            return __return($this->errStatus, '点击过快');
        }
        $validator = validator($request->all(), [
            'child_pool_id'    => 'required|integer|gt:0',
            // 支付组合 1 U+票 2 U
            'type'             => 'required|integer|gt:0|lte:2',
            'num'              => 'required|integer|gte:0',
            // 1 不租赁 2 租赁
            'pay_type'         => 'required|integer|gt:0|lte:2',
            'payment_password' => 'required'
        ]);
        $joinPool = config('site.join_pool');
        if (!$joinPool) {
            return __return($this->errStatus, '暂未开放');
        }
        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        $type = $request->type;
        $payType = $request->pay_type;
        $num = $request->num;


        $userPoolOrderNum = UserPoolOrder::where(['uid' => $user->id, 'status' => 1, 'order_type' => 1])->count();
        if ($userPoolOrderNum >= 1) {
            return __return($this->errStatus, '已进入过池子');
        }

        $childPool = ChildPool::where(['id' => $request->child_pool_id, 'status' => 1])->first();
        if (!$childPool) {
            return __return($this->errStatus, '池子不存在');
        }

        if ($childPool->uid == $user->id) {
            //return __return($this->errStatus, '不能进入自己池子');
        }



        $level = $childPool->level;
        switch ($level) {
            case 1:
                $needMinBen = config('pool.one_ben');
                $menPiao = config('pool.one_piao');
                break;
            case 2:
                $needMinBen = config('pool.two_ben');
                $menPiao = config('pool.two_piao');
                break;
            case 3:
                $needMinBen = config('pool.three_ben');
                $menPiao = config('pool.three_piao');
                break;
            case 4:
                $needMinBen = config('pool.four_ben');
                $menPiao = config('pool.four_piao');
                break;
            case 5:
                $needMinBen = config('pool.five_ben');
                $menPiao = config('pool.five_piao');
                break;
            case 6:
                $needMinBen = config('pool.six_ben');
                $menPiao = config('pool.six_piao');
                break;
        }


        $needMinBen = $needMinBen + $num * 50;

        $userLimitMoney = config('pool.user_limit_money');
        if ($needMinBen > ($userLimitMoney)) {
            return __return($this->errStatus, '最大投资金额:max_money', null, ['max_money' => $userLimitMoney]);
        }

        try {
            DB::beginTransaction();

            if ($type == 1) {
                // U+票
                $needU = $needMinBen * 0.5;

                $needPiao = $needMinBen * 0.5;

                $usdtAsset = UserAssets::getBalance($user->id, 8, 1, true);
                if ($usdtAsset->balance < $needU) {
                    DB::rollBack();
                    return __return($this->errStatus, 'USDT 余额不足');
                }


                if ($payType == 2) {
                    return __return($this->errStatus, '暂不支持租赁');
                    // 扣池主票
                    $masterUser = User::where(['id' => $childPool->uid])->first();
                    $piaoAsset = UserAssets::getBalance($masterUser->id, 6, 1, true);
                    if ($piaoAsset->balance < $needPiao) {
                        DB::rollBack();
                        return __return($this->errStatus, '池主票余额不足');
                    }
                } else {
                    // 扣自己票
                    $piaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                    // 加入池子要额外扣一张票
                    if ($piaoAsset->balance < ($needPiao + $menPiao)) {
                        DB::rollBack();
                        return __return($this->errStatus, '票余额不足');
                    }
                }

                $this->writeBalanceLog($usdtAsset, 0, -$needU, 10, '质押', '质押', $usdtAsset->pid, $usdtAsset->pname);
                $this->writeBalanceLog($piaoAsset, 0, -$needPiao, 10, '质押', '质押', $piaoAsset->pid, $piaoAsset->pname);
                $this->writeBalanceLog($piaoAsset, 0, -$menPiao, 19, '质押', '质押', $piaoAsset->pid, $piaoAsset->pname);


                $userPoolOrder = UserPoolOrder::where(['uid' => $user->id, 'master_pool_id' => $childPool->master_id, 'child_pool_id' => $childPool->id])->first();
                // if ($userPoolOrder) {
                //     $userPoolOrder->piao_num = $needPiao;
                //     $userPoolOrder->usdt_num = $needU;
                //     $userPoolOrder->cfg_num = 0;
                //     $userPoolOrder->price = $needMinBen;
                //     $userPoolOrder->status = 1;
                //     $userPoolOrder->type = 1;
                //     $userPoolOrder->pay_type = $payType;
                //     $userPoolOrder->join_at = date('Y-m-d H:i:s');
                //     $userPoolOrder->exit_at = null;
                //     $userPoolOrder->save();
                // } else {
                UserPoolOrder::create([
                    'uid'            => $user->id,
                    'master_pool_id' => $childPool->master_id,
                    'child_pool_id'  => $childPool->id,
                    'piao_num'       => $needPiao,
                    'usdt_num'       => $needU,
                    'cfg_num'        => 0,
                    'price'          => $needMinBen,
                    'status'         => 1,
                    'type'           => 1,
                    'pay_type'       => $payType,
                    'join_at'        => date('Y-m-d H:i:s')
                ]);
                // }
            } else if ($type == 2) {
                // U
                $needU = $needMinBen;
                $usdtAsset = UserAssets::getBalance($user->id, 8, 1, true);
                if ($usdtAsset->balance < $needU) {
                    DB::rollBack();
                    return __return($this->errStatus, 'USDT 余额不足');
                }

                $piaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                // 加入池子要额外扣一张票
                if ($piaoAsset->balance < $menPiao) {
                    DB::rollBack();
                    return __return($this->errStatus, '票余额不足');
                }

                $this->writeBalanceLog($usdtAsset, 0, -$needU, 10, '质押', '质押', $usdtAsset->pid, $usdtAsset->pname);
                $this->writeBalanceLog($piaoAsset, 0, -$menPiao, 19, '质押', '质押', $piaoAsset->pid, $piaoAsset->pname);


                $userPoolOrder = UserPoolOrder::where(['uid' => $user->id, 'master_pool_id' => $childPool->master_id, 'child_pool_id' => $childPool->id])->first();
                // if ($userPoolOrder) {
                //     $userPoolOrder->piao_num = 0;
                //     $userPoolOrder->usdt_num = $needU;
                //     $userPoolOrder->cfg_num = 0;
                //     $userPoolOrder->price = $needMinBen;
                //     $userPoolOrder->status = 1;
                //     $userPoolOrder->type = 2;
                //     $userPoolOrder->pay_type = $payType;
                //     $userPoolOrder->join_at = date('Y-m-d H:i:s');
                //     $userPoolOrder->exit_at = null;
                //     $userPoolOrder->save();
                // } else {
                UserPoolOrder::create([
                    'uid'            => $user->id,
                    'master_pool_id' => $childPool->master_id,
                    'child_pool_id'  => $childPool->id,
                    'piao_num'       => 0,
                    'usdt_num'       => $needU,
                    'cfg_num'        => 0,
                    'price'          => $needMinBen,
                    'status'         => 1,
                    'type'           => 2,
                    'pay_type'       => $payType,
                    'join_at'        => date('Y-m-d H:i:s')
                ]);
                // }
            }


            $childPool = ChildPool::where(['id' => $request->child_pool_id])->lockForUpdate()->first();
            $childPool->user_num++;
            $childPool->total_usdt += $needMinBen;
            $childPool->save();

            $masterPool = MasterPool::where(['id' => $childPool->master_id])->lockForUpdate()->first();
            $masterPool->user_num++;
            $masterPool->total_usdt += $needMinBen;
            $masterPool->save();

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return __return($this->errStatus, '操作失败');
        }
    }

    public function exitPool(Request $request)
    {
        $user = $request->user;
        if (!disablePoint(__METHOD__, $user)) {
            return __return($this->errStatus, '点击过快');
        }
        $validator = validator($request->all(), [
            // 订单ID
            'master_pool_id'   => 'required|integer|gt:0',
            'payment_password' => 'required'
        ]);

        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        try {
            DB::beginTransaction();

            $userPoolOrder = UserPoolOrder::where(['id' => $request->master_pool_id])->first();
            if (!$userPoolOrder) {
                DB::rollBack();
                return __return($this->errStatus, '池子订单不存在');
            }

            $now = time();
            $joinAt = strtotime($userPoolOrder->join_at);
            if (($joinAt + 24 * 3600) > $now && $userPoolOrder->order_type != 2) {
                return __return($this->errStatus, '质押未满24小时,不可退出');
            }

            // 主池
            $masterPool = MasterPool::where(['id' => $userPoolOrder->master_pool_id])->first();
            if (!$masterPool) {
                DB::rollBack();
                return __return($this->errStatus, '池子不存在');
            }

            $userPoolOrder->status = 0;
            $userPoolOrder->exit_at = date('Y-m-d H:i:s');
            // $userPoolOrder->is_reback = 0;
            $userPoolOrder->save();

            $rebakePiao = $userPoolOrder->piao_num;
            $rebakeUsdt = $userPoolOrder->usdt_num;
            if ($userPoolOrder->type == 1) {
                // U+票
                if ($userPoolOrder->pay_type == 2) {
                    // 票是池主的
                    $masterPiaoAsset = UserAssets::getBalance($masterPool->uid, 6, 1, true);
                } else {
                    // 票是自己的
                    $masterPiaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
                }
                if ($rebakePiao > 0) {
                    $this->writeBalanceLog($masterPiaoAsset, 0, $rebakePiao, 11, '退出质押', '退出质押', $masterPiaoAsset->pid, $masterPiaoAsset->pname);
                }

                if ($rebakeUsdt > 0) {
                    $userUsdtAsset = UserAssets::getBalance($user->id, 8, 1, true);
                    $this->writeBalanceLog($userUsdtAsset, 0, $rebakeUsdt, 11, '退出质押', '退出质押', $userUsdtAsset->pid, $userUsdtAsset->pname);
                }

            } else {
                if ($rebakeUsdt > 0) {
                    // U
                    $userUsdtAsset = UserAssets::getBalance($user->id, 8, 1, true);
                    $this->writeBalanceLog($userUsdtAsset, 0, $rebakeUsdt, 11, '退出质押', '退出质押', $userUsdtAsset->pid, $userUsdtAsset->pname);
                }

            }

            $childPool = ChildPool::where(['id' => $userPoolOrder->child_pool_id])->lockForUpdate()->first();
            $childPool->user_num -= 1;
            $childPool->total_usdt -= $userPoolOrder->price;
            $childPool->save();

            $masterPool = MasterPool::where(['id' => $childPool->master_id])->lockForUpdate()->first();
            $masterPool->user_num -= 1;
            $masterPool->total_usdt -= $userPoolOrder->price;
            $masterPool->save();

            $userPoolOrder->delete();

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }
    }


    public function openPool(request $request)
    {
        $user = $request->user;
        if (!disablePoint(__METHOD__, $user)) {
            return __return($this->errStatus, '点击过快');
        }
        $validator = validator($request->all(), [
            'child_pool_id'    => 'required|integer|gt:0',
            'payment_password' => 'required'
        ]);

        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }
        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        // 要点亮的池子
        $childPool = ChildPool::where(['id' => $request->child_pool_id, 'uid' => $user->id])->first();
        if (!$childPool) {
            return __return($this->errStatus, '操作错误');
        }

        $masterPoolNum = MasterPool::where(['uid' => $user->id])->where('id', '!=', $childPool->master_id)->whereIn('status', [1, -1])->count();
        if ($masterPoolNum >= 1) {
            return __return($this->errStatus, '已有池子激活未销毁,暂时不可激活');
        }

        switch ($childPool->level) {
            case 1:
                $needPiao = config('pool.open_level_one');
                $needUserNum = 0;
                break;
            case 2:
                $needPiao = config('pool.open_level_two');
                $needUserNum = config('pool.open_two_num');
                break;
            case 3:
                $needPiao = config('pool.open_level_three');
                $needUserNum = config('pool.open_three_num');
                break;
            case 4:
                $needPiao = config('pool.open_level_four');
                $needUserNum = config('pool.open_four_num');
                break;
            case 5:
                $needPiao = config('pool.open_level_five');
                $needUserNum = config('pool.open_five_num');
                break;
            case 6:
                $needPiao = config('pool.open_level_six');
                $needUserNum = config('pool.open_six_num');
                break;
            case 7:
                $needPiao = config('pool.open_level_seven');
                $needUserNum = config('pool.open_seven_num');
                break;
        }

        if ($childPool->level > 1) {
            $prevLevel = $childPool->level - 1;
            $preveChildPool = ChildPool::where(['master_id' => $childPool->master_id, 'level' => $prevLevel, 'status' => 1])->first();
            if (empty($preveChildPool)) {
                return __return($this->errStatus, '池子不存在');
            }

            if ($preveChildPool->user_num < $needUserNum) {
                return __return($this->errStatus, '池子人数不足');
            }
        }

        try {
            DB::beginTransaction();
            $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
            if ($userPiaoAsset->balance < $needPiao) {
                return __return($this->errStatus, '票余额不足');
            }
            $this->writeBalanceLog($userPiaoAsset, 0, -$needPiao, 19, '点亮池', 'open pool', $userPiaoAsset->pid, $userPiaoAsset->pname);
            $childPool = ChildPool::where(['id' => $request->child_pool_id, 'uid' => $user->id])->first();
            $masterPool = MasterPool::where(['id' => $childPool->master_id])->first();
            $masterPool->status = 1;
            $masterPool->level = $childPool->level;
            $masterPool->save();
            $childPool->status = 1;
            $childPool->open_time = date('Y-m-d H:i:s');
            $childPool->save();

            $user->is_pooler = 1;
            $user->save();

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }
    }

    // 我创建的池子
    public function myPools(Request $request)
    {
        $user = $request->user;
        $masterPool = MasterPool::where(['uid' => $user->id])->where('status', '<', 2)->orderBy('status', 'desc')->paginate(10);
        return __return($this->successStatus, '获取成功', $masterPool);
    }

    // 我的参与
    public function myJoin(Request $request)
    {
        $user = $request->user;
        // $userPoolOrders = UserPoolOrder::where(['uid' => $user->id])->orderBy('status', 'desc')->paginate(10);

        // foreach ($userPoolOrders as $userPoolOrder) {
        //     $childPool = ChildPool::where(['id' => $userPoolOrder->child_pool_id])->first();
        //     //$masterPool = MasterPool::where(['id' => $userPoolOrder->master_pool_id])->first();

        //     $userPoolOrder->pool_name = $childPool->pool_name;
        //     $userPoolOrder->pool_image = $childPool->pool_image;
        //     $userPoolOrder->balance_rate = $childPool->balance_rate;
        //     $userPoolOrder->level = $childPool->level;
        //     $userPoolOrder->user_num = $childPool->user_num;
        // }
        $userPoolOrder = UserPoolOrder::where(['uid' => $user->id, 'status' => 1])->first();
        if ($userPoolOrder) {
            $childPool = ChildPool::where(['id' => $userPoolOrder->child_pool_id])->first();
            //$masterPool = MasterPool::where(['id' => $userPoolOrder->master_pool_id])->first();
            $level = $childPool->level;
            switch ($level) {
                case 1:
                    $rate = config('pool.one_balance');
                    break;
                case 2:
                    $rate = config('pool.two_balance');
                    break;
                case 3:
                    $rate = config('pool.three_balance');
                    break;
                case 4:
                    $rate = config('pool.four_balance');
                    break;
                case 5:
                    $rate = config('pool.five_balance');
                    break;
                case 6:
                    $rate = config('pool.six_balance');
                    break;
            }

            $userPoolOrder->pool_name = $childPool->pool_name;
            $userPoolOrder->pool_image = $childPool->pool_image;
            $userPoolOrder->balance_rate = $rate;
            $userPoolOrder->level = $childPool->level;
            $userPoolOrder->user_num = $childPool->user_num;
            $userPoolOrder->can_exit = strtotime($userPoolOrder->join_at) + 24 * 3600;
            return __return($this->successStatus, '获取成功', $userPoolOrder);
        } else {
            return __return($this->successStatus, '获取成功', []);
        }

    }


    // 购买节点
    public function buyNode(Request $request)
    {
        $user = $request->user;
        if (!disablePoint(__METHOD__, $user)) {
            return __return($this->errStatus, '点击过快');
        }
        $validator = validator($request->all(), [
            'type'             => 'required|integer|gte:1',
            'payment_password' => 'required'
        ]);

        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        if ($user->node_level > 0) {
            return __return($this->errStatus, '已是节点');
        }

        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        $type = $request->input('type', 1);

        $node = Nodes::where(['id' => $type])->first();

        if (!$node) {
            return __return($this->errStatus, '节点不存在');
        }

        $needU = $node->price;
        $addSuan = $node->song_suan;
        $addCfa = $node->song_cfa;
        $addPiao = $node->song_piao;
        // if ($type == 1) {
        //     $needU = 500;
        //     $addSuan = 500;
        //     $addPiao = 500;
        //     $addCfa = 1000;
        // } else if ($type == 2) {
        //     $needU = 1000;
        //     $addSuan = 1000;
        //     $addPiao = 1000;
        //     $addCfa = 2000;
        // } else if ($type == 3) {
        //     $needU = 5000;
        //     $addSuan = 5000;
        //     $addPiao = 5000;
        //     $addCfa = 10000;
        // }

        try {
            DB::beginTransaction();
            $userUsdtAsset = UserAssets::getBalance($user->id, 8, 1, true);
            if ($userUsdtAsset->balance < $needU) {
                DB::rollBack();
                return __return($this->errStatus, 'USDT 余额不足');
            }
            $this->writeBalanceLog($userUsdtAsset, 0, -$needU, 12, '购买节点', 'buy node', $userUsdtAsset->pid, $userUsdtAsset->pname);

            // 算力
            $userSuanLiAsset = UserAssets::getBalance($user->id, 5, 1, true);
            $this->writeBalanceLog($userSuanLiAsset, 0, $addSuan, 12, '购买节点增加算力', 'add compute power', $userSuanLiAsset->pid, $userSuanLiAsset->pname);

            // 门票
            $userPiaoAsset = UserAssets::getBalance($user->id, 4, 1, true);
            $this->writeBalanceLog($userPiaoAsset, 0, $addPiao, 12, '购买节点赠送票', 'add CFB', $userPiaoAsset->pid, $userPiaoAsset->pname);

            $user->node_level = $type;
            $user->has_release_cfa = 0;
            $user->total_release_cfa = $addCfa;
            $user->save();

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return __return($this->errStatus, '操作失败');
        }

    }



    public function nodes(Request $request)
    {
        $user = $request->user;

        $nodes = Nodes::where(['status' => 1])->get();

        return __return($this->successStatus, '获取成功', $nodes);
    }


    public function poolData(Request $request)
    {
        $full = SystemValue::where(['name' => 'pool_master_balance_full'])->value('value');
        $five = SystemValue::where(['name' => 'pool_master_balance_five'])->value('value');
        $six = SystemValue::where(['name' => 'pool_master_balance_six'])->value('value');
        $cfa = SystemValue::where(['name' => 'pool_master_balance_cfa'])->value('value');
        $highNode = SystemValue::where(['name' => 'high_node_balance'])->value('value');

        // 全网日收益的5%
        $week = SystemValue::where(['name' => 'fen_static_balance'])->value('value');

        $pool1 = User::where(['email' => 'pool1@qq.com'])->first();
        $five = UserAssets::getBalance($pool1->id, 3)->balance;

        $pool2 = User::where(['email' => 'pool2@qq.com'])->first();
        $six = UserAssets::getBalance($pool2->id, 3)->balance;

        $pool3 = User::where(['email' => 'pool3@qq.com'])->first();
        $cfa = UserAssets::getBalance($pool3->id, 3)->balance;

        $pool4 = User::where(['email' => 'pool4@qq.com'])->first();
        $highNode = UserAssets::getBalance($pool4->id, 3)->balance;
        $da = round($week * 0.05, 6);

        $data = [
            'five'      => $five,
            'six'       => $six,
            'cfa'       => $cfa,
            'high_node' => $highNode,
            'da'        => $da
        ];
        return __return($this->successStatus, '获取成功', $data);
    }


    public function getChildPool(Request $request)
    {
        $user = $request->user;
        $level = $request->input('level', 0);

        if ($level == 0) {
            $childPools = ChildPool::where('status', '<=', 2)->orderBy('status', 'desc')->paginate(10);
        } else if ($level > 0 && $level < 7) {
            $childPools = ChildPool::where(['level' => $level])->where('status', '<=', 2)->orderBy('status', 'desc')->paginate(10);
            foreach ($childPools as $childPool) {
                $level = $childPool->level;
                switch ($level) {
                    case 1:
                        $needMinBen = config('pool.one_ben');
                        break;
                    case 2:
                        $needMinBen = config('pool.two_ben');
                        break;
                    case 3:
                        $needMinBen = config('pool.three_ben');
                        break;
                    case 4:
                        $needMinBen = config('pool.four_ben');
                        break;
                    case 5:
                        $needMinBen = config('pool.five_ben');
                        break;
                    case 6:
                        $needMinBen = config('pool.six_ben');
                        break;
                    default:
                        $needMinBen = 0;
                        break;
                }
                $half = $needMinBen / 2;
                $childPool->pay_mode = $needMinBen . '算力(' . $half . 'U+' . $half . 'CFT)';
                $childPool->mode1 = $needMinBen;
            }
        } else if ($level >= 7) {
            $childPools = CfaPool::paginate(10);
            foreach ($childPools as $childPool) {
                $childPool->pay_mode = '';
                $childPool->mode1 = 0;
                $childPool->total_usdt = $childPool->total_price;
            }
        }


        return __return($this->successStatus, '获取成功', $childPools);
    }

    public function getBalanceRate(Request $request)
    {
        $user = $request->user;
        $oneRate = config('pool.one_balance');
        $twoRate = config('pool.two_balance');
        $threeRate = config('pool.three_balance');
        $fourRate = config('pool.four_balance');
        $fiveRate = config('pool.five_balance');
        $sixRate = config('pool.six_balance');

        return __return($this->successStatus, '获取成功', [
            'one'   => $oneRate,
            'two'   => $twoRate,
            'three' => $threeRate,
            'four'  => $fourRate,
            'five'  => $fiveRate,
            'six'   => $sixRate
        ]);
    }

    // 算力前21名
    public function highSuanData(Request $request)
    {
        $user = $request->user;

        $value = SystemValue::where(['name' => 'super_node_one'])->value('value');

        $userAssets = UserAssets::where(['pid' => 5])->where('balance', '>', 0)->orderBy('balance', 'desc')->limit(21)->get();
        $suanLiData = [];
        foreach ($userAssets as $userAsset) {
            $user = User::where(['id' => $userAsset->uid])->first();

            $suanLiData[] = [
                'nickname' => $user->nickname,
                'suanli'   => round($userAsset->balance, 6)
            ];
        }

        $balanceData = [];
        $userMoneyLogs = UserMoneyLog::where(['type' => 21])->where('money', '>', 0)->orderBy('money', 'desc')->paginate(10);
        foreach ($userMoneyLogs as $userMoneyLog) {
            $user = User::where(['id' => $userMoneyLog->uid])->first();
            $balanceData[] = [
                'nickname' => $user->nickname,
                'money'    => round($userMoneyLog->money, 6)
            ];
        }

        $data = [
            'value'        => $value,
            'suanli_data'  => $suanLiData,
            'balance_data' => $balanceData
        ];

        return __return($this->successStatus, '获取成功', $data);
    }

    // CFA池订单
    public function cfaPoolOrder(Request $request)
    {
        $user = $request->user;

        // $orders = UserPoolOrder::where(['uid' => $user->id, 'order_type' => 2, 'status' => 1])->paginate(10);
        $orders = CfaPoolOrder::where(['uid' => $user->id])->paginate(10);
        foreach ($orders as $order) {
            if ($order) {
                $cfaPool = CfaPool::where(['id' => $order->cfa_pool_id])->first();
                $order->pool_name = $cfaPool->pool_name;
                $order->pool_image = $cfaPool->pool_image;
                $order->user_num = $cfaPool->user_num;
            }
        }

        return __return($this->successStatus, '获取成功', $orders);
    }


    public function joinCfaPool(Request $request)
    {
        $user = $request->user;
        $validator = validator($request->all(), [
            'order_id'         => 'required|integer|gte:1',
            'payment_password' => 'required'
        ]);

        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }
        $userPoolOrderId = $request->input('order_id', 0);


        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        try {
            DB::beginTransaction();


            $userPoolOrder = UserPoolOrder::where(['id' => $userPoolOrderId, 'uid' => $user->id])->lockForUpdate()->first();
            if (empty($userPoolOrder)) {
                DB::rollBack();
                return __return($this->errStatus, '参数错误');
            }

            if ($userPoolOrder->order_type != 2) {
                DB::rollBack();
                return __return($this->errStatus, '参数错误');
            }

            // 对应的主池和子池


            // 找到对应的cfa_pool
            $cfaPool = CfaPool::where(['master_id' => $userPoolOrder->master_pool_id])->lockForUpdate()->first();
            if (empty($cfaPool)) {
                return __return($this->errStatus, '参数错误');
            }

            // 判断是U+票还是纯U
            // U+票可直接转cfa订单，纯U需补和U等数的票
            if ($userPoolOrder->pay_type == 1) {
                // U+票
                CfaPoolOrder::create([
                    'uid'         => $userPoolOrder->uid,
                    'cfa_pool_id' => $cfaPool->id,
                    'piao_num'    => $userPoolOrder->piao_num,
                    'usdt_num'    => $userPoolOrder->usdt_num,
                    'price'       => $userPoolOrder->piao_num + $userPoolOrder->usdt_num,
                    'status'      => 1,
                ]);

                $cfaPool->piao_num += $userPoolOrder->piao_num;
                $cfaPool->usdt_num += $userPoolOrder->usdt_num;
                $cfaPool->total_price += ($userPoolOrder->usdt_num * 2);
                $cfaPool->user_num += 1;
                $cfaPool->save();

                $userPiaoAsset = UserAssets::getBalance($userPoolOrder->uid, 4, 1, true);
                $this->writeBalanceLog($userPiaoAsset, 0, $userPoolOrder->piao_num, 28, '加入CFA池', 'join in CFA pool', $userPiaoAsset->pid, $userPiaoAsset->pname);
                $this->writeBalanceLog($userPiaoAsset, 0, -$userPoolOrder->piao_num, 19, '加入CFA池', 'join in CFA pool', $userPiaoAsset->pid, $userPiaoAsset->pname);


            } else if ($userPoolOrder->pay_type == 2) {
                // 纯U,需补票
                $needPiao = $userPoolOrder->usdt_num;
                $userPiaoAsset = UserAssets::getBalance($userPoolOrder->uid, 4, 1, true);

                if ($userPiaoAsset->balance < $needPiao) {
                    return __return($this->errStatus, '票余额不足');
                }

                $this->writeBalanceLog($userPiaoAsset, $userPoolOrder->id, -$needPiao, 19, '加入CFA池', 'join in CFA pool', $userPiaoAsset->pid, $userPiaoAsset->pname);

                CfaPoolOrder::create([
                    'uid'         => $userPoolOrder->uid,
                    'cfa_pool_id' => $cfaPool->id,
                    'piao_num'    => $needPiao,
                    'usdt_num'    => $userPoolOrder->usdt_num,
                    'price'       => $needPiao + $userPoolOrder->usdt_num,
                    'status'      => 1,
                ]);
                $cfaPool->piao_num += $needPiao;
                $cfaPool->usdt_num += $userPoolOrder->usdt_num;
                $cfaPool->total_price += ($userPoolOrder->usdt_num * 2);
                $cfaPool->user_num += 1;
                $cfaPool->save();
            }
            $masterPool = MasterPool::where(['id' => $userPoolOrder->master_pool_id])->lockForUpdate()->first();
            $masterPool->total_usdt -= ($userPoolOrder->usdt_num * 2);
            $masterPool->user_num -= 1;
            $masterPool->save();

            $childPool = ChildPool::where(['id' => $userPoolOrder->child_pool_id])->lockForUpdate()->first();
            $childPool->total_usdt -= ($userPoolOrder->usdt_num * 2);
            $childPool->user_num -= 1;
            $childPool->save();

            $userPoolOrder->delete();
            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return __return($this->errStatus, '操作失败');
        }
    }

    public function exitCfaPool(Request $request)
    {
        $user = $request->user;
        $validator = validator($request->all(), [
            'order_id'         => 'required|integer|gte:1',
            'payment_password' => 'required'
        ]);

        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }
        if (empty($user->payment_password)) {
            return __return($this->errStatus, '您还没有设置支付密码，请先设置支付密码');
        }

        if (!Hash::check($request->payment_password, $user->payment_password)) {
            return __return($this->errStatus, '支付密码错误');
        }

        $orderId = $request->input('order_id', 0);

        try {
            DB::beginTransaction();
            $cfaPoolOrder = CfaPoolOrder::where(['id' => $orderId, 'uid' => $user->id])->lockForUpdate()->first();
            if (!$cfaPoolOrder) {
                DB::rollBack();
                return __return($this->errStatus, '订单不存在');
            }

            // 返还U,不返还算力
            $rebakeU = $cfaPoolOrder->usdt_num;
            $userAsset = UserAssets::getBalance($cfaPoolOrder->uid, 8, 1, true);
            $this->writeBalanceLog($userAsset, $cfaPoolOrder->id, $rebakeU, 29, '退出CFA池', 'exit CFA pool', $userAsset->pid, $userAsset->pname);

            $cfaPoolOrder->status = 0;
            $cfaPoolOrder->save();

            $cfaPool = CfaPool::where(['id' => $cfaPoolOrder->cfa_pool_id])->lockForUpdate()->first();
            $cfaPool->piao_num -= $cfaPoolOrder->piao_num;
            $cfaPool->usdt_num -= $cfaPoolOrder->usdt_num;
            $cfaPool->total_price -= $cfaPoolOrder->price;
            $cfaPool->user_num -= 1;
            $cfaPool->save();

            $cfaPoolOrder->delete();
            DB::commit();

            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }

    }

}