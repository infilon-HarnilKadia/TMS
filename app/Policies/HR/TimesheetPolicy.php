<?php

declare(strict_types=1);

namespace App\Policies\HR;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HR\Timesheet;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimesheetPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Timesheet');
    }

    public function view(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('View:Timesheet');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Timesheet');
    }

    public function update(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('Update:Timesheet');
    }

    public function delete(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('Delete:Timesheet');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Timesheet');
    }

    public function restore(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('Restore:Timesheet');
    }

    public function forceDelete(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('ForceDelete:Timesheet');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Timesheet');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Timesheet');
    }

    public function replicate(AuthUser $authUser, Timesheet $timesheet): bool
    {
        return $authUser->can('Replicate:Timesheet');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Timesheet');
    }

}