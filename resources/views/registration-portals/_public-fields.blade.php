@forelse ($fields as $field)
    @php
        $key = (string) $field['field_key'];
        $type = (string) $field['field_type'];
        $width = in_array(($field['field_width'] ?? 'full'), ['half', 'third'], true) ? 'rp-field--half' : '';
        $required = !empty($field['is_required']);
        $disabledAttr = !empty($disabled) ? ' disabled' : '';
        $options = $field['options'] ?? [];
    @endphp
    <div class="rp-field {{ $width }}">
        @if ($type === 'radio')
            <span class="rp-field__legend">{{ $field['label'] }}@if ($required) * @endif</span>
            <div class="rp-radio-group" role="radiogroup" aria-label="{{ $field['label'] }}">
                @foreach ($options as $optIndex => $opt)
                    <label class="rp-radio" for="f_{{ $key }}_{{ $optIndex }}">
                        <input
                            type="radio"
                            id="f_{{ $key }}_{{ $optIndex }}"
                            name="{{ $key }}"
                            value="{{ $opt }}"
                            @if ($required) required @endif{!! $disabledAttr !!}
                        >
                        <span>{{ $opt }}</span>
                    </label>
                @endforeach
            </div>
        @elseif ($type === 'textarea')
            <label for="f_{{ $key }}">{{ $field['label'] }}@if ($required) * @endif</label>
            <textarea id="f_{{ $key }}" name="{{ $key }}" placeholder="{{ $field['placeholder'] ?? '' }}" @if ($required) required @endif{!! $disabledAttr !!}></textarea>
        @elseif (in_array($type, ['select', 'state', 'country', 'church', 'ministry', 'marital_status'], true))
            <label for="f_{{ $key }}">{{ $field['label'] }}@if ($required) * @endif</label>
            <select id="f_{{ $key }}" name="{{ $key }}" @if ($required) required @endif{!! $disabledAttr !!}>
                <option value="">Select…</option>
                @foreach ($options as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        @elseif (in_array($type, ['file', 'passport'], true))
            <label for="f_{{ $key }}">{{ $field['label'] }}@if ($required) * @endif</label>
            <input type="file" id="f_{{ $key }}" name="{{ $key }}" accept="image/jpeg,image/png,image/webp,application/pdf" @if ($required) required @endif{!! $disabledAttr !!}>
        @else
            <label for="f_{{ $key }}">{{ $field['label'] }}@if ($required) * @endif</label>
            <input
                type="{{ in_array($type, ['email', 'phone', 'number', 'date'], true) ? ($type === 'phone' ? 'tel' : $type) : 'text' }}"
                id="f_{{ $key }}"
                name="{{ $key }}"
                placeholder="{{ $field['placeholder'] ?? '' }}"
                @if ($required) required @endif{!! $disabledAttr !!}
            >
        @endif
        @if (!empty($field['help_text']))
            <small>{{ $field['help_text'] }}</small>
        @endif
    </div>
@empty
    <p class="rp-public__notice">No registration fields have been configured for this portal yet.</p>
@endforelse
