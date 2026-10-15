<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomplete_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_token')->unique();
            $table->string('customer_phone', 20)->index();
            $table->string('customer_name')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('delivery_area')->nullable(); // 'inside' or 'outside'
            $table->json('items')->nullable(); // product_id, name, price, qty (prices come from the database)
            $table->decimal('cart_total', 12, 2)->default(0);
            $table->string('status')->default('new'); // new, no_answer, call_later, ordered, not_interested
            $table->timestamp('last_captured_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomplete_orders');
    }
};
