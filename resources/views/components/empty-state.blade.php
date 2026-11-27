@props(['title' => 'Nothing here yet', 'message' => null])

<div class="fs-panel px-4 py-8 text-center">
    <p class="text-sm font-medium">{{ $title }}</p>
    @if($message !== null)
        <p class="mx-auto mt-1 max-w-md text-[13px] text-muted">{{ $message }}</p>
    @endif
    @if(isset($action))
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>
