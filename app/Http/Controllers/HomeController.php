<?php

namespace App\Http\Controllers;

use App\Enums\LandingPageTheme;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $settings = StoreSetting::query()->first();

        $theme = LandingPageTheme::tryFrom(
            (string) ($settings->landing_page_theme ?? ''),
        ) ?? LandingPageTheme::Default;

        $featuredProducts = $this->serializeProducts(
            $this->publicProductQuery()
                ->where('is_featured', true)
                ->latest(),
            8,
        );

        if ($theme === LandingPageTheme::FashionEditorial) {
            return Inertia::render('home', [
                'theme' => $theme->value,
                'featuredProducts' => $featuredProducts,

                'newArrivals' => $this->serializeProducts(
                    $this->publicProductQuery()
                        ->latest(),
                    6,
                ),

                'categories' => $this->landingCategories(),
            ]);
        }

        return Inertia::render('home', [
            'theme' => LandingPageTheme::Default->value,
            'featuredProducts' => $featuredProducts,
            'newArrivals' => [],
            'categories' => [],
        ]);
    }

    /**
     * Keep public-product visibility consistent across all landing themes.
     *
     * @return Builder<Product>
     */
    private function publicProductQuery(): Builder
    {
        return Product::query()
            ->with([
                'category:id,name,slug',
                'images:id,product_id,path,alt_text,sort_order,is_primary',
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
        $primaryImage = $product->images->firstWhere(
            'is_primary',
            true,
        ) ?? $product->images->first();

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

            'image_url' => $primaryImage === null
                ? null
                : Storage::disk('public')->url(
                    $primaryImage->path,
                ),

            'image_alt' => $primaryImage?->alt_text,
        ];
    }

    /**
     * Category cards reuse the latest active product image instead of
     * introducing category-specific image storage during the MVP.
     *
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
                /*
                 * The landing page displays at most five categories, so a
                 * bounded product lookup here keeps the query simple and
                 * avoids unnecessary constrained eager-loading complexity.
                 */
                $product = $category
                    ->products()
                    ->where('is_active', true)
                    ->with([
                        'images:id,product_id,path,alt_text,sort_order,is_primary',
                    ])
                    ->latest()
                    ->first();

                $primaryImage = $product?->images->firstWhere(
                    'is_primary',
                    true,
                ) ?? $product?->images->first();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,

                    'image_url' => $primaryImage === null
                        ? null
                        : Storage::disk('public')->url(
                            $primaryImage->path,
                        ),

                    'image_alt' => $primaryImage?->alt_text,
                ];
            })
            ->values()
            ->all();
    }
}
