<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpeedCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('speed_card', function (Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->string('card_image', 250)->default('')->comment('加速卡封面');
            $table->string('card_name', 50)->default('')->comment('加速卡名称');
            $table->decimal('price')->default(0)->comment('价格(U)');
            $table->decimal('begin_piao')->default(0)->comment('起拍价(票)');
            $table->integer('period')->default(0)->unsigned()->comment('可用天数');
            $table->tinyInteger('type')->default(0)->unsigned()->comment('1 1号池加速卡  2 2号池加速卡  3 3号池加速卡 4 4号池加速卡 5 5号池加速卡 6 6号池加速卡 7 个人加速卡 8 池主加速卡');
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
        Schema::dropIfExists('speed_card');
    }
}