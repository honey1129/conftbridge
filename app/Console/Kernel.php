<?php

namespace App\Console;

use App\Console\Commands\CfaDeleteMasterPool;
use App\Console\Commands\CfaFenFee;
use App\Console\Commands\CfaFenHong;
use App\Console\Commands\CfaHighNode;
use App\Console\Commands\CfaOtcLine;
use App\Console\Commands\CfaPoolMaster;
use App\Console\Commands\CfaPoolRecommend;
use App\Console\Commands\CfaPoolUp;
use App\Console\Commands\CfaStartPai;
use App\Console\Commands\CfaUpdateUserLevel;
use App\Console\Commands\CfaWeekSuanLi;
use App\Console\Commands\Market\Ok\HistoryKline;
use Carbon\Carbon;
use App\Console\Commands\CfaEndPai;
use App\Console\Commands\CfaRebake;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
            ############# 交易所定时任务 #################
            // \App\Console\Commands\DeleteSubscribe::class,
            // \App\Console\Commands\CashSubscribe::class,
            // \App\Console\Commands\PositionsSubscribe::class,
            // \App\Console\Commands\OrderListSubscribe::class,
            // \App\Console\Commands\TradeSubscribe::class,
            // \App\Console\Commands\ChangeSubscribe::class,
            // \App\Console\Commands\OptionsSubscribe::class,
            // \App\Console\Commands\EntrustsSubscribe::class,
            // // \App\Console\Commands\MakeSecondInfo::class,
            // \App\Console\Commands\Newada::class,
            // HistoryKline::class,
            // \App\Console\Commands\Market\Week::class,
            // \App\Console\Commands\Market\Month::class,
            // //        \App\Console\Commands\Newadahq::class,
            // \App\Console\Commands\ProfitReturn::class



            ###################### CFA ######################



        \App\Console\Commands\CfaCalcDirNum::class, // 计算用户直推，个人业绩，团队业绩
        \App\Console\Commands\CfaReleaseCoin::class, // 释放CFA
        \App\Console\Commands\CfaStatic::class, // 静态收益
        \App\Console\Commands\CfaUpdateUserLevel::class, // 更新用户等级

        CfaPoolMaster::class, // 池主10%收益
        CfaFenFee::class, // 10% 手续费
        CfaHighNode::class, // 10%手续费高级节点周释放
        CfaFenHong::class, // cfa 分红
        CfaWeekSuanLi::class, // cfa 释放，超级节点每周算力前21名
        CfaPoolRecommend::class, // 池主推池主收益

            ##### 合成池相关 ######
        CfaPoolUp::class, // 生成合成池
        CfaDeleteMasterPool::class, // 合成CFA池后，主池清空后，删除

            ####### 拍卖  #######
        CfaStartPai::class,
        CfaEndPai::class,

            ### OTC ####
        CfaOtcLine::class,

        CfaRebake::class

    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //         //释放矿池数据
//         $schedule->command('cash:subscribe')->daily()->withoutOverlapping();
//         //清除数据数据
// //        $schedule->command('clearData')->daily()->withoutOverlapping();
//         //限价合约成交代码
//         $schedule->command('entrusts:subscribe')->cron('* * * * *')->withoutOverlapping();
//         //合约止盈止损
//         $schedule->command('positions:subscribe')->cron('* * * * *')->withoutOverlapping();
//         //币币交易成交
//         $schedule->command('order_list:subscribe')->cron('* * * * *')->withoutOverlapping();
//         //检查资产完整性
//         $schedule->command('change:subscribe')->cron('* * * * *')->withoutOverlapping();
//         //期权平仓
//         $schedule->command('options:subscribe')->cron('* * * * *')->withoutOverlapping();
//         //期权机器人下单
//         $schedule->command('trade:subscribe')->cron('* * * * *')->withoutOverlapping();
//         //持仓过夜费
// //        $schedule->command('positions:Overnight')->dailyAt('00:01')->withoutOverlapping();
//         //自定义币k线历史数据维护
//         $schedule->command('Newada')->cron('* * * * *')->withoutOverlapping();
//         //自定义币k线历史数据维护OK
//         $schedule->command('Market:OkHistoryKline')->cron('* * * * *')->withoutOverlapping(); //
//         //自定义币周线维护
//         $schedule->command('Market:week')->cron('* * * * *')->withoutOverlapping();
//         //自定义币月线维护
//         $schedule->command('Market:month')->cron('* * * * *')->withoutOverlapping(); //
// //        //利润回报推荐分佣
// //        $schedule->command('profit:return')->cron('* * * * *')->withoutOverlapping();  //
//         //自定义币交易数据//废弃
// //        $schedule->command('Newadahq')->cron('* * * * *')->withoutOverlapping();//



        // 计算用户直推、个人业绩、团队业绩
        $schedule->command('cfa_calc_dir_num')->everyFiveMinutes()->withoutOverlapping(); // clear
        // 更新用户等级
        $schedule->command('cfa_update_user_level')->dailyAt('00:00')->withoutOverlapping(); // clear
        // 释放CFA
        // $schedule->command('cfa_release_coin')->dailyAt('00:05')->withoutOverlapping(); // clear
        // 静态收益，等级收益，直推收益
        $schedule->command('cfa_static')->dailyAt('00:10')->withoutOverlapping(); // clear

        // 池主10%收益
        $schedule->command('cfa_pool_master')->dailyAt('00:15')->withoutOverlapping(); // clear
        // 池主池员收益
        $schedule->command('cfa_fen_fee')->dailyAt('00:20')->withoutOverlapping(); // clear
        // 提现手续费，高级节点周释放, 每周一1点
        $schedule->command('cfa_high_node')->weeklyOn(1, '1:00')->withoutOverlapping(); // clear

        // 全网分红 用到全网日收益, 每周一1点
        $schedule->command('cfa_fen_hong')->weeklyOn(1, '1:15')->withoutOverlapping(); // clear

        // CFA释放，算力前21名周结算
        $schedule->command('cfa_week_suan_li')->weeklyOn(1, '1:20')->withoutOverlapping(); // clear

        // 池主推池主
        $schedule->command('cfa_pool_recommend')->dailyAt('00:30')->withoutOverlapping(); // clear

        // 自动合成CFA池
        // $schedule->command('cfa_pool_up')->dailyAt('00:40')->withoutOverlapping();

        // 主池合成CFA池后自动销毁
        // $schedule->command('cfa_delete_master_pool')->dailyAt('00:50')->withoutOverlapping();

        // OTC画折线图
        $schedule->command('cfa_otc_line')->dailyAt('00:02')->withoutOverlapping();

        // 

        // 退出池子返还质押金额（暫時不用）
        // $schedule->command('cfa_rebake')->everyTenMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}