<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('discount_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Customer snapshot.
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 50);

            // Shipping address snapshot.
            $table->string('shipping_address_line_1');
            $table->string('shipping_address_line_2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_province');
            $table->string('shipping_postal_code', 20);
            $table->string('shipping_country', 2)->default('PH');

            $table->string('shipping_method', 30);

            $table->string('discount_code')->nullable();

            // All totals are stored in the smallest currency unit.
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('grand_total');

            $table->string('payment_method', 30);
            $table->string('payment_status', 30);
            $table->string('order_status', 30);

            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            $table->index(['order_status', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
