<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('threshold')->comment('Minimum slots to succeed');
            $table->decimal('deposit_amount', 12, 2)->comment('Deposit per slot');
            $table->timestamp('deadline');
            $table->enum('status', ['open', 'success', 'failed', 'processing'])->default('open');
            $table->timestamps();

            $table->index(['status', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
