<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('txn_id');
            $table->unsignedMediumInteger('user_id');
            $table->unsignedInteger('product_id');
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_price', 15, 4);
            $table->unsignedMediumInteger('unit_option_id');
            $table->string('txn_type', 30);
            $table->date('txn_date');
            $table->timestamps();

            $table->index('txn_id');
            $table->index(['user_id', 'product_id']);
        });

        DB::statement('ALTER TABLE transaction_items ALTER COLUMN txn_type TYPE txn_type_enum USING txn_type::txn_type_enum');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
