<div class="fs-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="fs-table">
            @if(isset($header))
                <thead>{{ $header }}</thead>
            @endif
            <tbody>{{ $slot }}</tbody>
        </table>
    </div>
</div>
