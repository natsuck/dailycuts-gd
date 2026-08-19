<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the announcement ticker flag to sale banners.
     */
    public function up(): void
    {
        Schema::table('sale_banners', function (Blueprint $table) {
            $table->boolean('show_in_ticker')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_banners', function (Blueprint $table) {
            $table->dropColumn('show_in_ticker');
        });
    }
};
