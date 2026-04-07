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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->onDeleteCascade();
            $table->string('order_number', 30)->unique();
            $table->integer('total_items');
            $table->decimal('total_price', 10, 2);
            $table->enum('status_order', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->string('customer_email', 100)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->foreignId('promo_id')->nullable()->constrained('promos')->onDeleteCascade();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
