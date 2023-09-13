<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\ChildPool;
use App\Models\Nodes;
use App\Models\SystemValue;
use App\Models\UserAssets;
use App\Models\AssetRelease;
use App\Models\UserPoolOrder;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class CfaHighNode extends Command
{
    use WriteUserMoneyLog;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfa_high_node';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cfa 分10% 手续费 高级节点 7 天一分';

    protected $configs;
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
        try {
            // $totalHighNodeBalance = SystemValue::where(['name' => 'high_node_balance'])->value('value');
            $pool4 = User::where(['email' => 'pool4@qq.com'])->first();
            $pool4Balance = UserAssets::getBalance($pool4->id, 3);
            $totalHighNodeBalance = $pool4Balance->balance;

            $highNodeNum = User::where(['node_level' => 3])->count();
            if ($highNodeNum > 0) {
                $highNodeBalance = number_format($totalHighNodeBalance / $highNodeNum, 6, '.', '');
                User::where(['node_level' => 3])->chunkById(100, function ($users) use ($highNodeBalance, $pool4)
                {
                    foreach ($users as $user) {
                        DB::beginTransaction();
                        if ($user->static_status) {
                            $pool4Balance = UserAssets::getBalance($pool4->id, 3, 1, true);
                            $this->writeBalanceLog($pool4Balance, 0, -$highNodeBalance, 26, $user->email . '高级节点收益', $user->email . '高级节点收益', $pool4Balance->pid, $pool4Balance->pname);

                            $userAssets = UserAssets::getBalance($user->id, 3, 1, true);
                            dump('高級節點收益：' . $highNodeBalance);
                            $this->writeBalanceLog($userAssets, 0, $highNodeBalance, 30, '高级节点收益', 'high node balance', $userAssets->pid, $userAssets->pname);
                            DB::commit();
                        } else {
                            DB::rollBack();
                        }
                    }
                });
            }



            // 中低级节点按算力加权分
            $pool5 = User::where(['email' => 'pool5@qq.com'])->first();
            $pool5Balance = UserAssets::getBalance($pool5->id, 3);
            $totalMidNodeBalance = $pool5Balance->balance;

            $midNodes = User::where(['node_level' => 2])->get();
            $smallNodes = User::where(['node_level' => 1])->get();

            $midNum = $midNodes->count();
            $smallNum = $smallNodes->count();

            // $midNode = Nodes::where(['id' => 2])->first();
            // $smallNode = Nodes::where(['id' => 1])->first();
            // $total = $midNum * $midNode->song_suan + $smallNum * $smallNode->song_suan;
            $midTotalBalance = round($totalMidNodeBalance * 2 / 3, 6);
            $smallTotalBalance = round($totalMidNodeBalance * 1 / 3, 6);
            if ($midNum > 0) {
                $midBalance = number_format($midTotalBalance / $midNum, 6, '.', '');
                dump($midBalance);
                foreach ($midNodes as $midUser) {
                    dump($midUser->email);
                    DB::beginTransaction();
                    $pool5Balance = UserAssets::getBalance($pool5->id, 3, 1, true);
                    $this->writeBalanceLog($pool5Balance, 0, -$midBalance, 26, $midUser->email . '中级节点收益', $midUser->email . '中级节点收益', $pool5Balance->pid, $pool5Balance->pname);


                    $midUserAsset = UserAssets::getBalance($midUser->id, 3, 1, true);
                    $this->writeBalanceLog($midUserAsset, 0, $midBalance, 31, '中低节点收益', '中低节点收益', $midUserAsset->pid, $midUserAsset->pname);
                    DB::commit();
                }
            }

            if ($smallNum > 0) {
                $smallBalance = number_format($smallTotalBalance / $smallNum, 6, '.', '');
                dump($smallBalance);
                foreach ($smallNodes as $smallUser) {
                    dump($smallUser->email);
                    DB::beginTransaction();
                    $pool5Balance = UserAssets::getBalance($pool5->id, 3, 1, true);
                    $this->writeBalanceLog($pool5Balance, 0, -$smallBalance, 26, $midUser->email . '低级节点收益', $midUser->email . '低级节点收益', $pool5Balance->pid, $pool5Balance->pname);

                    $smallUserAsset = UserAssets::getBalance($smallUser->id, 3, 1, true);
                    $this->writeBalanceLog($smallUserAsset, 0, $smallBalance, 31, '中低节点收益', '中低节点收益', $smallUserAsset->pid, $smallUserAsset->pname);
                    DB::commit();
                }
            }



            // SystemValue::where(['name' => 'high_node_balance'])->update(['value' => 0]);

            Log::info('command cfa_high_node');
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            Log::info('cfa_high_node Faild' . $exception->getMessage());
        }
    }
}