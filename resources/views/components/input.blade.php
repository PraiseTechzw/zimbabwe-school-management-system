@props([
    'name' => '',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'error' => null,
    'help' => null,
    'autocomplete' => null,
    'required' => false,
])

@php($id = 'field-'.$name)

<div>
    @if($label !== null)
        <label for="{{ $id }}" class="fs-label">{{ $label }}</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
           @if($autocomplete !== null) autocomplete="{{ $autocomplete }}" @endif
           @if($required) required aria-required="true" @endif
           @if($error !== null) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($help !== null) aria-describedby="{{ $id }}-help" @endif
           {{ $attributes->merge(['class' => 'fs-input']) }} />
    @if($error !== null)
        <p id="{{ $id }}-error" class="fs-error" role="alert">{{ $error }}</p>
    @elseif($help !== null)
        <p id="{{ $id }}-help" class="fs-help">{{ $help }}</p>
    @endif
</div>
