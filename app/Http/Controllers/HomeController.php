<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LandingPageSection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $featuredProducts = $this->serializeProducts(
            $this->publicProductQuery()
                ->where('is_featured', true)
                ->latest(),
            8,
        );

        return Inertia::render('home', [
            'featuredProducts' => $featuredProducts,

            'newArrivals' => $this->serializeProducts(
                $this->publicProductQuery()
                    ->latest(),
                6,
            ),

            'categories' => $this->landingCategories(),

            'sections' => $this->landingSections(),
        ]);
    }

    /**
     * Keep public-product visibility consistent across the storefront.
     *
     * @return Builder<Product>
     */
    private function publicProductQuery(): Builder
    {
        return Product::query()
            ->with([
                'category:id,name,slug',
                'images:id,product_id,path,alt_text,sort_order',
            ])
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('category_id')
                    ->orWhereHas(
                        'category',
                        fn (Builder $categoryQuery): Builder => $categoryQuery
                            ->where('is_active', true),
                    );
            });
    }

    /**
     * @param  Builder<Product>  $query
     * @return array<int, array<string, mixed>>
     */
    private function serializeProducts(
        Builder $query,
        int $limit,
    ): array {
        return $query
            ->limit($limit)
            ->get()
            ->map(
                fn (Product $product): array => $this->serializeProduct(
                    $product,
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProduct(Product $product): array
    {
        $mainImage = $product->images->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => $product->price,
            'stock_quantity' => $product->stock_quantity,
            'is_featured' => $product->is_featured,

            'category' => $product->category === null
                ? null
                : [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ],

            'image_url' => $mainImage === null
                ? null
                : $this->publicAssetUrl($mainImage->path),

            'image_alt' => $mainImage?->alt_text,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function landingCategories(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->whereHas(
                'products',
                fn (Builder $query): Builder => $query
                    ->where('is_active', true),
            )
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(function (Category $category): array {
                // Prefer the administrator-managed category image.
                $uploadedImageUrl = $category->image_path === null
                    ? null
                    : Storage::disk('public')->url($category->image_path);

                // Preserve seeded development category covers as a fallback.
                $seededImageUrl = $uploadedImageUrl === null
                    ? $this->publicAssetUrl(
                        "categories/{$category->slug}.png",
                    )
                    : null;

                $mainImage = null;

                if (
                    $uploadedImageUrl === null
                    && $seededImageUrl === null
                ) {
                    $product = $category
                        ->products()
                        ->where('is_active', true)
                        ->with([
                            'images:id,product_id,path,alt_text,sort_order',
                        ])
                        ->latest()
                        ->first();

                    $mainImage = $product?->images->first();
                }

                $imageUrl = $uploadedImageUrl
                    ?? $seededImageUrl
                    ?? (
                        $mainImage === null
                            ? null
                            : $this->publicAssetUrl($mainImage->path)
                    );

                $imageAlt = $category->image_alt;

                if (
                    $imageAlt === null
                    && (
                        $uploadedImageUrl !== null
                        || $seededImageUrl !== null
                    )
                ) {
                    $imageAlt = $category->name;
                }

                $imageAlt ??= $mainImage?->alt_text;

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image_url' => $imageUrl,
                    'image_alt' => $imageAlt,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Only active homepage sections are exposed to the storefront.
     *
     * @return array<string, array<string, mixed>>
     */
    private function landingSections(): array
    {
        return LandingPageSection::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(
                function (LandingPageSection $section): array {
                    return [
                        $section->key => [
                            'key' => $section->key,
                            'eyebrow' => $section->eyebrow,
                            'title' => $section->title,
                            'body' => $section->body,
                            'button_label' => $section->button_label,
                            'button_url' => $section->button_url,

                            'image_url' => $section->image_path === null
                                ? null
                                : Storage::disk('public')->url(
                                    $section->image_path,
                                ),

                            'image_alt' => $section->image_alt,
                        ],
                    ];
                },
            )
            ->all();
    }

    private function publicAssetUrl(string $path): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->url($path);
    }
}
