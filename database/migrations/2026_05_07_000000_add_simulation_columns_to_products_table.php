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
        if (! Schema::hasColumn('products', 'reorder_level')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('reorder_level')->default(10)->after('product_quantity');
            });
        }

        if (! Schema::hasColumn('products', 'expiry_date')) {
            Schema::table('products', function (Blueprint $table) {
                $afterColumn = Schema::hasColumn('products', 'reorder_level')
                    ? 'reorder_level'
                    : 'product_quantity';

                $table->date('expiry_date')->nullable()->after($afterColumn);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('products', 'expiry_date') ? 'expiry_date' : null,
            Schema::hasColumn('products', 'reorder_level') ? 'reorder_level' : null,
        ]));

        if ($columns !== []) {
            Schema::table('products', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
