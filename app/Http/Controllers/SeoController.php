<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => null,
            ],
            [
                'loc' => route('shop.index'),
                'lastmod' => null,
            ],
        ];

        $pageUrls = Page::query()
            ->whereIn(
                'slug',
                Page::publicSlugs(),
            )
            ->where('is_published', true)
            ->orderBy('slug')
            ->get([
                'slug',
                'updated_at',
            ])
            ->map(
                fn (Page $page): array => [
                    'loc' => route(
                        'pages.show',
                        [
                            'page' => $page->slug,
                        ],
                    ),

                    'lastmod' => $page->updated_at
                        ?->toAtomString(),
                ],
            )
            ->all();

        $productUrls = Product::query()
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
            ->orderBy('id')
            ->get([
                'id',
                'slug',
                'updated_at',
                'category_id',
            ])
            ->map(
                fn (Product $product): array => [
                    'loc' => route(
                        'products.show',
                        [
                            'product' => $product->slug,
                        ],
                    ),

                    'lastmod' => $product->updated_at
                        ?->toAtomString(),
                ],
            )
            ->all();

        return response()
            ->view(
                'sitemap',
                [
                    'urls' => [
                        ...$urls,
                        ...$pageUrls,
                        ...$productUrls,
                    ],
                ],
            )
            ->header(
                'Content-Type',
                'application/xml; charset=UTF-8',
            );
    }

    public function robots(): Response
    {
        if (! config('seo.indexing_enabled', false)) {
            return response(
                "User-agent: *\nDisallow: /\n",
                200,
                [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ],
            );
        }

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /dashboard',
            'Disallow: /account',
            'Disallow: /settings',
            '',
            'Sitemap: '.route('seo.sitemap'),
            '',
        ];

        return response(
            implode("\n", $lines),
            200,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ],
        );
    }
}
