<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

it('can render the list page', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users);
});

it('can filter users by role', function () {
    $role = Role::create(['name' => 'Salesperson', 'guard_name' => 'web']);

    $withRole = User::factory()->create();
    $withRole->assignRole($role);

    $withoutRole = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('roles', [$role->id])
        ->assertCanSeeTableRecords([$withRole])
        ->assertCanNotSeeTableRecords([$withoutRole]);
});
