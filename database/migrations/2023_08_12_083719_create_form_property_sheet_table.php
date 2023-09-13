<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormPropertySheetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_property_sheet', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 250)->nullable()->default('')->comment('表单名称');
            $table->string('notes', 250)->nullable()->default('')->comment('备注');
            $table->tinyInteger('type')->nullable()->default(0)->comment('类型');
            $table->tinyInteger('status')->nullable()->default(0)->comment('状态');

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
        Schema::dropIfExists('form_property_sheet');
    }
}
