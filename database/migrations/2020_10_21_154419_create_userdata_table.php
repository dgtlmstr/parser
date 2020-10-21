<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserdataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('userdata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->default(0);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('card_number');
            $table->unsignedBigInteger('idr')->default(0);
            $table->index(['customer_id']);
            $table->index(['card_number']);
            $table->index(['idr']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('userdata');
    }
}
