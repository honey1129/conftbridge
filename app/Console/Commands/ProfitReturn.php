<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Http\Traits\WriteAgentMoneyLog;
use App\Models\AgentAssets;
use App\Models\AgentUser;
use App\Models\FeeRebates;
use App\Models\ProfitRebates;
use App\Models\UserAssets;
use App\Models\UserTrans;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProfitReturn extends Command
{
    use WriteUserMoneyLog,WriteAgentMoneyLog;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profit:return';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '利润回报推荐分佣//未使用';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Profit Return');
        try {
            $members = Redis::smembers('profit');
            if(empty($members)) return;

            foreach ($members as $member) {
                Redis::srem('profit',$member);

                //数据转换为数组 balance trans_id
                $data = json_decode($member,true);

                //查询是否存在记录
                $tran = UserTrans::where('id',$data['trans_id'])
                    ->where('distribute_income',0)
                    ->first();

                if(empty($tran)) {
                    Log::info('Profit Return $member-'.$member.'-empty');
                    continue;
                }

                //发放收益
                $tran->distribute_income = 1;
                $tran->save();

                $user = User::find($tran->uid);
                if(empty($user)){
                    Log::info('Profit Return $tran->uid'.$tran->uid.'-empty');
                    continue;
                }
                $user_asset = UserAssets::getBalance($user->id);

                //解冻赠送的usdt
                $send_money = DB::table('user_send')
                    ->where('uid',$user->id)
                    ->where('type',1)
                    ->where('used',0)
                    ->value('money');
                if ($send_money > 0) {
                    DB::table('user_send')
                        ->where('uid',$user->id)
                        ->where('type',1)
                        ->where('used',0)
                        ->update([
                            'used' => 1,
                            'updated_at' => now(),
                            'freed_at' => now()
                        ]);

                    $this->writeBalanceLog($user_asset,$tran->id,'USDT',$send_money,14,'解凍贈送的USDT');
                    $this->writeFrostLog($user_asset,$tran->id,'USDT',$send_money * (-1),14,'解凍贈送的USDT');
                }

                //增加盈亏
                $asset = UserAssets::getBalance($user->id);
                $asset->profit_and_loss += $tran->profit;
                $asset->total_fee += $tran->fee;
                $asset->save();

                //TODO 推荐返佣
                $this->recommendReturn($tran,$user);

                //TODO 自身返佣
                $balance = $data['balance'];
                $this->selfReturn($tran,$user,$balance);

            }

        } catch (\Exception $e){
            Log::info('Profit Return'.$e->getMessage().$e->getLine());
        }

    }

    /**
     * 手续费推荐返佣
     * @param $tran
     * @param $user
     */
    public function recommendReturn($tran,$user){
        $recommend_yongjin = $tran->fee * (0.01);
        $recommend =User::find($user->recommend_id);
        if(empty($recommend)){
            Log::info($user->account.'推荐人不存在');
            return;
        }

        $recommend_ratio = DB::table('admin_config')->where('name','commission.recommend_rate')->value('value');

        if($recommend_ratio <= 0){
            Log::info($recommend->account.'推荐人返佣比例 <= 0');
            return;
        }

        $recommend_fee = $recommend_yongjin * $recommend_ratio;

        if($recommend_fee <= 0){
            return;
        }

        $recommend_asset = UserAssets::getBalance($user->recommend_id);

        $recommend_asset->total_commission += $recommend_fee;
        $recommend_asset->save();

        $this->writeBalanceLog($recommend_asset,$tran->id,'USDT',$recommend_fee,6,'推薦人返佣');

        FeeRebates::create([
            'recommend_id' => $user->recommend_id,
            'from_uid' => $user->id,
            'fee' => $tran->fee,
            'recommend_yongjin' => $recommend_fee,
            'memo' => '推薦返佣',
            'type' => FeeRebates::FEE_RECOMMEND,
        ]);

    }

    /**
     * 手续费自身返佣
     * @param $tran
     * @param $user
     */
    public function selfReturn($tran,$user,$balance){
        $self_yongjin = $tran->fee * (0.01);

        $self_ratio = $this->getUserRatio($balance);
        if($self_ratio <= 0){
            Log::info($user->account.'自身返佣比例 <= 0');
            return;
        }

        $self_fee = $self_yongjin * $self_ratio;

        if($self_fee <= 0){
            return;
        }

        $self_asset = UserAssets::getBalance($user->id);
        $self_asset->total_commission += $self_fee;
        $self_asset->save();

        $this->writeBalanceLog($self_asset,$tran->id,'USDT',$self_fee,15,'自身返佣');

        FeeRebates::create([
            'recommend_id' => $user->id,
            'from_uid' => $user->id,
            'fee' => $tran->fee,
            'recommend_yongjin' => $self_fee,
            'memo' => '自身返佣',
            'type' => FeeRebates::FEE_SELF
        ]);

    }


    /**
     * 自身返佣比例
     *
     * @param $msg
     */
    public function getUserRatio($balance)
    {
        $self_asset = $balance;//UserAssets::getBalance($user->id);

        $conf = $this->getConf();

        $self_ratio = 0;

        if ($self_asset > $conf['commission.v1_dispose']['0']) {
            $self_ratio = $conf['commission.v1_dispose']['2'];
        }

        if ($self_asset > $conf['commission.v2_dispose']['0']) {
            $self_ratio = $conf['commission.v2_dispose']['2'];
        }

        if ($self_asset > $conf['commission.v3_dispose']['0']) {
            $self_ratio = $conf['commission.v3_dispose']['2'];
        }

        if ($self_asset > $conf['commission.v4_dispose']['0']) {
            $self_ratio = $conf['commission.v4_dispose']['2'];
        }

        if ($self_asset > $conf['commission.v5_dispose']['0']) {
            $self_ratio = $conf['commission.v5_dispose']['2'];
        }

        return $self_ratio;

    }

    public function getConf(){

        $res = DB::table('admin_config')->where('name', 'like', 'commission.v%')->pluck('value','name');

        $data = [];
        foreach ($res as $k => $v){
            $data[$k] = explode(',',$v);
        }
        return $data;

    }

}
