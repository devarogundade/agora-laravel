<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->integer('duration'); # in days
            $table->integer('unit');
            $table->integer('price');
            $table->string('status')->default('pending');
            $table->string('stage')->default('clearing');
            # read doc at https://dev-arogundade.gitbook.io/agoralease/
            # to know more about offer stage
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('user_id');
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
        Schema::dropIfExists('offers');
    }
};
