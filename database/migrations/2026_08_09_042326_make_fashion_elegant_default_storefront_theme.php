<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Fashion Elegant is now the only valid storefront theme.
         * Normalize existing store settings before changing the database
         * default so deployed stores cannot remain on the removed template.
         */
        DB::table('store_settings')->update([
            'landing_page_theme' => 'fashion_editorial',
        ]);

        Schema::table('store_settings', function (Blueprint $table): void {
            $table
                ->string('landing_page_theme', 50)
                ->default('fashion_editorial')
                ->change();
        });
    }

    public function down(): void
    {
        /*
         * Restore only the previous schema default.
         *
         * Existing rows are intentionally not changed back to "default"
         * because doing so would overwrite the storefront choice/data.
         */
        Schema::table('store_settings', function (Blueprint $table): void {
            $table
                ->string('landing_page_theme', 50)
                ->default('default')
                ->change();
        });
    }
};
