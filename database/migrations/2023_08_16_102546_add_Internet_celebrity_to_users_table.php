<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternetCelebrityToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
           $table->tinyInteger('whole_fans_num')->nullable()->default(0)->comment('全网粉丝量');
            $table->json('commercial_labels', 250)->nullable()->default('')->comment('商业标签');
            $table->string('country', 250)->nullable()->default('')->comment('国家');





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
