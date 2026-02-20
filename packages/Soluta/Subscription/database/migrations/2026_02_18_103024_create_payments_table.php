<?php

use App\Enums\PaymentStatus;
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
        $statuses = getEnumsForDB(PaymentStatus::values());

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedMediumInteger('user_id')->index();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3);
            $table->unsignedTinyInteger('plan_id')->nullable();
            $table->unsignedSmallInteger('gateway_id');
            $table->string('gateway_txn_id')->nullable();
            $table->string('gateway_status')->nullable();
            $table->string('status', 20)->index();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('subscription_id')->nullable()->index();
            $table->timestamps();
        });

        DB::transaction(function () use ($statuses) {
            /* Drop previous enum */
            DB::statement('DROP TYPE IF EXISTS payment_status_enum');
            DB::statement("CREATE TYPE payment_status_enum AS ENUM ($statuses)");
            DB::statement('ALTER TABLE payments ALTER COLUMN status TYPE payment_status_enum USING status::payment_status_enum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        DB::statement('DROP TYPE IF EXISTS payment_status_enum');
    }
};
