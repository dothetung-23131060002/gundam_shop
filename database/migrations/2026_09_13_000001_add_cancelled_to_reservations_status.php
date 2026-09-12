<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('reserved','refunded','converted','cancelled') DEFAULT 'reserved'");
        } else {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('status', 20)->default('reserved')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('reserved','refunded','converted') DEFAULT 'reserved'");
        } else {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('status', 20)->default('reserved')->change();
            });
        }
    }
};
