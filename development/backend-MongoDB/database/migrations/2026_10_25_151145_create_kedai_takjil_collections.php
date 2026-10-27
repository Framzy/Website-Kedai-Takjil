<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        $connection = Schema::connection('mongodb');
        $collection_names = ['products', 'orders', 'order_items'];

        foreach ($collection_names as $collection) {
            if (!$connection->hasTable($collection)) {
                $connection->create($collection, function ($collection) {
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    { 

    }
};