<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            // Nullable keeps this migration production-safe for existing rows.
            // Application validation requires email for new address entries.
            $table->string('email')->nullable();
        });

        // Existing addresses predate the email column. Backfill them from the
        // owning customer's email so they immediately remain checkout-ready.
        DB::table('addresses')
            ->select([
                'id',
                'user_id',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($addresses): void {
                    foreach ($addresses as $address) {
                        $email = DB::table('users')
                            ->where('id', $address->user_id)
                            ->value('email');

                        if (! is_string($email) || $email === '') {
                            continue;
                        }

                        DB::table('addresses')
                            ->where('id', $address->id)
                            ->update([
                                'email' => $email,
                            ]);
                    }
                },
            );
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropColumn('email');
        });
    }
};
