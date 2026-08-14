@php
    /** @var \App\Models\User|null $user */
    $user = filament()->auth()->user();

    $name = $user?->name ?: 'Guest';

    $initials = \Illuminate\Support\Str::of($name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => \Illuminate\Support\Str::upper(mb_substr($part, 0, 1)))
        ->implode('');

    $role = method_exists($user, 'getRoleNames')
        ? $user?->getRoleNames()->first()
        : null;
@endphp

<div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">
        {{ $initials ?: '?' }}
    </span>

    <span class="flex min-w-0 flex-col leading-tight">
        <span class="truncate text-sm font-medium text-white">
            {{ $name }}
        </span>
        <span class="truncate text-xs text-gray-400">
            {{ $role ? \Illuminate\Support\Str::headline($role) : 'No role assigned' }}
        </span>
    </span>
</div>
