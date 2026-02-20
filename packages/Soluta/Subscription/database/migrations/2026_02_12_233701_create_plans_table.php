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
        Schema::create('plans', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->integer('grace_days')->default(0);
            $table->string('name');
            $table->integer('periodicity')->unsigned()->nullable();
            $table->string('periodicity_type')->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('price')->comment('usd');
            $table->boolean('is_free')->default(false);
            $table->softDeletes();
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
        Schema::dropIfExists('plans');
    }
};
