<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class DevelopmentImageSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const IMAGE_EXTENSIONS = [
        'jpeg',
        'jpg',
        'png',
        'webp',
    ];

    public function run(): void
    {
        $this->copyCategoryImages();
        $this->seedProductImages();

        $this->copyImage(
            database_path('seeders/images/website/hero-banner.png'),
            'website/hero-banner.png',
        );
    }

    private function copyCategoryImages(): void
    {
        $sourceDirectory = database_path('seeders/images/categories');

        foreach ($this->imageFiles($sourceDirectory) as $imageFile) {
            $this->copyImage(
                $imageFile->getPathname(),
                'categories/'.$imageFile->getFilename(),
            );
        }
    }

    private function seedProductImages(): void
    {
        $sourceDirectory = database_path('seeders/images/products');

        foreach ($this->imageFiles($sourceDirectory) as $imageFile) {
            $filename = $imageFile->getFilename();
            $identifier = pathinfo($filename, PATHINFO_FILENAME);

            /*
             * Product seed images may use either the SKU or product slug as
             * their filename. This keeps the development assets explicit
             * without introducing a second hard-coded product/image map.
             */
            $product = Product::query()
                ->where('sku', $identifier)
                ->orWhere('slug', $identifier)
                ->first();

            if ($product === null) {
                throw new RuntimeException(
                    "No seeded product matches image [{$filename}]. ".
                    'Use the product SKU or slug as the image filename.',
                );
            }

            $destinationPath = 'products/'.$filename;

            $this->copyImage(
                $imageFile->getPathname(),
                $destinationPath,
            );

            /*
             * Keep the seeded development image first in the gallery while
             * preserving any other images that may already exist locally.
             */
            $product->images()
                ->where('path', '!=', $destinationPath)
                ->where('sort_order', 0)
                ->update([
                    'sort_order' => 1,
                ]);

            $product->images()->updateOrCreate(
                [
                    'path' => $destinationPath,
                ],
                [
                    'alt_text' => $product->name,
                    'sort_order' => 0,
                ],
            );
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    private function imageFiles(string $directory): array
    {
        if (! File::isDirectory($directory)) {
            throw new RuntimeException(
                "Development seeder image directory is missing: {$directory}",
            );
        }

        $files = array_values(
            array_filter(
                File::files($directory),
                fn (SplFileInfo $file): bool => in_array(
                    strtolower($file->getExtension()),
                    self::IMAGE_EXTENSIONS,
                    true,
                ),
            ),
        );

        if ($files === []) {
            throw new RuntimeException(
                "No supported development seeder images found in: {$directory}",
            );
        }

        usort(
            $files,
            fn (SplFileInfo $left, SplFileInfo $right): int => strcmp(
                $left->getFilename(),
                $right->getFilename(),
            ),
        );

        return $files;
    }

    private function copyImage(
        string $sourcePath,
        string $destinationPath,
    ): void {
        if (! File::isFile($sourcePath)) {
            throw new RuntimeException(
                "Development seeder image is missing: {$sourcePath}",
            );
        }

        $stored = Storage::disk('public')->put(
            $destinationPath,
            File::get($sourcePath),
        );

        if (! $stored) {
            throw new RuntimeException(
                "Unable to seed image to public storage: {$destinationPath}",
            );
        }
    }
}
