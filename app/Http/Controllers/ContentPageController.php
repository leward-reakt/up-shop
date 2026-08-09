<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function __invoke(Page $page): Response
    {
        abort_unless(
            Page::isPublicSlug($page->slug)
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
