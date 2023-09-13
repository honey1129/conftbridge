<?php

namespace App\Console\Commands;

use App\Http\Traits\WriteUserMoneyLog;
use App\Models\Products;
use App\Models\UserAssets;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ChangeSubscribe extends Command
{
    use WriteUserMoneyLog;
    /**
     * 检查余额是否完整
     *
     * @var string
     */
    protected $signature = 'change:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查资产完整性（暂停使用）';

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
        die();
        try {
            $product_list = Products::where(['type'=>2,'state'=>1])->select('pid','code','pname','actprice')->get();
            if($product_list)
            {
                foreach ($product_list as $key =>$val)
                {
                    DB::update('update user_assets set frost=0,balance=balance+frost where pid=? and ptype=? and frost>?',[$val['pid'],2,0]);
                }
            }
        } catch (\Exception $exception) {
            Log::info('static Faild' . $exception->getMessage());
        }
    }
}
