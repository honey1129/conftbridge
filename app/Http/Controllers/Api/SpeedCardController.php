<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleAuctionOrder;
use App\Models\AuctionCard;
use App\Models\AuctionCardPaiLog;
use App\Models\AuctionYuLog;
use App\Models\ChildPool;
use App\Models\MasterPool;
use App\Models\SpeedCard;
use App\Models\SpeedCardLog;
use App\Models\UserSpeedCard;
use App\Models\UserAssets;
use App\Models\UserPoolOrder;
use Illuminate\Http\Request;
use DB;
use Log;
use App\User;
use Hash;
use Illuminate\Support\Facades\Redis;

class SpeedCardController extends Controller
{
    public function cardList(Request $request)
    {
        $user = $request->user;
        $validator = validator([
            'status' => 'required|integer|gte:0'
        ]);
        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }
        $status = $request->input('status');
        if ($status == 0) {
            // 未使用
            $userCards = UserSpeedCard::where(['uid' => $user->id, 'status' => $status])->where('end_time', '<=', now())->paginate(10);
        } else if ($status == 1) {
            // 已使用
            $userCards = UserSpeedCard::where(['uid' => $user->id, 'status' => $status])->paginate(10);
        } else if ($status == 2) {
            // 已过期
            $userCards = UserSpeedCard::where(['uid' => $user->id, 'status' => 0])->where('end_time', '>', now())->paginate(10);
        }

