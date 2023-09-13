<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//
//use Illuminate\Support\Facades\Route;

//不需要验证用户身份
Route::group(['namespace' => 'Api'], function ()
{
    //开发用测试接口
    Route::any('user/test', 'UserController@test');
    Route::any('get_market/{name}', function ($name)
    {
        $value = \Illuminate\Support\Facades\DB::table('xy_dayk_info')
            ->where('name', $name)
            ->orderBy('id', 'desc')
            ->value('closingPrice');
        return $value;
    });
    //检测
    Route::post('user/check', 'UserController@check');
    Route::any('user/test_login', 'UserController@test_login');
    Route::post('user/login', 'UserController@login');
    Route::post('user/register_user', 'UserController@register_user');
    Route::post('user/register', 'UserController@register');
    Route::post('user/sendSms', 'UserController@sendSms');
    Route::post('user/sendEmail', 'UserController@sendEmail');
    Route::post('user/forgetPassword', 'UserController@forgetPassword');
    #========行情部分
    //K线历史数据
    Route::get('ticket', 'TicketController@kline');

    Route::get('options/setCycle', 'OptionsController@setCycle');
    //行情列表
    Route::get('getPro', 'TicketController@getPro');
    //交易币种信息
    Route::get('starList', 'TicketController@starList');
    //主流币行情列表
    Route::get('getMast', 'TicketController@getMast');
    Route::get('options/realList', 'OptionsController@realList');
    Route::get('user/launchList', 'UserController@launchList');
    //盘口/深度
    Route::get('getDepth', 'TicketController@getDepth');
    //实时成交
    Route::get('RealTimeDeal', 'TicketController@RealTimeDeal');
    //获取汇率
    Route::get('getRate', 'TicketController@getRate');
    //获取固定汇率
    Route::get('getFixrate', 'TicketController@getFixrate');

    //获取币种信息简介
    Route::get('getProInfo', 'TicketController@getProInfo');
    //外调接口
    Route::get('getpros', 'IndexController@getpros');

    //获取币种价格
    Route::any('contract/codePrice', 'ContractController@codePrice');
    //处理REDIS
    Route::any('contract/addRedis', 'ContractController@addRedis');


    Route::get('software/emailAddress', 'SoftwareController@emailAddress');
    //获取轮播图
    Route::get('software/slides', 'SoftwareController@slides');
    //获取协议
    Route::get('software/systemAgree', 'SoftwareController@systemAgree');

    Route::get('software/courseList', 'SoftwareController@course_list');
    Route::get('software/courseInfo', 'SoftwareController@course_info');


    Route::get('software/systemPosts', 'SoftwareController@systemPosts'); //平台公告
    Route::get('software/postsInfo', 'SoftwareController@postsInfo'); //公告详情
    Route::get('software/feedBackType', 'SoftwareController@feedBackType'); //反馈问题类型
    Route::get('software/softwareUpdate', 'SoftwareController@softwareUpdate'); //版本更新
    Route::get('software/downloadLink', 'SoftwareController@downloadLink'); //下载地址
    //获取客服信息等
    Route::get('software/content', 'SoftwareController@content');
    //首页资讯
    Route::get('software/news', 'SoftwareController@news');


    //后台平仓接口
    Route::any('contract/sysClosePosition', 'ContractController@sysClosePosition');
    //zdd登陆之后进一步验证
    Route::post('user/yanzheng', 'UserController@yanzheng');
    //邀请码开关
    Route::post('user/ifyaoqingma', 'UserController@ifyaoqingma');
    //轮播图和首页公告
    Route::post('software/lunbogonggao', 'SoftwareController@lunbogonggao');
    Route::post('software/wenzhanglist', 'SoftwareController@wenzhanglist');
    //获取区号
    Route::get('software/quhao', 'SoftwareController@quhao');
    //未知接口获取了所有用户数据
//    Route::get('user/wanshan', 'UserController@wanshan');
    //验证手势密码
    Route::post('user/shoushi_check', 'UserController@shoushi_check');
    Route::get('user/shiyan', 'UserController@shiyan');

    Route::any('coin/getChainNetworkLst', [\App\Http\Controllers\Api\CoinController::class, 'chainNetworkLst']);

    Route::post('brand_side/register', 'UserInfoController@register');//品牌方注册
    Route::post('brand_side/send_code', 'UserInfoController@sendCode');//品牌方注册发送验证码
    Route::get('brand_side/get_user_info', 'UserInfoController@getUserInfo');//获取品牌方信息
    Route::get('brand_side/get_physical_product_info_list', 'UserInfoController@getPhysicalProductInfoList');//获取品牌方信息
    Route::get('brand_side/get_nft_invitation', 'UserInfoController@getNftInvitation');//获取品牌方信息
    Route::get('cyber_star/get_user_info', 'UserInfoController@getInternetCelebrity');//网红注册
    Route::get('member/get_user_info', 'UserInfoController@getMemberInfo');//获取会员主页信息

    /**
     * shop
     **/
    //product list
    Route::any('shop/list', 'Shop\ProductsController@list');

});

