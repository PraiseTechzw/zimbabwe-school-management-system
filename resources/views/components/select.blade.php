@props([
    'name' => '',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'error' => null,
])

@php($id = 'field-'.$name)

<div>
    @if($label !== null)
        <label for="{{ $id }}" class="fs-label">{{ $label }}</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}"
            @if($error !== null) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->merge(['class' => 'fs-input']) }}>
        @if($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $option)
            <option value="{{ $option['value'] }}" @selected((string) $option['value'] === (string) $selected)>{{ $option['label'] }}</option>
        @endforeach
    </select>
    @if($error !== null)
        <p id="{{ $id }}-error" class="fs-error" role="alert">{{ $error }}</p>
    @endif
</div>
