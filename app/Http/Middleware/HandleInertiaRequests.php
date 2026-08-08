<?php

namespace App\Http\Middleware;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'name' => config('app.name'),

            'auth' => [
                'user' => $request->user(),
            ],

            'cart' => [
                'guest_has_items' => fn (): bool => $this->guestCartHasItems(
                    $request,
                ),
            ],

            'store' => fn (): array => $this->storeData(),

            'seo' => [
                'base_url' => rtrim(
                    (string) config('app.url'),
                    '/',
                ),
                'indexing_enabled' => (bool) config(
                    'seo.indexing_enabled',
                    false,
                ),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function guestCartHasItems(Request $request): bool
    {
        if ($request->user() !== null) {
            return false;
        }

        $storedItems = $request
            ->session()
            ->get('cart.items', []);

        if (! is_array($storedItems)) {
            return false;
        }

        foreach ($storedItems as $productId => $quantity) {
            if (
                is_numeric($productId)
                && is_numeric($quantity)
                && (int) $productId > 0
                && (int) $quantity > 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Storefront information comes from the single store settings record.
     *
     * @return array<string, mixed>
     */
    private function storeData(): array
    {
        $settings = StoreSetting::query()->first();

        $logoPath = $settings?->store_logo_path;

        return [
            'name' => $settings?->store_name
                ?: (string) config('app.name', 'Up Shop'),

            'logo_url' => is_string($logoPath) && $logoPath !== ''
                ? Storage::disk('public')->url($logoPath)
                : null,

            'email' => $settings?->store_email,
            'contact_number' => $settings?->contact_number,
            'business_address' => $settings?->business_address,
        ];
    }
}
