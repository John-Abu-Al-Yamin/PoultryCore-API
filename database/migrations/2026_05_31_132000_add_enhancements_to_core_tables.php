<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DELETE FROM payments WHERE customer_id IS NOT NULL AND customer_id NOT IN (SELECT id FROM customers)');
        DB::statement('DELETE FROM payments WHERE sale_id IS NOT NULL AND sale_id NOT IN (SELECT id FROM sales)');

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('unit', 50)->default('piece')->after('quantity');
            $table->string('purchase_number', 50)->nullable()->unique()->after('id');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->string('unit', 50)->default('piece')->after('quantity');
            $table->string('sale_number', 50)->nullable()->unique()->after('id');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_number', 50)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['sale_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('unit');
            $table->dropColumn('purchase_number');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('unit');
            $table->dropColumn('sale_number');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('receipt_number');
        });
    }
};
