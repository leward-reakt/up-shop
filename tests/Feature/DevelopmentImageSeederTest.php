<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\DevelopmentImageSeeder;
use Database\Seeders\DevelopmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

class DevelopmentImageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_category_product_and_website_images(): void
    {
        Storage::fake('public');

        $this->seed(DevelopmentSeeder::class);
        $this->seed(DevelopmentImageSeeder::class);

        foreach ($this->expectedCategoryImages() as $filename) {
            $this->assertTrue(
                Storage::disk('public')->exists(
                    'categories/'.$filename,
                ),
            );
        }

        $this->assertTrue(
            Storage::disk('public')->exists(
                'website/hero-banner.png',
            ),
        );

        $productImageFiles = $this->productImageFiles();

        $this->assertNotEmpty($productImageFiles);

        $this->assertSame(
            count($productImageFiles),
            ProductImage::query()->count(),
        );

        foreach ($productImageFiles as $imageFile) {
            $filename = $imageFile->getFilename();
            $identifier = pathinfo(
                $filename,
                PATHINFO_FILENAME,
            );

            $product = Product::query()
                ->where('sku', $identifier)
                ->orWhere('slug', $identifier)
                ->firstOrFail();

            $destinationPath = 'products/'.$filename;

            $this->assertTrue(
                Storage::disk('public')->exists(
                    $destinationPath,
                ),
            );

            $this->assertDatabaseHas('product_images', [
                'product_id' => $product->id,
                'path' => $destinationPath,
                'alt_text' => $product->name,
                'sort_order' => 0,
            ]);
        }
    }

    public function test_it_can_reseed_images_without_duplicate_product_images(): void
    {
        Storage::fake('public');

        $this->seed(DevelopmentSeeder::class);
        $this->seed(DevelopmentImageSeeder::class);

        $expectedProductImageCount = ProductImage::query()->count();

        $this->seed(DevelopmentImageSeeder::class);

        $this->assertSame(
            $expectedProductImageCount,
            ProductImage::query()->count(),
        );
    }

    /**
     * @return list<string>
     */
    private function expectedCategoryImages(): array
    {
        return [
            'accessories.png',
            'apparel.png',
            'beauty-self-care.png',
            'footwear.png',
            'lifestyle.png',
        ];
    }

    /**
     * @return list<SplFileInfo>
     */
    private function productImageFiles(): array
    {
        return array_values(
            array_filter(
                File::files(
                    database_path('seeders/images/products'),
                ),
                fn (SplFileInfo $file): bool => in_array(
                    strtolower($file->getExtension()),
                    [
                        'jpeg',
                        'jpg',
                        'png',
                        'webp',
                    ],
                    true,
                ),
            ),
        );
    }
}
