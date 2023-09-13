<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleTypeToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('role_type')->nullable()->default(0)->comment('用户类型0普通用户，1品牌方');
            $table->string('official_website', 250)->nullable()->default('')->comment('官网');
            $table->text('blurb')->nullable()->comment('简介');
            $table->tinyInteger('product_type')->nullable()->default(0)->comment('产品类型0');
            $table->string('target_market', 250)->nullable()->default('')->comment('目标市场');
            $table->string('industry_type')->nullable()->default(0)->comment('行业类型');






        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::table('users', function (Blueprint $table) {
//            $table->dropColumn('role_type');
//        });
    }
}
