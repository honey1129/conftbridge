<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function ()
{
    if (\App\Service\Tools::isMobile()) {
        return redirect('/h5/index.html');
    } else {
        return redirect('/h5/index.html');
        // return redirect('/web/index.html');
    }
});


//充值回掉
Route::any('notify/walletPay', 'NotifyController@zzWalletPay');