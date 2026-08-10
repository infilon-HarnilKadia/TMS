<?php

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Models\ModuleField;
use App\Models\Role;
use Livewire\Livewire;

it('can render the create page', function () {
    Livewire::test(CreateRole::class)
        ->assertOk();
});

it('can create a role with field visibility', function () {
    $customerName = ModuleField::create(['module_key' => 'customers', 'field_key' => 'name', 'label' => 'Name']);
    $customerPhone = ModuleField::create(['module_key' => 'customers', 'field_key' => 'phone', 'label' => 'Phone']);
    ModuleField::create(['module_key' => 'orders', 'field_key' => 'total_price', 'label' => 'Total Price']);

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Salesperson',
            'guard_name' => 'web',
            'field_visibility' => [
                'customers' => [$customerName->id, $customerPhone->id],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::where('name', 'Salesperson')->first();

    expect($role)->not->toBeNull();
    expect($role->moduleFields()->pluck('field_key')->sort()->values()->all())
        ->toBe(['name', 'phone']);
});
