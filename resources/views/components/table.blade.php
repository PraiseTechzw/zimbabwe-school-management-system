<div class="overflow-x-auto rounded-md border border-border">
    <table class="fs-table">
        @if(isset($header))
            <thead>{{ $header }}</thead>
        @endif
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
