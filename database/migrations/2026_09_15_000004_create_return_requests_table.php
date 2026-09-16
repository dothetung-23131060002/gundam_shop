<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_detail_id')->nullable()->constrained('order_details')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason');
            $table->string('status')->default('requested');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->string('inspection')->nullable();
            $table->unsignedInteger('restocked_qty')->default(0);
            $table->foreignId('refund_transaction_id')->nullable()->constrained('refund_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
