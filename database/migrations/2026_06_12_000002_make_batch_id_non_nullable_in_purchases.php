<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DELETE FROM purchases WHERE batch_id IS NULL');

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->change();
        });
    }
};
