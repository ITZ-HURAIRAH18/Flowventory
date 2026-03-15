<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add performance indexes for frequently queried columns
     */
    public function up(): void
    {
        // Optimize product searches by SKU and status
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'status')) {
                    return; // Column doesn't exist yet
                }
                $table->index('status');
                $table->fullText(['name', 'sku']); // For full-text search
            });
        }

        // Optimize inventory queries by branch and product
        if (Schema::hasTable('inventories')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->index(['branch_id', 'quantity']); // For branch inventory with low stock checks
            });
        }

        // Optimize stock movement history queries
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->index(['branch_id', 'created_at']);
                $table->index('product_id');
                $table->index('type');
            });
        }

        // Optimize user queries by role
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role_id');
                $table->index('email');
            });
        }

        // Optimize branch queries by manager
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->index('manager_id');
            });
        }

        // Optimize order queries
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['branch_id', 'created_at']);
                $table->index('status');
            });
        }

        // Optimize order items queries
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index('order_id');
                $table->index('product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropFullText(['name', 'sku']);
            });
        }

        if (Schema::hasTable('inventories')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropIndex(['branch_id', 'quantity']);
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropIndex(['branch_id', 'created_at']);
                $table->dropIndex(['product_id']);
                $table->dropIndex(['type']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role_id']);
                $table->dropIndex(['email']);
            });
        }

        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropIndex(['manager_id']);
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['branch_id', 'created_at']);
                $table->dropIndex(['status']);
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex(['order_id']);
                $table->dropIndex(['product_id']);
            });
        }
    }
};
