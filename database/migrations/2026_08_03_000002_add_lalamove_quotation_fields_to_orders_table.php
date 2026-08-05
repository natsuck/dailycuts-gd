<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Lalamove quotation, pickup and tracking fields to orders.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('quotation_id')->nullable()->after('lalamove_status');
            $table->string('pickup_stop_id')->nullable()->after('quotation_id');
            $table->string('delivery_stop_id')->nullable()->after('pickup_stop_id');
            $table->string('delivery_status')->default('pending')->after('delivery_stop_id');
            $table->string('tracking_url')->nullable()->after('delivery_status');
            $table->string('pickup_address')->nullable()->after('tracking_url');
            $table->decimal('pickup_lat', 10, 7)->nullable()->after('pickup_address');
            $table->decimal('pickup_lng', 10, 7)->nullable()->after('pickup_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'quotation_id',
                'pickup_stop_id',
                'delivery_stop_id',
                'delivery_status',
                'tracking_url',
                'pickup_address',
                'pickup_lat',
                'pickup_lng',
            ]);
        });
    }
};
