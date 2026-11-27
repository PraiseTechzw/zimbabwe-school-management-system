@props(['message' => 'Something went wrong.'])

<div class="fs-card border-negative/30 px-4 py-3" role="alert">
    <p class="text-sm font-medium text-negative">{{ $message }}</p>
    @if(isset($retry))
        <div class="mt-2">{{ $retry }}</div>
    @endif
</div>
