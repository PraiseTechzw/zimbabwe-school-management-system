@props(['school' => null, 'academicYear' => null, 'compact' => false])

@php
    $schoolName = is_array($school) ? (string) ($school['name'] ?? '') : '';
    $schoolType = is_array($school) ? (string) ($school['type'] ?? '') : '';
@endphp

<div {{ $attributes->merge(['class' => 'fs-panel flex flex-wrap items-center gap-x-5 gap-y-2 px-3 py-2.5 text-[13px]']) }}>
    <div class="flex min-w-0 items-center gap-2">
        @if($schoolName !== '')
            <span class="font-medium">{{ $schoolName }}</span>
            @if($schoolType !== '')
                <span class="text-muted">{{ $schoolType }}</span>
            @endif
        @else
            <span class="text-muted">No school context</span>
        @endif
    </div>

    @if(! $compact)
        <div class="flex items-center gap-2">
            @if(is_array($academicYear) && ($academicYear['name'] ?? '') !== '')
                <span class="h-2 w-2 shrink-0 rounded-full bg-data" aria-hidden="true"></span>
                <span class="fs-figure">{{ $academicYear['name'] }}</span>
                <span class="fs-figure text-muted">{{ $academicYear['start_date'] }} – {{ $academicYear['end_date'] }}</span>
            @else
                <span class="text-muted">No current academic year</span>
            @endif
        </div>
    @endif
</div>
