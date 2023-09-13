<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSocialProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_social_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID')->index();
            $table->string('social_id')->default('')->comment('社交账户唯一ID');
            $table->string('social_type')->default('')->comment('社交登录类型，例如：Facebook、Google、Twitter等');
            $table->text('access_token')->nullable()->default('')->comment('访问令牌');
            $table->integer('expires_in')->nullable()->default(0)->comment('过期时间');
            $table->text('refresh_token')->nullable()->default('')->comment('刷新令牌');
            $table->integer('refresh_expires_in')->nullable()->default(0)->comment('过期时间');
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
        Schema::dropIfExists('user_social_profiles');
    }
}
