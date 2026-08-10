<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)
                ->nullable();

            $table->string('provider', 50)
                ->nullable();

            $table->string('provider_checkout_id')
                ->nullable()
                ->unique();

            $table->string('provider_payment_intent_id')
                ->nullable()
                ->unique();

            $table->string('provider_payment_id')
                ->nullable()
                ->unique();

            $table->timestamp('failed_at')
                ->nullable();

            $table->json('metadata')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'provider',
                'provider_checkout_id',
                'provider_payment_intent_id',
                'provider_payment_id',
                'failed_at',
                'metadata',
            ]);
        });
    }
};
