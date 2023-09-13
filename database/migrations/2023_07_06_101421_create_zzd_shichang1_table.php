<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZzdShichang1Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zzd_shichang1', function (Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID')->index();
            $table->unsignedInteger('zongnum')->default(0)->comment('总数量');
            $table->unsignedInteger('dongjie')->default(0)->comment('交易中数量');
            $table->unsignedInteger('num1')->default(0)->comment('已购数量');
            $table->unsignedInteger('num2')->default(0)->comment('剩余数量');
            $table->decimal('money')->default(0)->comment('设定价格');
            $table->integer('money1')->default(0)->comment('交易最低数量');
            $table->integer('money2')->default(0)->comment('交易最高数量');
            $table->tinyInteger('leixing')->default(0)->comment('1售卖 2收购')->index();
            $table->tinyInteger('type')->default(0)->comment('资产类型')->index();
            $table->tinyInteger('is_show')->default(1)->comment('0下架 1上架')->index();
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
        Schema::dropIfExists('zzd_shichang1');
    }
}