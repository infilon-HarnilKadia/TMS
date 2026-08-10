<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","DeleteAny:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:Author","View:Author","Create:Author","Update:Author","Delete:Author","DeleteAny:Author","Restore:Author","ForceDelete:Author","ForceDeleteAny:Author","RestoreAny:Author","Replicate:Author","Reorder:Author","ViewAny:PostCategory","View:PostCategory","Create:PostCategory","Update:PostCategory","Delete:PostCategory","DeleteAny:PostCategory","Restore:PostCategory","ForceDelete:PostCategory","ForceDeleteAny:PostCategory","RestoreAny:PostCategory","Replicate:PostCategory","Reorder:PostCategory","ViewAny:Post","View:Post","Create:Post","Update:Post","Delete:Post","DeleteAny:Post","Restore:Post","ForceDelete:Post","ForceDeleteAny:Post","RestoreAny:Post","Replicate:Post","Reorder:Post","ViewAny:Department","View:Department","Create:Department","Update:Department","Delete:Department","DeleteAny:Department","Restore:Department","ForceDelete:Department","ForceDeleteAny:Department","RestoreAny:Department","Replicate:Department","Reorder:Department","ViewAny:Employee","View:Employee","Create:Employee","Update:Employee","Delete:Employee","DeleteAny:Employee","Restore:Employee","ForceDelete:Employee","ForceDeleteAny:Employee","RestoreAny:Employee","Replicate:Employee","Reorder:Employee","ViewAny:Expense","View:Expense","Create:Expense","Update:Expense","Delete:Expense","DeleteAny:Expense","Restore:Expense","ForceDelete:Expense","ForceDeleteAny:Expense","RestoreAny:Expense","Replicate:Expense","Reorder:Expense","ViewAny:LeaveRequest","View:LeaveRequest","Create:LeaveRequest","Update:LeaveRequest","Delete:LeaveRequest","DeleteAny:LeaveRequest","Restore:LeaveRequest","ForceDelete:LeaveRequest","ForceDeleteAny:LeaveRequest","RestoreAny:LeaveRequest","Replicate:LeaveRequest","Reorder:LeaveRequest","ViewAny:Project","View:Project","Create:Project","Update:Project","Delete:Project","DeleteAny:Project","Restore:Project","ForceDelete:Project","ForceDeleteAny:Project","RestoreAny:Project","Replicate:Project","Reorder:Project","ViewAny:Task","View:Task","Create:Task","Update:Task","Delete:Task","DeleteAny:Task","Restore:Task","ForceDelete:Task","ForceDeleteAny:Task","RestoreAny:Task","Replicate:Task","Reorder:Task","ViewAny:Timesheet","View:Timesheet","Create:Timesheet","Update:Timesheet","Delete:Timesheet","DeleteAny:Timesheet","Restore:Timesheet","ForceDelete:Timesheet","ForceDeleteAny:Timesheet","RestoreAny:Timesheet","Replicate:Timesheet","Reorder:Timesheet","ViewAny:Brand","View:Brand","Create:Brand","Update:Brand","Delete:Brand","DeleteAny:Brand","Restore:Brand","ForceDelete:Brand","ForceDeleteAny:Brand","RestoreAny:Brand","Replicate:Brand","Reorder:Brand","ViewAny:ProductCategory","View:ProductCategory","Create:ProductCategory","Update:ProductCategory","Delete:ProductCategory","DeleteAny:ProductCategory","Restore:ProductCategory","ForceDelete:ProductCategory","ForceDeleteAny:ProductCategory","RestoreAny:ProductCategory","Replicate:ProductCategory","Reorder:ProductCategory","ViewAny:Customer","View:Customer","Create:Customer","Update:Customer","Delete:Customer","DeleteAny:Customer","Restore:Customer","ForceDelete:Customer","ForceDeleteAny:Customer","RestoreAny:Customer","Replicate:Customer","Reorder:Customer","ViewAny:Order","View:Order","Create:Order","Update:Order","Delete:Order","DeleteAny:Order","Restore:Order","ForceDelete:Order","ForceDeleteAny:Order","RestoreAny:Order","Replicate:Order","Reorder:Order","ViewAny:Product","View:Product","Create:Product","Update:Product","Delete:Product","DeleteAny:Product","Restore:Product","ForceDelete:Product","ForceDeleteAny:Product","RestoreAny:Product","Replicate:Product","Reorder:Product","View:Dashboard","View:HrDashboard","View:ShopDashboard","View:FeaturesOverview","View:ShopKpisStats","View:OrdersYearOverYearChart","View:CustomerGrowthChart","View:FlaggedOrders","View:TopProductsByRevenueChart","View:CustomerSegmentsChart","View:OrderValueDistributionChart","View:ProductMarginAnalysisChart","View:WorkforceInsightsStats","View:DepartmentLeaveLoadChart","View:ProjectHealthChart","View:UtilizationRateChart","View:BudgetBurnRateChart"]},{"name":"Salesperson","guard_name":"web","permissions":[]}]';
        $directPermissions = '[]';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = '';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var Model $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = collect($rolePlusPermission['permissions'])
                    ->map(fn ($permission) => $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]))
                    ->all();

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
