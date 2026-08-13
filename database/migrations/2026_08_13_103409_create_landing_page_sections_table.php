<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('eyebrow')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url', 2048)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('landing_page_sections')->insert([
            [
                'key' => 'hero',
                'eyebrow' => 'The New Collection',
                'title' => 'Effortless elegance.',
                'body' => 'Refined silhouettes, considered details, and timeless pieces created for modern dressing.',
                'button_label' => 'Shop new arrivals',
                'button_url' => 'shop?sort=newest',
                'image_path' => null,
                'image_alt' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'collections',
                'eyebrow' => 'Collections',
                'title' => 'Shop by collection',
                'body' => 'Discover a considered selection of pieces designed for an elegant and versatile wardrobe.',
                'button_label' => 'View all collections',
                'button_url' => 'shop',
                'image_path' => null,
                'image_alt' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'new_arrivals',
                'eyebrow' => 'New arrivals',
                'title' => 'The latest pieces',
                'body' => null,
                'button_label' => 'View all',
                'button_url' => 'shop?sort=newest',
                'image_path' => null,
                'image_alt' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'story',
                'eyebrow' => 'Our approach',
                'title' => "Crafted with care.\nDesigned with purpose.",
                'body' => 'Thoughtful materials, refined proportions, and carefully considered details come together in pieces made to remain part of your wardrobe season after season.',
                'button_label' => 'Discover our story',
                'button_url' => 'about',
                'image_path' => null,
                'image_alt' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'signature',
                'eyebrow' => 'Signature selection',
                'title' => 'Timeless by design',
                'body' => 'A refined selection of defining pieces chosen for their versatility, proportion, and enduring appeal.',
                'button_label' => null,
                'button_url' => null,
                'image_path' => null,
                'image_alt' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'final_cta',
                'eyebrow' => 'The complete wardrobe',
                'title' => 'Discover the collection.',
                'body' => 'Modern essentials and refined statement pieces designed to create an elegant, considered wardrobe.',
                'button_label' => 'Shop all',
                'button_url' => 'shop',
                'image_path' => null,
                'image_alt' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_sections');
    }
};
