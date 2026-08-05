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
            $table->string('checkout_session_url')->nullable()->after('checkout_session_id');
            $table->string('idempotency_key')->nullable()->after('delivery_lng');

            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'idempotency_key']);

            $table->dropColumn(['checkout_session_url', 'idempotency_key']);
        });
    }
};
