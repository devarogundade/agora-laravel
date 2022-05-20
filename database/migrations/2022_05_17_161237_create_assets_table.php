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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('No name');
            $table->string('type');
            $table->integer('unit'); # unit contained
            $table->integer('price'); # price per unit unit per 24hr
            $table->longText('about');
            $table->timestamp('verified_at')->nullable();
            $table->longText('location'); # a descriptive address
            $table->string('state');
            $table->longText('metadata'); # json of attributes
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
        Schema::dropIfExists('assets');
    }
};