//auth.security
//需要验证用户access_token
Route::group(['namespace' => 'Api', 'middleware' => ['auth.security']], function ()
{
    //创建+绑定谷歌验证
    Route::post('user/createGoogleSecret', 'UserController@createGoogleSecret');
    Route::post('user/authenticatorBind', 'UserController@authenticatorBind');
    //开启、关闭谷歌验证
    Route::post('user/googleVerifyStart', 'UserController@googleVerifyStart');
    //重置登录密码
    Route::post('user/resetPassword', 'UserController@resetPassword');
    //创建资金密码
    Route::post('user/createPaymentPassword', 'UserController@createPaymentPassword');
    //重置资金密码
    Route::post('user/resetPaymentPassword', 'UserController@resetPaymentPassword');
    //初级认证
    Route::post('authentication/primaryCertification', 'AuthenticationController@primaryCertification');
    //高级认证
    Route::post('authentication/advancedCertification', 'AuthenticationController@advancedCertification');

    // 生成地址
    Route::get('user/generateAddress', 'UserController@generateAddress');
    Route::post('user/importAddress', 'UserController@importAddress');


    //首页信息
    Route::get('contract/getProduct', 'ContractController@getProduct');
    //用户钱包充值
    Route::post('recharge/walletRecharge', 'RechargeController@walletRecharge');

    Route::post('options/allClosePosition', 'OptionsController@allClosePosition');
    Route::post('options/closePosition', 'OptionsController@closePosition');

    //用户钱包充值
    Route::get('recharge/rechargeList', 'RechargeController@rechargeList');


    // 获取助记词
    Route::get('user/getWords', 'UserController@getWords');

    //添加提币地址
    Route::post('withdraw/createWithdrawAddress', 'ApplyWithdrawController@createWithdrawAddress');
    //提币地址列表
    Route::get('withdraw/getWithdrawAddress', 'ApplyWithdrawController@getWithdrawAddress');
    //用户提币
    Route::post('withdraw/applyWithdraw', 'ApplyWithdrawController@applyWithdraw');
    //删除提币地址
    Route::post('withdraw/deleteWithdrawAddress', 'ApplyWithdrawController@deleteWithdrawAddress');
    //合约交易下单
    Route::post('contract/createOrder', 'ContractController@createOrder');
    //私募交易下单
    Route::post('user/createOrder', 'UserController@createOrder');
    Route::get('user/slideShow', 'UserController@slideShow');
    //设置止盈止损
    Route::post('contract/setPoit', 'ContractController@setPoit');
    //撤单接口
    Route::post('contract/cancellations', 'ContractController@cancellations');
    //平仓接口
    Route::post('contract/closePosition', 'ContractController@closePosition');
    //一键全平仓
    Route::post('contract/allClosePosition', 'ContractController@allClosePosition');
    //分享合约状态图
    Route::get('contract/shareOrder', 'ContractController@shareOrder');

    //平仓信息统计
    Route::get('contract/orderList', 'ContractController@orderList');


    //法币交易 成为商家
    Route::post('shop/shopApply', 'ShopApplyController@shopApply');
    //撤销商家
    Route::get('shop/shopCancel', 'ShopApplyController@shopCancel');
    //求购/售出发单限额
    Route::get('shop/orderLimit', 'ShopApplyController@orderLimit');
    //求购/售出发单
    Route::post('shop/createBuyingOrder', 'ShopApplyController@createBuyingOrder');
    //求购/售出列表
    Route::get('shop/orderList', 'ShopApplyController@orderList');
    //求购/售出撤单
    Route::post('shop/cancelOrder', 'ShopApplyController@cancelOrder');
    //查看商家信息
    Route::get('shop/shopInfo', 'ShopApplyController@shopInfo');
    //添加/编辑提交 支付方式
    Route::post('shop/payAdd', 'ShopApplyController@payAdd');
    //支付方式编辑页数据
    Route::post('shop/payInfo', 'ShopApplyController@payInfo');
    //改变支付方式状态
    Route::post('shop/setPayStatus', 'ShopApplyController@setPayStatus');
    //支付方式列表
    Route::get('shop/payList', 'ShopApplyController@payList');
    //手续费佣金提到余额
    Route::get('assets/feeWithdrawInfo', 'UserAssetsController@feeWithdrawInfo');
    Route::post('assets/feeWithdraw', 'UserAssetsController@feeWithdraw');


    //上传
    Route::post('authentication/shangchuan', 'AuthenticationController@shangchuan');
    //开启手机验证
    Route::post('user/yzmobile', 'UserController@yzmobile');
    Route::post('user/nicheng', 'UserController@nicheng');
    //设置手势按钮
    Route::post('user/createshoushiPassword', 'UserController@createshoushiPassword');
    //手势按钮开关
    Route::post('user/shoushi_but', 'UserController@shoushi_but');


    // OTC
    Route::post('otc/fabu', 'OtcController@fabu');
    Route::get('otc/shichang_list', 'OtcController@shichang_list');
    Route::post('otc/ordersell', 'OtcController@ordersell');
    Route::get('otc/hyjiaoyi_list', 'OtcController@hyjiaoyi_list');
    Route::get('otc/myfabulist', 'OtcController@myfabulist');
    Route::get('otc/shjiaoyi_list', 'OtcController@shjiaoyi_list');
    Route::post('otc/chedan', 'OtcController@chedan');
    Route::get('otc/shiinfo', 'OtcController@shiinfo');

    /**
     * Shop
     */
    Route::post('shop/cart', 'Shop\CartController@add')->name('cart.add');
    Route::post('shop/orders', 'Shop\OrdersController@store')->name('orders.store');

});

