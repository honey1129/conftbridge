<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZzdZoushiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zzd_zoushi', function (Blueprint $table)
        {
            $table->bigIncrements('id');
            $table->decimal('price')->default(0);
            $table->decimal('max_price')->default(0);
            $table->decimal('min_price')->default(0);
            $table->string('date')->default('');
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
        Schema::dropIfExists('zzd_zoushi');
    }
}