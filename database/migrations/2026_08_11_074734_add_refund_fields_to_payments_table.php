<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('refund_reference')
                ->nullable()
                ->unique();

            $table->timestamp('refunded_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique([
                'refund_reference',
            ]);

            $table->dropColumn([
                'refund_reference',
                'refunded_at',
            ]);
        });
    }
};
