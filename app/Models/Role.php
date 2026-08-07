<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /** @return BelongsToMany<ModuleField, $this> */
    public function moduleFields(): BelongsToMany
    {
        return $this->belongsToMany(ModuleField::class, 'role_field_permission');
    }
}
