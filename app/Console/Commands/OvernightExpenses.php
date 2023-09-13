<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\UserAssets;
use App\Models\UserPositions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OvernightExpenses extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'positions:Overnight';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '持仓过夜费(未启用)';

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
        $gljrate   = config('trans.daily_interest');
        DB::table('user_positions')
            ->chunkById(100, function ($orders) use ($gljrate) {
                foreach ($orders as $order) {
                    $bouns = $order->buyprice * $order->buynum * $gljrate * 0.01;
                    $userBalance = UserAssets::getBalance($order->uid, true);
                    if ($bouns > 0 && $userBalance->balance > $bouns) {
                        DB::beginTransaction();
                        $userBalance->total_interest -= $bouns;
                        $userBalance->save();
                        $dec = $this->writeBalanceLog($userBalance, $order->id,  -$bouns, 6,6, 6);
                        $inc = DB::table('user_positions')->where(['id' => $order->id])->increment('dayfee', $bouns);
                        if ($dec && $inc) {
                            DB::commit();
                        } else {
                            DB::rollBack();
                        }
                    }
                }
            });
    }

}
