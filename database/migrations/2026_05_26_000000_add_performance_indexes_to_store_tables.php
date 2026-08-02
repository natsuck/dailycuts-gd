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
            $table->index('product_category', 'products_category_index');
            $table->index('created_at', 'products_created_at_index');
            $table->index('product_quantity', 'products_quantity_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'orders_status_created_at_index');
            $table->index(['payment_status', 'created_at'], 'orders_payment_status_created_at_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id', 'order_items_order_id_index');
            $table->index('product_id', 'order_items_product_id_index');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->index(['user_id', 'product_id'], 'carts_user_product_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('carts_user_product_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_id_index');
            $table->dropIndex('order_items_product_id_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_at_index');
            $table->dropIndex('orders_payment_status_created_at_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_index');
            $table->dropIndex('products_created_at_index');
            $table->dropIndex('products_quantity_index');
        });
    }
};
