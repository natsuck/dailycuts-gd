<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
        });

        // Remove orphaned order_items before enforcing foreign keys.
        DB::table('order_items')
            ->whereNotIn('order_id', DB::table('orders')->select('id'))
            ->delete();

        DB::table('order_items')
            ->whereNotIn('product_id', DB::table('products')->select('id'))
            ->delete();

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });

        // Collapse duplicate variant weights (same product) before enforcing uniqueness.
        $dupVariants = DB::table('product_variants')
            ->select('product_id', 'weight')
            ->groupBy('product_id', 'weight')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupVariants as $dup) {
            $rows = DB::table('product_variants')
                ->where('product_id', $dup->product_id)
                ->where('weight', $dup->weight)
                ->orderBy('id')
                ->get();

            $keep = $rows->first();
            $removeIds = $rows->pluck('id')->slice(1)->all();

            foreach ($removeIds as $removeId) {
                DB::table('carts')->where('variant_id', $removeId)->update(['variant_id' => $keep->id]);
            }

            DB::table('product_variants')->whereIn('id', $removeIds)->delete();
        }

        // Merge duplicate cart rows (same user, product, variant) before enforcing uniqueness.
        $duplicates = DB::table('carts')
            ->select('user_id', 'product_id', 'variant_id')
            ->groupBy('user_id', 'product_id', 'variant_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $rows = DB::table('carts')
                ->where('user_id', $dup->user_id)
                ->where('product_id', $dup->product_id)
                ->where(function ($query) use ($dup) {
                    if ($dup->variant_id === null) {
                        $query->whereNull('variant_id');
                    } else {
                        $query->where('variant_id', $dup->variant_id);
                    }
                })
                ->orderBy('id')
                ->get();

            $keep = $rows->first();
            $totalQty = $rows->sum('quantity');

            DB::table('carts')->where('id', $keep->id)->update(['quantity' => $totalQty]);
            DB::table('carts')->whereIn('id', $rows->pluck('id')->slice(1))->delete();
        }

        Schema::table('carts', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id', 'variant_id'], 'carts_user_product_variant_unique');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique(['product_id', 'weight'], 'product_variants_product_weight_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_product_weight_unique');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique('carts_user_product_variant_unique');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variant_id']);
            $table->dropColumn('variant_id');
        });
    }
};
