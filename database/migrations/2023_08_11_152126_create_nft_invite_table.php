<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNftInviteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nft_invite', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID')->index();
            $table->unsignedBigInteger('brand_side_id')->default(0)->comment('品牌方ID')->index();
            $table->string('country', 250)->nullable()->default('')->comment('国家');
            $table->string('commercial_labels', 250)->nullable()->default('')->comment('商业标签');
            $table->integer('due_time')->nullable()->comment('邀请到期日');
            $table->string('leave_a_message', 250)->nullable()->default('')->comment('留言');
            $table->tinyInteger('status')->nullable()->default(0)->comment('邀请状态');

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
        Schema::dropIfExists('nft_info');
    }
}
