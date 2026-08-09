<?php

namespace App\Http\Controllers;

use App\Enums\LandingPageTheme;
use App\Http\Requests\ShopIndexRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(ShopIndexRequest $request): Response
    {
        $filters = $request->validated();

        $search = isset($filters['search'])
            ? trim((string) $filters['search'])
            : '';

        $category = isset($filters['category'])
            ? (string) $filters['category']
            : '';

        $availability = isset($filters['availability'])
            ? (string) $filters['availability']
            : '';

        $sort = isset($filters['sort'])
            ? (string) $filters['sort']
            : 'featured';

        $minPrice = $this->toMinorUnit(
            $filters['min_price'] ?? null,
        );

        $maxPrice = $this->toMinorUnit(
            $filters['max_price'] ?? null,
        );

        $query = Product::query()
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
                        fn (
                            Builder $categoryQuery,
                        ): Builder => $categoryQuery
                            ->where('is_active', true),
                    );
            })
            ->when(
                $search !== '',
                fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%"),
            )
            ->when(
                $category !== '',
                fn (Builder $query): Builder => $query
                    ->whereHas(
                        'category',
                        fn (
                            Builder $categoryQuery,
                        ): Builder => $categoryQuery
                            ->where('slug', $category)
                            ->where('is_active', true),
                    ),
            )
            ->when(
                $minPrice !== null,
                fn (Builder $query): Builder => $query
                    ->where('price', '>=', $minPrice),
            )
            ->when(
                $maxPrice !== null,
                fn (Builder $query): Builder => $query
                    ->where('price', '<=', $maxPrice),
            )
            ->when(
                $availability === 'in_stock',
                fn (Builder $query): Builder => $query
                    ->where('stock_quantity', '>', 0),
            );

        match ($sort) {
            'newest' => $query->latest(),

            'price_asc' => $query->orderBy('price'),

            'price_desc' => $query->orderByDesc('price'),

            default => $query
                ->orderByDesc('is_featured')
                ->latest(),
        };

        $products = $query
            ->paginate(12)
            ->withQueryString();

        $products->through(
            fn (
                Product $product,
            ): array => $this->productCardData($product),
        );

        return Inertia::render('shop/index', [
            'theme' => $this->storefrontTheme()->value,

            'products' => $products,

            'categories' => $this->activeCategories(),

            'filters' => [
                'search' => $search,

                'category' => $category,

                'min_price' => isset($filters['min_price'])
                    ? (string) $filters['min_price']
                    : '',

                'max_price' => isset($filters['max_price'])
                    ? (string) $filters['max_price']
                    : '',

                'availability' => $availability,

                'sort' => $sort,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        $product->load([
            'category:id,name,slug,is_active',

            'images:id,product_id,path,alt_text,sort_order',
        ]);

        abort_unless($product->is_active, 404);

        if (
            $product->category_id !== null
            && (
                $product->category === null
                || ! $product->category->is_active
            )
        ) {
            abort(404);
        }

        $metaTitle = filled($product->meta_title)
            ? (string) $product->meta_title
            : $product->name;

        $metaDescription = filled($product->meta_description)
            ? (string) $product->meta_description
            : str((string) $product->description)
                ->squish()
                ->limit(160, '')
                ->toString();

        return Inertia::render('shop/show', [
            'theme' => $this->storefrontTheme()->value,

            /*
             * Fashion Elegant uses category links in its shared storefront
             * navigation. Keeping this query here avoids making every Inertia
             * request pay for catalog navigation data.
             */
            'categories' => $this->activeCategories(),

            'product' => [
                ...$this->productCardData($product),

                'description' => $product->description,

                'meta_title' => $metaTitle,

                'meta_description' => $metaDescription,

                'images' => $product->images
                    ->map(
                        fn ($image): array => [
                            'id' => $image->id,

                            'url' => Storage::disk('public')
                                ->url($image->path),

                            'alt_text' => $image->alt_text,
                        ],
                    )
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * @return Collection<int, Category>
     */
    private function activeCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);
    }

    private function storefrontTheme(): LandingPageTheme
    {
        $settings = StoreSetting::query()->first();

        return LandingPageTheme::tryFrom(
            (string) ($settings->landing_page_theme ?? ''),
        ) ?? LandingPageTheme::FashionEditorial;
    }

    /**
     * @return array<string, mixed>
     */
    private function productCardData(Product $product): array
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
                : Storage::disk('public')
                    ->url($mainImage->path),

            'image_alt' => $mainImage?->alt_text,
        ];
    }

    private function toMinorUnit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(
            ((float) $value) * 100,
        );
    }
}
