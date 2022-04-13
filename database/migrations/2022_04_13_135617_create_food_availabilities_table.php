<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFoodAvailabilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('food_availabilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('food_id');
            $table->foreign('food_id')->references('id')->on('foods');
            $table->foreignId('food_id')
                ->constrained()
                ->onDelete('cascade');
            $table->json('working_days');
            $table->integer('working_hours');
            $table->integer('prepare_time');
            $table->integer('max_orders');
            $table->integer('min_orders');
            $table->integer('daily_orders');
            $table->boolean('food_type');
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
        Schema::dropIfExists('food_availabilities');
    }
}
