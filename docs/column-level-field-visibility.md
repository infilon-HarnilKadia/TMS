# Column-level field visibility — how it works

This is a deep-dive on the custom system that lets an admin say "role X can
see the `phone` field on Customers, but not `credit_limit`." It's a
**separate, hand-built layer** — not part of `spatie/laravel-permission` or
`bezhansalleh/filament-shield`, which only understand whole
Resources/Pages/Widgets, not individual fields within one. If you haven't
read `docs/creating-a-module.md` yet, read that first — this file assumes
you already know how Shield's row-level permissions work.

## Why this couldn't just reuse Shield

Shield answers one question: *"can this user open the Customers page at
all?"* That's a single permission (`ViewAny:Customer`) checked once, before
the whole page renders. Field visibility is a different shape of question —
*"of the fields on Customers, which ones does this specific role see?"* —
answered per-field, potentially differently for every role, and Shield has
no concept of "field" at all. So this had to be a new data model, a new
admin UI, and (still pending) new enforcement code.

## The data model

Two new tables, two new/modified models.

### `module_fields` — the catalog

```php
// database/migrations/2026_08_07_122002_create_module_fields_table.php
Schema::create('module_fields', function (Blueprint $table) {
    $table->id();
    $table->string('module_key');   // e.g. 'customers'
    $table->string('field_key');    // e.g. 'phone'
    $table->string('label');        // e.g. 'Phone' — what admins see in the UI
    $table->timestamps();

    $table->unique(['module_key', 'field_key']);
});
```

This is just a flat catalog: "the `customers` module has a field called
`phone`." `module_key` is a plain string, not a foreign key to anything —
by convention it should match the target Filament Resource's slug, but
nothing enforces that. The unique constraint means you can't register the
same field twice for the same module (seeding is safe to re-run).

`App\Models\ModuleField` (`app/Models/ModuleField.php`) is a thin wrapper:

```php
class ModuleField extends Model
{
    protected $fillable = ['module_key', 'field_key', 'label'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_field_permission');
    }
}
```

### `role_field_permission` — the pivot

```php
// database/migrations/2026_08_07_122008_create_role_field_permission_table.php
Schema::create('role_field_permission', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->foreignId('module_field_id')->constrained()->cascadeOnDelete();

    $table->primary(['role_id', 'module_field_id']);
});
```

A plain many-to-many pivot, no extra columns. Composite primary key (no
surrogate `id`) since a row is just "this role can see this field" — there's
nothing else to store per-pairing. `cascadeOnDelete()` on both sides means
deleting a role or a module field automatically cleans up its pivot rows —
no orphaned permissions left behind.

### `App\Models\Role` — why a custom Role model exists

Spatie's own `Role` model (`Spatie\Permission\Models\Role`) has no idea
`role_field_permission` exists — it only knows about `role_has_permissions`.
So `app/Models/Role.php` extends it purely to bolt on the missing
relationship:

```php
class Role extends SpatieRole
{
    public function moduleFields(): BelongsToMany
    {
        return $this->belongsToMany(ModuleField::class, 'role_field_permission');
    }
}
```

For this to actually take effect, `config/permission.php` has to point
Spatie's `models.role` config at *this* class instead of its own default:

```php
'models' => [
    'role' => App\Models\Role::class,   // was Spatie\Permission\Models\Role::class
    ...
],
```

Everywhere in the app that resolves "the role model" — Spatie's own
internals, Shield's `Utils::getRoleModel()`, Filament resources — now gets
`App\Models\Role`, so `$role->moduleFields()` is available wherever a role
instance shows up. This is also why `RoleResource::getModel()` (in
`RoleResource.php`) returns `Utils::getRoleModel()` rather than hardcoding
a class — it's deliberately indirect so it always resolves to whichever
Role class is currently configured.

## The admin UI — Role edit screen's "Field Visibility" tab

This lives entirely inside `app/Filament/Resources/Roles/RoleResource.php`,
specifically `getFieldVisibilityFormComponent()`:

```php
public static function getFieldVisibilityFormComponent(): Section
{
    return Section::make('Field Visibility')
        ->schema(
            ModuleField::query()
                ->orderBy('module_key')->orderBy('field_key')
                ->get()
                ->groupBy('module_key')
                ->map(fn ($fields, $moduleKey) => Section::make(Str::headline($moduleKey))
                    ->collapsible()
                    ->schema([
                        CheckboxList::make("field_visibility.{$moduleKey}")
                            ->hiddenLabel()
                            ->options($fields->pluck('label', 'id'))
                            ->columns(2),
                    ]))
                ->values()
                ->toArray()
        );
}
```

