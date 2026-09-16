<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->restrictOnDelete();
            $table->enum('type', [
                'supplier_shortage',
                'lost_in_transit',
                'damaged',
                'quality_defect',
                'wrong_item',
                'delivery_failed',
                'other',
            ]);
            $table->text('description')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_incidents');
    }
};
