<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'fresh_storage')) {
                $table->string('fresh_storage')->nullable()->after('product_type');
            }
            if (! Schema::hasColumn('products', 'fresh_prep')) {
                $table->string('fresh_prep')->nullable()->after('fresh_storage');
            }
            if (! Schema::hasColumn('products', 'fresh_delivery')) {
                $table->string('fresh_delivery')->nullable()->after('fresh_prep');
            }
            if (! Schema::hasColumn('products', 'fresh_best_for')) {
                $table->string('fresh_best_for')->nullable()->after('fresh_delivery');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['fresh_storage', 'fresh_prep', 'fresh_delivery', 'fresh_best_for'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