Walking through it:

1. Pull **every** `ModuleField` row, sorted by module then field.
2. `groupBy('module_key')` — so all of `customers`'s fields end up together,
   all of `orders`'s fields together, etc.
3. For each group, render a collapsible `Section` (titled via
   `Str::headline('customers')` → "Customers") containing one
   `CheckboxList`.
4. The checkbox **options** are `$fields->pluck('label', 'id')` — so the
   value submitted per checked box is the `ModuleField`'s numeric **id**,
   and the label shown to the admin is the human-readable `label` column
   (`'Phone'`, not `'phone'`).
5. The field's **name** is `"field_visibility.{$moduleKey}"` — e.g.
   `field_visibility.customers`, `field_visibility.orders`. This dotted
   name is the key design decision explained below.

This whole method is called once, appended into `RoleResource::form()`
alongside the role's `name`/`guard_name` fields and Shield's own
permission-tree component (`static::getShieldFormComponents()`).

### Why `field_visibility.{moduleKey}`, not a direct `->relationship()` binding

Filament's `CheckboxList` has a built-in `->relationship('moduleFields', 'label')`
mode that would auto-load/auto-save a many-to-many relation with zero
extra code. That was deliberately **not** used here, because there are
*multiple* `CheckboxList` components on the same form (one per module) that
would all need to bind to the *same* underlying `moduleFields` relationship.
Filament's relationship-binding assumes one component owns the whole
relationship; two or more components each trying to sync their own subset
of the same relation stomp on each other — whichever one saves last wins,
silently discarding the other module's selections.

Using a non-existent, purely form-local key (`field_visibility.customers`)
sidesteps that entirely: Filament just treats it as ordinary nested form
state, doesn't try to persist it anywhere on its own, and the actual
database write is done by hand (next section). The tradeoff is that
loading and saving this data now has to be written manually instead of
"free" — that's the whole reason `EditRole.php`/`CreateRole.php` have the
extra lifecycle hooks below.

## Loading and saving — the page lifecycle hooks

Both `CreateRole.php` and `EditRole.php` follow the same three-part
pattern (Edit needs a fourth step, to pre-fill existing data).

### Loading existing selections (`EditRole` only)

```php
// app/Filament/Resources/Roles/Pages/EditRole.php
protected function mutateFormDataBeforeFill(array $data): array
{
    $data['field_visibility'] = $this->record
        ->moduleFields()
        ->get()
        ->groupBy('module_key')
        ->map(fn ($fields) => $fields->pluck('id')->toArray())
        ->toArray();

    return $data;
}
```

