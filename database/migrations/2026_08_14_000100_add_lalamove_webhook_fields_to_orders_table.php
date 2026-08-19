<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Lalamove webhook ordering and driver tracking fields to orders.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('last_lalamove_webhook_at')->nullable()->after('tracking_url');
            $table->string('lalamove_driver_name')->nullable()->after('last_lalamove_webhook_at');
            $table->string('lalamove_driver_phone')->nullable()->after('lalamove_driver_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'last_lalamove_webhook_at',
                'lalamove_driver_name',
                'lalamove_driver_phone',
            ]);
        });
    }
};
