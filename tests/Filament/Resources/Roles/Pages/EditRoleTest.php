<?php

use App\Filament\Resources\Roles\Pages\EditRole;
use App\Models\ModuleField;
use App\Models\Role;
use Livewire\Livewire;

it('can render the edit page with existing field visibility selected', function () {
    $customerName = ModuleField::create(['module_key' => 'customers', 'field_key' => 'name', 'label' => 'Name']);
    $customerPhone = ModuleField::create(['module_key' => 'customers', 'field_key' => 'phone', 'label' => 'Phone']);

    $role = Role::create(['name' => 'Salesperson', 'guard_name' => 'web']);
    $role->moduleFields()->sync([$customerName->id]);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertOk()
        ->assertFormSet([
            'field_visibility' => [
                'customers' => [$customerName->id],
            ],
        ]);
});

it('can update field visibility, replacing the previous selection', function () {
    $customerName = ModuleField::create(['module_key' => 'customers', 'field_key' => 'name', 'label' => 'Name']);
    $customerPhone = ModuleField::create(['module_key' => 'customers', 'field_key' => 'phone', 'label' => 'Phone']);

    $role = Role::create(['name' => 'Salesperson', 'guard_name' => 'web']);
    $role->moduleFields()->sync([$customerName->id]);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm([
            'field_visibility' => [
                'customers' => [$customerPhone->id],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->moduleFields()->pluck('field_key')->all())->toBe(['phone']);
});
