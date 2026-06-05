{{-- resources/views/vendor/backpack/ui/fields/phone.blade.php --}}

<label>{!! $field['label'] !!}</label>

{{-- Render required asterisk dynamically if active in validation --}}
@if(isset($field['attributes']['required']) || (isset($field['show_asterisk']) && $field['show_asterisk'] === true))
<span class="text-danger">*</span>
@endif

<div class="input-group">
    <!-- Phone Prefix Dropdown Selection -->
    <select
        name="{{ $field['name'] }}_prefix"
        class="form-select"
        style="max-width: 110px;" disabled>

        @php
        $prefixes = $field['prefixes'] ?? [
        '+60' => '+60',
        ];
        $selectedPrefix = old($field['name'].'_prefix') ?? $field['value_prefix'] ?? $field['default_prefix'] ?? '';
        @endphp
        @foreach($prefixes as $value => $label)
        <option value="{{ $value }}" {{ $selectedPrefix == $value ? 'selected' : '' }}>
            {{ $label }}
        </option>
        @endforeach
    </select>

    <!-- Main Phone Number Entry Area -->
    <input
        type="text"
        name="{{ $field['name'] }}"
        class="form-control"
        value="{{ old(square_brackets_to_dots($field['name'])) ?? $field['value'] ?? $field['default'] ?? '' }}">
</div>

{{-- Field helper text / hints --}}
@if (isset($field['hint']))
<div class="form-text text-muted">{!! $field['hint'] !!}</div>
@endif