Route::group(['namespace' => 'Api', 'middleware' => ['auth']], function ()
{
    //持仓/委托 数据接口
    Route::get('contract/transData', 'ContractController@transData');
    //持仓信息统计
    Route::get('contract/statistics', 'ContractController@statistics');

});
//需要验证用户access_token
Route::group(['namespace' => 'Api', 'middleware' => ['auth']], function ()
{

    //验证手机号或者邮箱是否注册
    Route::post('user/logout', 'UserController@logout');
    //登录日志
    Route::get('user/loginHistory', 'UserController@loginHistory');
    //用户信息
    Route::get('user/details', 'UserController@details');
    //检测账户余额
    Route::get('withdraw/checkBalance', 'ApplyWithdrawController@checkBalance');
    //我的客户
    Route::get('user/recommends', 'UserController@recommends');
    //推荐链接-APP
    Route::any('user/registerLink', 'UserController@registerLink');
    //推荐链接-PC
    Route::any('user/recommendLink', 'UserController@registerLink');
    //我的佣金
    Route::get('user/commissionDetails', 'UserController@commissionDetails');
    //获取推荐人数+佣金
    Route::get('user/recommendInfo', 'UserController@recommendInfo');
    Route::post('user/updateAvatar', 'UserController@updateAvatar');
    Route::post('user/phoneBind', 'UserController@phoneBind');
    Route::post('user/emailBind', 'UserController@emailBind');
    //用户资金明细
    Route::get('user/userMoneyLog', 'UserController@userMoneyLog');
    Route::get('user/userMoneyList', 'UserController@userMoneyList');

    Route::get('user/myLaunch', 'UserController@myLaunch');

    Route::get('user/releaseCfa', 'UserController@releaseCfa');

    Route::post('options/createOrder', 'OptionsController@createOrder');

    Route::get('options/orderList', 'OptionsController@orderList');

    Route::post('options/cancelOrder', 'OptionsController@cancelOrder');

    Route::post('software/feedBack', 'SoftwareController@feedBack'); //反馈问题
    Route::get('software/feedBackList', 'SoftwareController@feedBackList'); //反馈问题列表

    //用户在线充值（三方）
    Route::post('recharge/onlineRecharge', 'RechargeController@onlineRecharge');

    //用户充值记录
    Route::get('recharge/rechargeLog', 'RechargeController@rechargeLog');
    //充值记录
    Route::get('recharge/index', 'RechargeController@index');
    //资产信息
    Route::get('user/assetInfo', 'UserController@assetInfo');
    //获取单账户可以余额
    Route::get('user/assetInfoBalance', 'UserController@assetInfoBalance');
    //资产总览
    Route::get('user/assetInfoAll', 'UserController@assetInfoAll');
    //我的优惠券
    Route::get('user/myCoupons', 'UserAssetsController@myCoupons');

    //添加、编辑银行卡
    Route::post('user/userBankEdit', 'UserAssetsController@userBankEdit');
    //提币明细
    Route::get('withdraw/withdrawLog', 'ApplyWithdrawController@withdrawLog');

    Route::get('withdraw/withdrawList', 'ApplyWithdrawController@withdrawList');


    // 获取用户持币信息
    Route::get('user/getChiBiInfo', 'UserController@getUserChiInfo');
    // 公募
    // 处理公募
    Route::post('user/handleGongmu', 'GongMuController@handleGongmu');
    // 获取用户公募记录
    Route::get('user/gongmuList', 'GongMuController@getGongMuList');
    // 公募页获取数据
    Route::get('user/getGongmuInfo', 'GongMuController@getGongMuInfo');
    Route::get('user/chibilist', 'UserController@chibilist');
    Route::get('user/suanliinfo', 'UserController@suanliinfo');
    Route::get('user/jiedianinfo', 'UserController@jiedianinfo');
    Route::get('user/jiediansy', 'UserController@jiediansy');
    Route::get('user/jiedianchibi', 'UserController@jiedianchibi');
    Route::get('user/jiediansq', 'UserController@jiediansq');
    Route::get('user/jiediansqlist', 'UserController@jiediansqlist');
    Route::get('user/jiediansh', 'UserController@jiediansh');
    Route::get('user/tanchu', 'UserController@tanchu');
    Route::get('user/kuanggongsuanli', 'UserController@kuanggongsuanli');
    Route::post('user/jihuo', 'UserController@jihuo');
    Route::post('user/buytc', 'UserController@buytc');
    Route::get('user/goumailist', 'UserController@goumailist');
    Route::post('user/zhangben', 'UserController@zhangben');

    // 获取用户账户信息
    Route::get('user/getAssets', 'UserAssetsController@getAssets');
    // 添加子账户
    Route::post('user/addSubUser', 'UserController@addSubUser');
    // 切换子账户
    Route::post('user/switchSubAccount', 'UserController@switchSubAccount');
    // 获取子账户
    Route::get('user/getSubUsers', 'UserController@getSubUsers');


    #=========法币交易=========
    //交易大厅
    Route::get('trade/trading', 'FbTradeController@trading');
    //我的订单
    Route::get('trade/myOrderList', 'FbTradeController@myOrderList');
    //我的发布
    Route::get('trade/myTrade', 'FbTradeController@myTrade');
    //交易下单
    Route::post('trade/createOrder', 'FbTradeController@createOrder');
    //发布求购/售出交易
    Route::post('trade/createTrade', 'FbTradeController@createTrade');
    //订单详情
    Route::get('trade/orderDetail', 'FbTradeController@orderDetail');
    //标记已付款
    Route::post('trade/setOrderStatus', 'FbTradeController@setOrderStatus');
    //确认放行
    Route::post('trade/confirm', 'FbTradeController@confirm');
    //提交申诉
    Route::post('trade/appeal', 'FbTradeController@appeal');
    //取消申诉
    Route::post('trade/cancelAppeal', 'FbTradeController@cancelAppeal');
    //取消订单
    Route::post('trade/cancelOrder', 'FbTradeController@cancelOrder');
    //撤销发布求购/售出
    Route::post('trade/cancelTrade', 'FbTradeController@cancelTrade');


    #=========币币交易=========
    //下单
    Route::post('BbTrade/trans', 'TradeController@bbtran');
    //撤单
    Route::post('BbTrade/cancel', 'TradeController@cancel');
    //交易记录
    Route::get('BbTrade/tranList', 'TradeController@tranlist');
    //交易币种信息
    Route::get('BbTrade/getPro', 'TradeController@get_pro');
    //各币种详情
    Route::get('BbTrade/getCodeBalance', 'TradeController@getCodeBalance');
    //交易明细
    Route::get('BbTrade/tranLists', 'TradeController@tranLists');


    Route::post('user/devEcology', 'UserController@dev_ecology');


    Route::post('user/transfer', 'UserController@transfer');
    Route::get('user/transfer_list', 'UserController@transfer_list');
    Route::get('user/transfer_lists', 'UserController@transfer_lists');
    Route::get('user/release_list', 'UserController@release_list');
    Route::get('user/transferFee', 'UserController@transfer_fee');

    // 池子
    Route::get('pool/getPools', 'PoolController@getPools'); // 池子列表
    Route::get('pool/searchPool', 'PoolController@searchPool'); // 搜索池子
    Route::get('pool/myPools', 'PoolController@myPools'); // 我创建的池子
    Route::get('pool/myJoin', 'PoolController@myJoin'); // 我参与的池子

    Route::post('pool/createPool', 'PoolController@createPool'); // 创建池子
    Route::get('pool/getChildPools', 'PoolController@getChildPools'); // 获取子池
    Route::get('pool/getChildPoolDetail', 'PoolController@getChildPoolDetail'); // 获取子池
    Route::post('pool/joinPool', 'PoolController@joinPool'); // 加入池子
    Route::post('pool/exitPool', 'PoolController@exitPool'); // 退出池子
    Route::post('pool/openPool', 'PoolController@openPool'); // 点亮池子

    Route::post('pool/buyNode', 'PoolController@buyNode'); // 购买节点
    Route::get('pool/nodes', 'PoolController@nodes'); // 节点列表

    Route::get('pool/poolData', 'PoolController@poolData');

    // 首页子池列表
    Route::get('pool/getChildPool', 'PoolController@getChildPool');

    // 获取池子收益率
    Route::get('pool/getBalanceRate', 'PoolController@getBalanceRate');

    // 获取算力前21名
    Route::get('pool/highSuanData', 'PoolController@highSuanData');


    // 普通池子订单转CFA池订单
    Route::post('pool/joinCfaPool', 'PoolController@joinCfaPool');
    Route::post('pool/exitCfaPool', 'PoolController@exitCfaPool');
    // 获取CFA池订单
    Route::get('pool/cfaPoolOrder', 'PoolController@cfaPoolOrder');

    // 加速卡
    Route::get('card/cardList', 'SpeedCardController@cardList');




    Route::post('card/transferCard', 'SpeedCardController@transferCard');

    // 划转
    Route::post('asset/transfer', 'UserAssetsController@transfer');

    // gpt
    Route::get('gpt/getInfo', 'ChatGptController@gpt');
    Route::get('gpt/strGptGetImage', 'ChatGptController@strGptGetImage');
    Route::get('gpt/chat', 'ChatGptController@chat');
    Route::get('gpt/chatList', 'ChatGptController@chatList');

    Route::get('user/myTeam', 'UserController@my_team');

    // 拍卖
    // 拍卖列表
    Route::get('auction/auctionlist', 'SpeedCardController@auctionList');
    // 预约拍卖
    Route::post('auction/auctionYu', 'SpeedCardController@auctionYu');
    // 拍卖详情
    Route::get('auction/auctionDetail', 'SpeedCardController@auctionDetail');
    // 拍卖
    Route::post('auction/auctionCard', 'SpeedCardController@auctionCard');

    Route::get('user/teamNode', 'UserController@teamNodes');
    Route::get('user/myRecommends', 'UserController@myRecommends');
});
//需要验证用户access_token
Route::group(['namespace' => 'Api', 'middleware' => ['auth']], function ()
{
    //获取币种
    Route::any('coin/getCoinLst', [\App\Http\Controllers\Api\CoinController::class, 'coinLst']);
    //币种获取网络
    Route::any('coin/getChainLst', [\App\Http\Controllers\Api\CoinController::class, 'chainLst']);
    //支持主网列表
    //Route::any('coin/getChainNetworkLst', [\App\Http\Controllers\Api\CoinController::class, 'chainNetworkLst']);

});
