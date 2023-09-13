<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZzdBuy1Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zzd_buy1', function (Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->string('order_sn', '125')->default('')->comment('订单编号');
            $table->tinyInteger('leixing')->default(0)->comment('1买2卖');
            $table->tinyInteger('type')->default(0)->comment('1门票');
            $table->bigInteger('user_id')->default(0)->comment('用户ID')->index('idx_user_id');
            $table->bigInteger('sh_id')->default(0)->comment('商户ID')->index('idx_sh_id');
            $table->bigInteger('sc_id')->default(0)->comment('市场ID')->index('idx_sc_id');
            $table->integer('num')->default(0)->unsigned()->comment('交易数量');
            $table->decimal('shouxufei')->default(0)->unsigned()->comment('手续费');
            $table->decimal('money')->default(0)->unsigned()->comment('交易金额usdt');
            $table->tinyInteger('status')->default(0)->unsigned()->comment('0 交易中 1 交易完成');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zzd_buy1');
    }
}