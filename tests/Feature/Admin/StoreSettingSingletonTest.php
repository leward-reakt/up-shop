<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\StoreSettings\StoreSettingResource;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSettingSingletonTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_settings_can_only_be_created_when_none_exist(): void
    {
        $this->assertTrue(
            StoreSettingResource::canCreate(),
        );

        StoreSetting::query()->create([
            'store_name' => 'Singleton Test Store',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
        ]);

        $this->assertFalse(
            StoreSettingResource::canCreate(),
        );

        $this->assertDatabaseCount(
            'store_settings',
            1,
        );
    }

    public function test_store_settings_cannot_be_deleted_through_filament_resource(): void
    {
        $settings = StoreSetting::query()->create([
            'store_name' => 'Singleton Test Store',
            'currency' => 'PHP',
            'default_shipping_fee' => 0,
        ]);

        $this->assertFalse(
            StoreSettingResource::canDelete($settings),
        );

        $this->assertFalse(
            StoreSettingResource::canDeleteAny(),
        );
    }
}