        foreach ($userCards as $userCard) {
            $card = AuctionCard::where(['id' => $userCard->card_id])->first();
            $userCard->type = $card->type;
            $userCard->rate = $card->rate;
            $userCard->card_name = $card->card_name;
        }
        return __return($this->successStatus, '获取成功', $userCards);
    }

    public function transferCard(Request $request)
    {
        $user = $request->user;
        $validator = validator([
            'to_account'       => 'required',
            'card_id'          => 'required',
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


        $toUser = User::where(['email' => $request->to_account])->first();
        if (!$toUser) {
            return __return($this->errStatus, '对方用户信息不存在');
        }

        $userSpeedCard = UserSpeedCard::where(['id' => $request->card_id, 'uid' => $user->id])->first();
        if (!$userSpeedCard) {
            return __return($this->errStatus, '加速卡不存在');
        }
        try {
            DB::beginTransaction();
            $userSpeedCard->uid = $toUser->id;
            $userSpeedCard->save();

            SpeedCardLog::writeLog($user, $toUser, $userSpeedCard->id);
            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return __return($this->errStatus, '操作失败');
        }
    }


    /**
     * 拍卖列表
     */
    public function auctionList(Request $request)
    {
        $user = $request->user;
        $auctionCards = AuctionCard::where('status', '<=', 1)->select(['id', 'card_name', 'price', 'begin_piao', 'current_piao', 'period', 'rate', 'start_time', 'end_time', 'type', 'yu_uids'])->paginate(10);
        foreach ($auctionCards as $auctionCard) {
            $yuUidsStr = $auctionCard->yu_uids;
            $yuUidArr = explode(',', $yuUidsStr);
            if (!in_array($user->id, $yuUidArr)) {
                $auctionCard->has_yu = 0;
            } else {
                $auctionCard->has_yu = 1;
            }
        }
        return __return($this->successStatus, '获取成功', $auctionCards);
    }

    // 先花U预约拍卖
    public function auctionYu(Request $request)
    {
        $user = $request->user;
        if (!disablePoint(__METHOD__, $user)) {
            return __return($this->errStatus, '点击过快');
        }
        $validator = validator($request->all(), [
            'id'               => 'required|integer|gt:0',
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


        $id = $request->input('id');
        $auctionCard = AuctionCard::where(['id' => $id])->first();

        $now = time();
        $timeAgo = 10 * 60;
        $startTime = strtotime($auctionCard->start_time);
        if (($now + $timeAgo) > $startTime) {
            return __return($this->errStatus, '超过预约时间');
        }

        try {
            DB::beginTransaction();
            $needU = $auctionCard->price;
            $userUsdtAsset = UserAssets::getBalance($user->id, 8, 1, true);
            if ($userUsdtAsset->balance < $needU) {
                DB::rollBack();
                return __return($this->errStatus, 'USDT 余额不足');
            }
            $isYu = AuctionYuLog::where(['uid' => $user->id, 'speed_card_id' => $auctionCard->id])->first();
            if ($isYu) {
                return __return($this->errStatus, '已预约');
            }

            $this->writeBalanceLog($userUsdtAsset, 0, -$needU, 34, '预约拍卖', 'book auction', $userUsdtAsset->pid, $userUsdtAsset->pname);
            AuctionYuLog::create([
                'uid'           => $user->id,
                'speed_card_id' => $auctionCard->id,
                'usdt_num'      => $needU,
                'is_reback'     => 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ]);
            $auctionCard = AuctionCard::where(['id' => $id])->lockForUpdate()->first();
            $auctionCard->yu_uids = $auctionCard->yu_uids . $user->id . ',';
            $auctionCard->save();

            DB::commit();
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return __return($this->errStatus, '操作失败');
        }
    }

    public function auctionDetail(Request $request)
    {
        $user = $request->user;
        $validator = validator($request->all(), [
            'id' => 'required|integer|gt:0'
        ]);
        if ($validator->fails()) {
            return __return($this->errStatus, '参数错误');
        }

        $id = $request->input('id');

        $auction = Redis::get('auction:' . $id);
        if (!$auction) {
            $auction = AuctionCard::where(['id' => $id])->first();
            $end = strtotime($auction->end_time) + 3600;
            $auction = json_encode($auction);
            Redis::set('auction:' . $id, $auction, 'EXAT', $end);
        }

        $data = [];

        $auctionObj = json_decode($auction);

        $curPrice = Redis::get('auction:' . $auctionObj->id . ':curprice');
        if ($curPrice) {
            $auctionObj->current_piao = $curPrice;
        }
        $data['auction'] = $auctionObj;

        $orders = Redis::zrevrangebyscore('auction:' . $id . ':users', '+inf', 0, ['withscores' => true, 'limit' => [0, 10]]);
        $users = [];
        foreach ($orders as $key => $score) {
            $userArr = explode(':', $key);
            $users[] = [
                'nickname' => $userArr[0],
                'avatar'   => $userArr[1],
                'cft_num'  => $score
            ];
        }
        $data['pais'] = $users;
        $userUsdtAsset = UserAssets::getBalance($user->id, 8);
        $userCftAsset = UserAssets::getBalance($user->id, 4);
        $data['user'] = [
            'nickname' => $user->nickname,
            'headimg'  => $user->avatar,
            'usdt'     => round($userUsdtAsset->balance, 6),
            'cft'      => round($userCftAsset->balance, 6)
        ];

        return __return($this->successStatus, '获取成功', $data);
    }



    // 拍卖
    public function auctionCard(Request $request)
    {
        $user = $request->user;

        if (!disablePoint(__METHOD__, $user)) {
            return __return($this->errStatus, '点击过快');
        }

        $validator = validator($request->all(), [
            'id'               => 'required|integer|gt:0',
            'cft_num'          => 'required|integer|gt:0',
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

        $id = $request->input('id', 0);
        $cftNum = $request->input('cft_num');

        $auction = Redis::get('auction:' . $id);
        if (!$auction) {
            $auction = AuctionCard::where(['id' => $id])->first();
            $end = strtotime($auction->end_time) + 3600;
            $auction = json_encode($auction);
            Redis::set('auction:' . $id, $auction, 'EXAT', $end);
        }

        $auctionObj = json_decode($auction);

        if ($auctionObj->status != 1) {
            return __return($this->errStatus, '未到达竞拍时间');
        }

        $now = time();
        if (strtotime($auctionObj->start_time) > $now) {
            return __return($this->errStatus, '未到达竞拍时间');
        }

        if (strtotime($auctionObj->end_time) < $now) {
            return __return($this->errStatus, '竞拍已结束,谢谢参与');
        }

        // $zsetKey = 'auction:' . $auctionObj->id . ':users';
        $member = $user->nickname . ':' . $user->avatar;
        // $score = Redis::zscore($zsetKey, $member);

        // if ($score) {
        //     return __return($this->errStatus, '已参与过竞拍');
        // }

        $curPrice = Redis::get('auction:' . $auctionObj->id . ':curprice');
        if (!$curPrice) {
            $curPrice = 0;
        } else {
            if ($cftNum <= $curPrice) {
                return __return($this->errStatus, '拍卖价过低');
            }
        }
        $userCftAsset = UserAssets::getBalance($user->id, 4);
        if ($userCftAsset->balance < $cftNum) {
            return __return($this->errStatus, '票余额不足');
        }

        try {
            $suoKey = 'auction:' . $id . 'suo';
            $flag = false;
            while (!$flag) {
                $flag = Redis::set($suoKey, 1, 'EX', 10, 'NX');
                if ($flag) {
                    // 抢到锁
                    $end = strtotime($auctionObj->end_time) + 3600;
                    Redis::set('auction:' . $auctionObj->id . ':curprice', $cftNum, 'EXAT', $end);
                    $score = floatval($cftNum);
                    Redis::zadd('auction:' . $auctionObj->id . ':users', [$member => $score]);

                    $queueJson = json_encode(['uid' => $user->id, 'cft_num' => $cftNum, 'speed_card_id' => $auctionObj->id]);
                    HandleAuctionOrder::dispatch($queueJson)->onQueue('auction');

                    Redis::del($suoKey);
                }
            }
            return __return($this->successStatus, '操作成功');
        } catch (\Exception $e) {
            Log::error($e);
            return __return($this->errStatus, '操作失败');
        }
    }


}