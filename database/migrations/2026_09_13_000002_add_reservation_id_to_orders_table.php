<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->after('batch_id')
                ->constrained('reservations')->nullOnDelete();
        });

        // Backfill từ payment balance (order_id ↔ reservation_id)
        $links = DB::table('payments')
            ->where('type', 'balance')
            ->whereNotNull('order_id')
            ->whereNotNull('reservation_id')
            ->select('order_id', 'reservation_id')
            ->get();

        foreach ($links as $link) {
            DB::table('orders')
                ->where('id', $link->order_id)
                ->whereNull('reservation_id')
                ->update(['reservation_id' => $link->reservation_id]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
        });
    }
};
