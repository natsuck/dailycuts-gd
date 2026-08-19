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
        Schema::create('maya_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id');
            $table->string('event');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('request_reference_number')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'event']);

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maya_webhook_events');
    }
};
