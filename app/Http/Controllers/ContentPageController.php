<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    /**
     * Public pages approved by the locked MVP scope.
     *
     * @var array<int, string>
     */
    public const PUBLIC_SLUGS = [
        'about',
        'contact',
        'privacy-policy',
        'terms-and-conditions',
        'shipping-policy',
        'return-refund-policy',
    ];

    public function __invoke(Page $page): Response
    {
        abort_unless(
            in_array(
                $page->slug,
                self::PUBLIC_SLUGS,
                true,
            )
            && $page->is_published,
            404,
        );

        $metaTitle = filled($page->meta_title)
            ? (string) $page->meta_title
            : $page->title;

        $metaDescription = filled($page->meta_description)
            ? (string) $page->meta_description
            : str((string) $page->content)
                ->squish()
                ->limit(160, '')
                ->toString();

        return Inertia::render('pages/show', [
            'contentPage' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => (string) ($page->content ?? ''),
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
            ],
        ]);
    }
}
