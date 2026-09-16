<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_transaction_id')->constrained('refund_transactions')->cascadeOnDelete();
            $table->foreignId('order_detail_id')->nullable()->constrained('order_details')->nullOnDelete();
            $table->unsignedInteger('quantity_refunded');
            $table->decimal('amount_refunded', 12, 2);
            $table->timestamps();

            // Unique constraint: cùng 1 refund transaction không refund 2 lần cùng item
            $table->unique(['refund_transaction_id', 'order_detail_id'], 'uq_refund_detail');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_transaction_details');
    }
};
