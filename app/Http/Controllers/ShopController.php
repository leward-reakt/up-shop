<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopIndexRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
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

        $minPrice = $this->toMinorUnit($filters['min_price'] ?? null);
        $maxPrice = $this->toMinorUnit($filters['max_price'] ?? null);

        $query = Product::query()
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
                        fn (Builder $categoryQuery): Builder => $categoryQuery
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
            fn (Product $product): array => $this->productCardData($product),
        );

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        return Inertia::render('shop/index', [
            'products' => $products,
            'categories' => $categories,
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
            'images:id,product_id,path,alt_text,sort_order,is_primary',
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

        return Inertia::render('shop/show', [
            'product' => [
                ...$this->productCardData($product),
                'description' => $product->description,
                'images' => $product->images
                    ->map(fn ($image): array => [
                        'id' => $image->id,
                        'url' => Storage::disk('public')->url($image->path),
                        'alt_text' => $image->alt_text,
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productCardData(Product $product): array
    {
        $primaryImage = $product->images->firstWhere('is_primary', true)
            ?? $product->images->first();

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
                : Storage::disk('public')->url($primaryImage->path),
            'image_alt' => $primaryImage?->alt_text,
        ];
    }

    private function toMinorUnit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }
}
