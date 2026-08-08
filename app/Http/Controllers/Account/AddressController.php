<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $addresses = $request
            ->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(
                fn (Address $address): array => $this->addressData(
                    $address,
                ),
            )
            ->values()
            ->all();

        return Inertia::render('account/addresses/index', [
            'addresses' => $addresses,
        ]);
    }

    public function store(
        AddressRequest $request,
    ): RedirectResponse {
        $user = $request->user();

        $data = $request->validated();

        $requestedDefault = (bool) (
            $data['is_default'] ?? false
        );

        $email = $data['email'] ?? $user->email;

        unset($data['is_default']);

        DB::transaction(
            function () use (
                $user,
                $data,
                $email,
                $requestedDefault,
            ): void {
                $makeDefault = $requestedDefault
                    || ! $user->addresses()->exists();

                if ($makeDefault) {
                    $user
                        ->addresses()
                        ->update([
                            'is_default' => false,
                        ]);
                }

                $user
                    ->addresses()
                    ->create([
                        ...$data,
                        'email' => $email,
                        'country' => 'PH',
                        'is_default' => $makeDefault,
                    ]);
            },
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Address added.'),
        ]);

        return to_route('account.addresses.index');
    }

    public function edit(
        Request $request,
        Address $address,
    ): Response {
        Gate::authorize('update', $address);

        return Inertia::render('account/addresses/edit', [
            'address' => $this->addressData($address),
        ]);
    }

    public function update(
        AddressRequest $request,
        Address $address,
    ): RedirectResponse {
        Gate::authorize('update', $address);

        $user = $request->user();

        $data = $request->validated();

        $requestedDefault = (bool) (
            $data['is_default'] ?? false
        );

        $email = $data['email']
            ?? $address->email
            ?? $user->email;

        unset($data['is_default']);

        DB::transaction(
            function () use (
                $user,
                $address,
                $data,
                $email,
                $requestedDefault,
            ): void {
                $anotherDefaultExists = $user
                    ->addresses()
                    ->where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->exists();

                $makeDefault = $requestedDefault
                    || $address->is_default
                    || ! $anotherDefaultExists;

                if ($requestedDefault) {
                    $user
                        ->addresses()
                        ->where('id', '!=', $address->id)
                        ->update([
                            'is_default' => false,
                        ]);
                }

                $address->update([
                    ...$data,
                    'email' => $email,
                    'country' => 'PH',
                    'is_default' => $makeDefault,
                ]);
            },
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Address updated.'),
        ]);

        return to_route('account.addresses.index');
    }

    public function destroy(
        Request $request,
        Address $address,
    ): RedirectResponse {
        Gate::authorize('delete', $address);

        $user = $request->user();

        DB::transaction(
            function () use (
                $user,
                $address,
            ): void {
                $wasDefault = $address->is_default;

                $address->delete();

                if (! $wasDefault) {
                    return;
                }

                $nextAddress = $user
                    ->addresses()
                    ->orderBy('id')
                    ->first();

                $nextAddress?->update([
                    'is_default' => true,
                ]);
            },
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Address deleted.'),
        ]);

        return to_route('account.addresses.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function addressData(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'email' => $address->email,
            'phone' => $address->phone,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'province' => $address->province,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'is_default' => $address->is_default,
        ];
    }
}
