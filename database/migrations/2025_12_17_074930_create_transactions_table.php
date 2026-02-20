<?php

use App\Enums\TxnType;
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
        // To wrap type with quotation eg. 'type'
        $txnTypes = getEnumsForDB(TxnType::values());

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('user_id');
            $table->unsignedInteger('contact_id')->nullable();
            $table->string('txn_type', 30);
            $table->dateTime('txn_date');
            $table->decimal('total', 15, 4);
            $table->decimal('due', 15, 4);
            $table->boolean('is_due');
            $table->boolean('is_fixed_discount');
            $table->decimal('discount_value', 15, 4);
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('currency_id');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'txn_type', 'txn_date']);
            $table->index(['user_id', 'contact_id', 'is_due']);
        });

        DB::transaction(function () use ($txnTypes) {
            /* Drop previous enum */
            DB::statement('DROP TYPE IF EXISTS txn_type_enum');
            DB::statement("CREATE TYPE txn_type_enum AS ENUM ($txnTypes)");
            DB::statement('ALTER TABLE transactions ALTER COLUMN txn_type TYPE txn_type_enum USING txn_type::txn_type_enum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        DB::statement('DROP TYPE IF EXISTS txn_type_enum');
    }
};
