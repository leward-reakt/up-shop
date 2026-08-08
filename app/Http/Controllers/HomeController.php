<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $featuredProducts = Product::query()
            ->with([
                'category:id,name,slug',
                'images:id,product_id,path,alt_text,sort_order,is_primary',
            ])
            ->where('is_active', true)
            ->where('is_featured', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('category_id')
                    ->orWhereHas(
                        'category',
                        fn (Builder $categoryQuery): Builder => $categoryQuery
                            ->where('is_active', true),
                    );
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Product $product): array {
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
            })
            ->values()
            ->all();

        return Inertia::render('home', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
