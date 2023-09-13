<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormHeaderTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_header_table', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 250)->nullable()->default('')->comment('表单名称');
            $table->string('property', 250)->nullable()->default('')->comment('属性名');
            $table->tinyInteger('property_type')->nullable()->default(0)->comment('属性类型');
            $table->integer('form_property_id')->nullable()->comment('属性表id');
            $table->tinyInteger('input_type')->nullable()->default(0)->comment('输入方式');
            $table->integer('sort')->nullable()->default(0)->comment('排序');
            $table->json('options')->nullable()->default(null)->comment('选项');

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
        Schema::dropIfExists('form_header_table');
    }
}
