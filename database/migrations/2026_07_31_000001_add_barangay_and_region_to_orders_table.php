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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'barangay')) {
                $table->string('barangay')->nullable()->after('city');
            }
            if (! Schema::hasColumn('orders', 'region')) {
                $table->string('region')->nullable()->after('barangay');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'barangay')) {
                $table->dropColumn('barangay');
            }
            if (Schema::hasColumn('orders', 'region')) {
                $table->dropColumn('region');
            }
        });
    }
};
