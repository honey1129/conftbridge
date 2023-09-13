<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class EntrustsSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'entrusts:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '委托转持仓(计划任务)';

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
            $this->entrustedToPositions();
        } catch (\Exception $exception) {
            Log::info('RedisSubscribe Faild' . $exception->getMessage());
        }
    }

    /**
     * 委托转持仓逻辑
     * @param $message
     */
    public function entrustedToPositions()
    {
        //最新币种价格

        // TODO 委托转持仓
        DB::table('user_entrusts')
          ->where('status', 1)
          ->chunkById(100, function ($entrusts)
          {
              foreach ($entrusts as $entrust) {
                  // 买入价大于买入时最新价，现在最新价大于等于买入价  --- 买涨
                  // 买入价小于买入时最新价，现在最新价小于等于买入价  --- 买跌
                  $newprice = Redis::get('vb:ticker:newprice:' . $entrust->code);
                  // if (($entrust->buyprice > $entrust->market_price && $newprice >= $entrust->buyprice) ||
                  //     ($entrust->buyprice < $entrust->market_price && $newprice <= $entrust->buyprice))
                  if (($entrust->otype == 1 && $newprice <= $entrust->buyprice) ||
                      ($entrust->otype == 2 && $newprice >= $entrust->buyprice)) {
                      //符合条件，转持仓

                      $queue = [
                          'newprice' => $newprice,
                          'entrust'  => $entrust
                      ];
                      try {
                          $server = Redis::connection('server');
                          $tid    = $server->lpush('entrusts_to_positions', json_encode($queue));
                          if ($tid === false) {
                              continue;
                          }
                      } catch (\Exception $exception) {
                          Log::info('chunkById Faild' . $exception->getMessage()/*. $exception->getL()*/);
                      }

                  }
              }
          });

    }

}
