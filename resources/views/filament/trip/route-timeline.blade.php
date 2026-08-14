@php
    /** @var array<int, array{label: string, caption: string, distance: ?string, tone: string}> $stops */
    $tones = [
        'start' => 'bg-primary-600',
        'via' => 'bg-blue-500',
        'end' => 'bg-orange-500',
    ];
@endphp

@if (blank($stops))
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Set an origin hub and destination to preview the route.
    </p>
@else
    <ol class="flex flex-col">
        @foreach ($stops as $index => $stop)
            @if ($stop['distance'])
                {{-- Connector carrying the leg distance --}}
                <li class="flex items-stretch gap-3">
                    <div class="flex w-3 shrink-0 justify-center">
                        <span class="w-0.5 bg-primary-600/40"></span>
                    </div>
                    <span class="py-1 text-xs font-medium text-primary-700 dark:text-primary-400">
                        {{ $stop['distance'] }}
                    </span>
                </li>
            @endif

            <li class="flex items-start gap-3">
                <span class="mt-1.5 flex w-3 shrink-0 justify-center">
                    <span @class(['h-3 w-3 rounded-full', $tones[$stop['tone']] ?? $tones['via']])></span>
                </span>

                <span class="flex min-w-0 flex-col gap-0.5 {{ $loop->last ? '' : 'pb-1' }}">
                    <span class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $stop['label'] }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $stop['caption'] }}
                    </span>
                </span>
            </li>
        @endforeach
    </ol>
@endif
