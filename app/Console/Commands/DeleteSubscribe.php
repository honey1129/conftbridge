<?php

namespace App\Console\Commands;
use App\Models\FifteenMinInfo;
use App\Models\FiveMinInfo;
use App\Models\MarketInfo;
use App\Models\MinInfo;
use App\Models\RealTime;
use App\Models\SecondInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class DeleteSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除数据//未使用';

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
            $hour = date('H');
            if($hour==0)
            {
                SecondInfo::truncate();
                $marketinfo = MarketInfo::where('pid',9)->first();
                $realtime = RealTime::where('pid',9)->orderBy('id','desc')->first();
                if($realtime->price>$marketinfo->price)
                {
                    MarketInfo::where('pid',9)->update(['price'=>$realtime->price]);
                }
                $one_time =  strtotime('-3 days');
                $five_time = strtotime('-5 days');
                $fifteen_time = strtotime('-10 days');
                MinInfo::where('timestamp','<=',$one_time)->delete();
                FiveMinInfo::where('timestamp','<=',$five_time)->delete();
                FifteenMinInfo::where('timestamp','<=',$fifteen_time)->delete();
            }
        } catch (\Exception $exception) {
            Log::info('destroy Faild' . $exception->getMessage());
        }
    }
}
