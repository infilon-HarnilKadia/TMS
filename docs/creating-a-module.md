# How to add a new module

A "module" in this app is a Filament Resource that shows up in the admin
panel, gated by Shield-generated permissions, and optionally by
column-level field visibility. This is the checklist to follow every time
you add one (e.g. a "Suppliers" module, a "Warranties" module, etc).

## 1. Model + migration (skip if the model already exists)

```
php artisan make:model Supplier -m
```

Fill in the migration, then:

```
php artisan migrate
```

## 2. Filament Resource

```
php artisan make:filament-resource Supplier --generate
```

Wire up the table/form as usual. Nothing Shield-specific needed here —
Shield discovers resources automatically from the panel's
`discoverResources()` call in `AdminPanelProvider`.

## 3. Generate permissions + policy

```
php artisan shield:generate --resource=SupplierResource --panel=admin
```

This creates:
- `app/Policies/SupplierPolicy.php` (checks `$user->can('ViewAny:Supplier')`
  etc — note the `Verb:Model` naming convention, not snake_case)
- Rows in the `permissions` table for every CRUD action
  (`ViewAny:Supplier`, `View:Supplier`, `Create:Supplier`, ...)

Policy enforcement is already wired globally via
`FilamentShield::enforcePolicies()` in `AppServiceProvider::boot()` — you
don't need to register the new policy anywhere yourself.

## 4. Grant the new permissions to roles

**super_admin doesn't get new permissions automatically.** Re-sync it:

```
php artisan shield:seeder --option=permissions_via_roles --force --no-interaction
vendor/bin/pint --dirty --format agent
php artisan db:seed --class=ShieldSeeder --force
```

This regenerates `database/seeders/ShieldSeeder.php` from the current DB
state (all roles + their permissions) and re-runs it. It's idempotent
(`firstOrCreate` under the hood), so safe to re-run any time roles or
permissions change.

For any other role, go to **Users → Roles** in the admin panel (`/shield/roles`)
and check the boxes for the new module under the "Resources" tab.

⚠️ Don't forget to commit the regenerated `database/seeders/ShieldSeeder.php`
and re-run it in every environment (including `.env.testing`'s SQLite DB —
`tests/Pest.php` seeds it automatically per test, so nothing extra needed
there).

## 5. (Optional) Column-level field visibility

Row-level access (can the user open the Suppliers list at all) comes from
step 3–4. If specific *fields* on the module also need per-role hiding
(e.g. a "cost_price" column only some roles should see), you need to:

**a. Register the fields:**

Add entries to `database/seeders/ModuleFieldSeeder.php`:

```php
'suppliers' => [
    'name' => 'Name',
    'cost_price' => 'Cost Price',
    'contact_email' => 'Contact Email',
],
```

Then:

```
php artisan db:seed --class=ModuleFieldSeeder --force
```

This makes the fields selectable in the Role edit screen's
"Field Visibility" tab (`app/Filament/Resources/Roles/RoleResource.php`) —
admins can now tick which roles see which `suppliers` fields.

**b. ⚠️ Wire up enforcement — this part does NOT happen automatically.**

As of this writing, no existing resource actually checks
`role_field_permission` — the Field Visibility tab only manages the data.
You must add a `->visible()` closure to each field-restricted column/input
yourself. The pattern to copy:

```php
// In SuppliersTable.php / SupplierForm.php
TextColumn::make('cost_price')
    ->visible(fn (): bool => auth()->user()
        ?->roles
        ->flatMap(fn ($role) => $role->moduleFields)
        ->contains(fn ($f) => $f->module_key === 'suppliers' && $f->field_key === 'cost_price') ?? false),
```

That closure is verbose to repeat per field — if you're adding
field-visibility to more than one or two columns, add a small helper
first (e.g. a `canViewField(string $moduleKey, string $fieldKey): bool`
method on `App\Models\User`) rather than copy-pasting the query everywhere.

## 6. Test it

Follow the pattern in `tests/Filament/Resources/Users/Pages/CreateUserTest.php`
and `tests/Filament/Resources/Roles/Pages/CreateRoleTest.php`:

- A basic "can render the page" test for each new page
- A permission-denial test: create a user with **no** role, assert they
  get a 403 on the new resource's routes
- If you added field visibility: a test that a role without the field
  permission doesn't see that column/input, and one that a role with it
  does

Run just the new tests first:
```
php artisan test tests/Filament/Resources/Suppliers
```

Then the full suite before committing (it's slow — ~14 min — let it run
in the background):
```
php artisan test --compact
```

**Never run tests without `.env.testing` present** — it points at an
in-memory SQLite DB. Without it, `RefreshDatabase` runs against the real
MySQL `tms` database from `.env`.

## 7. Format + commit

```
vendor/bin/pint --dirty --format agent
```

Then commit — include the model/migration/resource files, the
policy, the regenerated `ShieldSeeder.php`, `ModuleFieldSeeder.php` changes
if any, and the new tests.

---

## Reference: what already exists

- **Roles/permissions engine:** `spatie/laravel-permission` +
  `bezhansalleh/filament-shield`
- **Role management UI:** `/shield/roles` — `app/Filament/Resources/Roles/`
  (published from Shield's package so it can be customized; the "Field
  Visibility" tab is custom, everything else is Shield's default)
- **User management UI:** `/users` — `app/Filament/Resources/Users/`
  (custom-built, includes role assignment via `CheckboxList`/`Select`)
- **Field-level visibility data model:** `App\Models\ModuleField`,
  `App\Models\Role::moduleFields()`, `role_field_permission` pivot table
- **super_admin:** a real role (not a config-level Gate bypass —
  `define_via_gate` is `false`) with every permission explicitly synced.
  Reseed it via `shield:seeder` + `db:seed --class=ShieldSeeder` whenever
  new permissions are added.
