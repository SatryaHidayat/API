<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE orders MODIFY status ENUM('pending', 'paid', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('orders')->where('status', 'paid')->update(['status' => 'pending']);
            DB::statement(
                "ALTER TABLE orders MODIFY status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'"
            );
        }
    }
};
