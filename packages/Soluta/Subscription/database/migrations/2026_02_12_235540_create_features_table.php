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
        Schema::create('features', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('name');
            $table->boolean('consumable')->default(true);
            $table->boolean('quota')->default(false);
            $table->boolean('postpaid')->default(false);
            $table->integer('periodicity')->unsigned()->nullable();
            $table->string('periodicity_type')->nullable();
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
        Schema::dropIfExists('features');
    }
};
