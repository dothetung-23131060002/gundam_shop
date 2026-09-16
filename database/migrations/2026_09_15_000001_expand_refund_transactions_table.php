<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Strategy: create new table with correct schema, copy data, drop old, rename.
        // This avoids Doctrine DBAL ->change() issues with timestamp type.

        Schema::create('refund_transactions_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->timestamp('refunded_at')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('type', ['deposit_refund', 'order_refund'])->default('deposit_refund');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Copy existing data
        DB::statement('INSERT INTO refund_transactions_new (id, reservation_id, amount, reason, refunded_at, created_at, updated_at) SELECT id, reservation_id, amount, reason, refunded_at, created_at, updated_at FROM refund_transactions');

        // Drop old table and rename new
        Schema::dropIfExists('refund_transactions');
        Schema::rename('refund_transactions_new', 'refund_transactions');

        // Add indexes
        Schema::table('refund_transactions', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::create('refund_transactions_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason');
            $table->timestamp('refunded_at');
            $table->timestamps();
        });

        DB::statement('INSERT INTO refund_transactions_old (id, reservation_id, amount, reason, refunded_at, created_at, updated_at) SELECT id, reservation_id, amount, reason, refunded_at, created_at, updated_at FROM refund_transactions');

        Schema::dropIfExists('refund_transactions');
        Schema::rename('refund_transactions_old', 'refund_transactions');
    }
};
