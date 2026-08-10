<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

it('can render the create page', function () {
    Livewire::test(CreateUser::class)
        ->assertOk();
});

it('can create a user and assign a role', function () {
    $role = Role::create(['name' => 'Salesperson', 'guard_name' => 'web']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'password',
            'roles' => [$role->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'jane.doe@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole('Salesperson'))->toBeTrue();
    expect($user->password)->not->toBe('password');
});

it('validates the form data', function (array $data, array $errors) {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'password',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`email` is required' => [['email' => null], ['email' => 'required']],
    '`email` must be valid' => [['email' => 'not-an-email'], ['email' => 'email']],
]);