Filament calls this right before populating the form with a record's data.
Without it, the `field_visibility.*` checkboxes would always render empty
on edit (there's no such column on `roles` for Filament to read from). This
hook fetches the role's *actual* `moduleFields` from the pivot table,
re-groups them back into the same `['customers' => [1, 2], 'orders' => [5]]`
shape the form expects, and injects it into the data array Filament is
about to use to fill the form. `ViewRole.php` has an identical copy of this
hook, for the same reason on the read-only view page.

### Extracting the selections before save (`Create` and `Edit`)

```php
// EditRole.php (CreateRole.php's mutateFormDataBeforeCreate is identical in shape)
protected function mutateFormDataBeforeSave(array $data): array
{
    $this->fieldVisibility = $data['field_visibility'] ?? [];

    $this->permissions = collect($data)
        ->filter(fn ($v, $key) => ! in_array($key, [
            'name', 'guard_name', 'select_all', 'field_visibility', ...
        ], true))
        ->values()->flatten()->unique();

    return Arr::only($data, ['name', 'guard_name']);
}
```

This runs right before Filament tries to save the form data onto the
`Role` model. Two things happen:

1. `field_visibility` is stashed into a protected property (`$this->fieldVisibility`)
   so it survives into `afterSave()`/`afterCreate()`.
2. The **return value** is `Arr::only($data, ['name', 'guard_name'])` — i.e.
   everything else (`field_visibility`, the Shield permission checkboxes,
   `select_all`) is stripped out. This matters because `roles` only has
   `name`/`guard_name` (+ tenant key) as real columns; if `field_visibility`
   were left in the array, Filament's save would try to mass-assign it onto
   the `Role` model and throw (no such attribute).

(The `$this->permissions` collection built here is Shield's own logic,
unrelated to field visibility — it's gathering up which permission
checkboxes were ticked, for `afterSave` to `syncPermissions()` with. Worth
knowing it's there so you don't confuse it with the field-visibility flow
sitting right next to it.)

### Writing the pivot (`afterSave` / `afterCreate`)

```php
protected function afterSave(): void
{
    // ...Shield's permission sync happens first...

    $this->record->moduleFields()->sync(
        collect($this->fieldVisibility)->flatten()->unique()->all()
    );
}
```

By this point the `Role` record itself has been saved (it has an `id`).
`$this->fieldVisibility` is still shaped like
`['customers' => [1, 2], 'orders' => [5]]` — nested by module, exactly as
the form produced it. `->flatten()` collapses that into one flat list of
`ModuleField` ids (`[1, 2, 5]`), `->unique()` guards against duplicates,
and `->sync(...)` on the `moduleFields()` relationship does the actual
pivot-table write: any ids in the list get a `role_field_permission` row
created if missing, and any *existing* pivot rows for ids **not** in the
list get deleted. This is what makes unchecking a box on edit actually
remove that permission — `sync()` is a full replace, not an additive merge.

`CreateRole.php`'s `afterCreate()` is the same call, just triggered after
the initial insert instead of an update.

## Verifying this actually works

Two test files exercise the whole load/save round-trip:

- `tests/Filament/Resources/Roles/Pages/CreateRoleTest.php` — creates a
  role with `field_visibility.customers = [id1, id2]`, then asserts
  `$role->moduleFields()->pluck('field_key')` matches.
- `tests/Filament/Resources/Roles/Pages/EditRoleTest.php` — two tests:
  one asserts `assertFormSet(['field_visibility' => ['customers' => [$id]]])`
  correctly pre-fills from existing pivot data (proving the
  `mutateFormDataBeforeFill` hook works), the other changes the selection
  and asserts the *old* pivot row is gone and only the *new* one remains
  (proving `sync()`, not merge, is happening).

Both pass — `Tests: 483 passed` as of the last full suite run. This is
solid evidence the data model + admin UI genuinely work, not just that they
compile.

## What's still missing: enforcement

Everything above lets an admin **configure** field visibility. Nothing in
the app currently **reads** that configuration to actually hide anything.
Confirmed by grep — zero matches across every existing resource:

```
grep -rn "moduleFields\|module_key" app/Filament/Resources/Shop app/Filament/Resources/HR app/Filament/Resources/Blog
# (no output)
```

If you tick "Salesperson cannot see `customers.phone`" today and log in as
a Salesperson, the `phone` column still renders on the Customers table —
the checkbox you unchecked has no effect anywhere outside the Roles screen
itself.

### The pattern to wire it up, when you're ready

On the specific field you want to gate, in the resource's `Table` or `Form`
class:

```php
TextColumn::make('phone')
    ->visible(fn (): bool => auth()->user()
        ?->roles
        ->flatMap(fn ($role) => $role->moduleFields)
        ->contains(fn ($f) => $f->module_key === 'customers' && $f->field_key === 'phone')
        ?? false),
```

What this does: get the logged-in user's roles, flatten each role's
`moduleFields` into one collection, and check whether any of them match
this exact module+field pair. `?? false` covers the guest/no-roles case
safely.

This is expensive and verbose to repeat per field. Recommended next step
before applying this broadly: add a helper, e.g.

```php
// app/Models/User.php
public function canViewField(string $moduleKey, string $fieldKey): bool
{
    return $this->roles
        ->flatMap(fn ($role) => $role->moduleFields)
        ->contains(fn ($f) => $f->module_key === $moduleKey && $f->field_key === $fieldKey);
}
```

so call sites become `->visible(fn () => auth()->user()?->canViewField('customers', 'phone') ?? false)`.
Also worth considering, once this is applied to more than a couple of
resources: caching the current user's visible-field set for the request
(this recomputes the flatMap on every field check otherwise), and deciding
whether *forms* should also strip hidden fields from validation/dehydration
(Filament's `->visible(false)` on a form input already excludes it from
submission automatically — no extra work needed there, only table columns
need the explicit check since they're pure display).
