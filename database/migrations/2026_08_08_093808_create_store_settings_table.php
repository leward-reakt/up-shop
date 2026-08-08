<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();

            $table->string('store_name');
            $table->string('store_email')->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->text('business_address')->nullable();

            $table->string('currency', 3)->default('PHP');

            $table->unsignedBigInteger('default_shipping_fee')->default(0);
            $table->unsignedBigInteger('free_shipping_threshold')->nullable();

            // Basis points: 100 = 1.00%.
            $table->unsignedInteger('tax_rate_basis_points')->nullable();

            $table->json('social_links')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
