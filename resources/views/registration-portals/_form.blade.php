@php
    $isEdit = isset($portal);
    $action = $isEdit ? route('registration-portals.update', $portal) : route('registration-portals.store');
    $p = $portal ?? null;
    $selectedTemplate = old('template', 'conference');
    $existingFields = old('fields');
    if (is_string($existingFields)) {
        $existingFields = json_decode($existingFields, true);
    }
    if (! is_array($existingFields)) {
        $existingFields = $portalFields ?? [];
    }
    $templates = $templates ?? [];
@endphp
<form method="POST" action="{{ $action }}" class="space-y-6" id="registrationPortalForm">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="fields" id="fieldsPayload" value="">

    @unless($isEdit)
        <div>
            <x-input-label value="1. Choose a starting template" />
            <p class="mt-1 text-sm text-gray-500">Click a template to load starter fields. You can add, edit, or remove fields below.</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3" id="templatePicker">
                @foreach ([
                    'conference' => ['Church Conference', 'Name, contact, church, gender, age group'],
                    'youth' => ['Youth / Camp', 'Youth details + medical notes'],
                    'quick' => ['Quick Event', 'Fastest: name, contact, gender, age group'],
                ] as $key => $meta)
                    <button
                        type="button"
                        class="template-card text-left rounded-lg border-2 p-4 transition {{ $selectedTemplate === $key ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950/40' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-400' }}"
                        data-template="{{ $key }}"
                        aria-pressed="{{ $selectedTemplate === $key ? 'true' : 'false' }}"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-semibold text-sm">{{ $meta[0] }}</div>
                            <span class="template-check text-indigo-600 text-sm {{ $selectedTemplate === $key ? '' : 'hidden' }}">✓</span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">{{ $meta[1] }}</div>
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="template" id="templateInput" value="{{ $selectedTemplate }}">
            <p id="templateHint" class="mt-2 text-sm text-indigo-700 dark:text-indigo-300"></p>
            <x-input-error :messages="$errors->get('template')" />
        </div>
    @endunless

    <div>
        <x-input-label for="event_name" value="{{ $isEdit ? 'Event name' : '2. Event name' }}" />
        <x-text-input id="event_name" name="event_name" class="block mt-1 w-full" required :value="old('event_name', $p?->event_name ?? '')" placeholder="e.g. Solution Week 2026" />
        <x-input-error :messages="$errors->get('event_name')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="slug" value="Public link slug" />
            <x-text-input id="slug" name="slug" class="block mt-1 w-full font-mono text-sm" :value="old('slug', $p?->slug ?? '')" placeholder="auto-generated" />
            <p class="mt-1 text-xs text-gray-500">Form URL: <span class="font-mono">/register/<span id="slugPreview">{{ old('slug', $p?->slug ?? 'your-event') }}</span></span></p>
        </div>
        <div>
            <x-input-label for="venue" value="Venue (optional)" />
            <x-text-input id="venue" name="venue" class="block mt-1 w-full" :value="old('venue', $p?->venue ?? '')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="start_date" value="Start date (optional)" />
            <x-text-input id="start_date" name="start_date" type="date" class="block mt-1 w-full" :value="old('start_date', $p?->start_date?->format('Y-m-d') ?? '')" />
        </div>
        <div>
            <x-input-label for="end_date" value="End date (optional)" />
            <x-text-input id="end_date" name="end_date" type="date" class="block mt-1 w-full" :value="old('end_date', $p?->end_date?->format('Y-m-d') ?? '')" />
        </div>
    </div>

    @if($isEdit)
        <div>
            <x-input-label for="description" value="Description" />
            <textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('description', $p?->description ?? '') }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><x-input-label for="contact_email" value="Contact email" /><x-text-input id="contact_email" name="contact_email" type="email" class="block mt-1 w-full" :value="old('contact_email', $p?->contact_email ?? '')" /></div>
            <div><x-input-label for="contact_phone" value="Contact phone" /><x-text-input id="contact_phone" name="contact_phone" class="block mt-1 w-full" :value="old('contact_phone', $p?->contact_phone ?? '')" /></div>
        </div>
        <div><x-input-label for="max_registrants" value="Max registrants" /><x-text-input id="max_registrants" name="max_registrants" type="number" min="0" class="block mt-1 w-full" :value="old('max_registrants', $p?->max_registrants ?? '')" /></div>
    @endif

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-sm">{{ $isEdit ? 'Form fields' : '3. Form fields' }}</h3>
                <p class="text-xs text-gray-500 mt-1">Add any fields you need. Gender and Age group help dashboard Male/Female/Children/Teens counts.</p>
            </div>
            <button type="button" id="addFieldBtn" class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">+ Add field</button>
        </div>
        <div id="fieldsBuilder" class="space-y-3"></div>
        <x-input-error :messages="$errors->get('fields')" />
    </div>

    <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-4 space-y-3">
        <div>
            <x-input-label for="status" value="Publish status" />
            <select id="status" name="status" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach($statuses as $st)
                    <option value="{{ $st }}" @selected(old('status', $p?->status ?? 'open') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        @unless($isEdit)
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" id="publishNow" class="rounded border-gray-300" @checked(old('status', 'open') === 'open')>
                <span>Publish now (set status to Open)</span>
            </label>
        @endunless
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">{{ $isEdit ? 'Save changes' : 'Create registration form' }}</button>
        <a href="{{ route('registration-portals.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a>
    </div>
</form>

<script type="application/json" id="registration-portal-form-boot">
{!! json_encode([
    'templates' => $templates,
    'initialFields' => array_values($existingFields),
    'isEdit' => (bool) $isEdit,
    'selectedTemplate' => $selectedTemplate,
    'slugTouched' => (bool) ($isEdit || old('slug')),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}
</script>
<script>
(() => {
    const boot = JSON.parse(document.getElementById('registration-portal-form-boot').textContent);
    const templates = boot.templates;
    const initialFields = boot.initialFields;
    const isEdit = boot.isEdit;
    const selectedTemplate = boot.selectedTemplate;

    const fieldsBuilder = document.getElementById('fieldsBuilder');
    const fieldsPayload = document.getElementById('fieldsPayload');
    const templateInput = document.getElementById('templateInput');
    const templateHint = document.getElementById('templateHint');
    const nameInput = document.getElementById('event_name');
    const slugInput = document.getElementById('slug');
    const slugPreview = document.getElementById('slugPreview');
    const publishNow = document.getElementById('publishNow');
    const statusSelect = document.getElementById('status');
    const form = document.getElementById('registrationPortalForm');
    let slugTouched = Boolean(boot.slugTouched);
    let fields = [];

    const fieldTypes = [
        { value: 'text', label: 'Text' },
        { value: 'email', label: 'Email' },
        { value: 'phone', label: 'Phone' },
        { value: 'number', label: 'Number' },
        { value: 'date', label: 'Date' },
        { value: 'textarea', label: 'Long text' },
        { value: 'select', label: 'Dropdown' },
        { value: 'radio', label: 'Radio buttons' },
        { value: 'file', label: 'File upload' },
    ];

    function slugify(value) {
        return value.toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 80) || 'event';
    }

    function keyify(label, fallback) {
        const base = (label || fallback || 'field').toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        return base || ('field_' + Math.random().toString(36).slice(2, 6));
    }

    function normalizeTemplateField(field) {
        return {
            field_key: field.field_key || keyify(field.label, 'field'),
            field_type: field.field_type || 'text',
            label: field.label || field.field_key || 'Field',
            is_required: !!field.is_required,
            field_width: field.field_width || 'full',
            options: Array.isArray(field.options) ? field.options : (Array.isArray(field.options_json) ? field.options_json : []),
            placeholder: field.placeholder || '',
        };
    }

    function loadTemplate(name, announce) {
        const list = (templates[name] || templates.conference || []).map(normalizeTemplateField);
        fields = list;
        if (templateInput) templateInput.value = name;
        document.querySelectorAll('.template-card').forEach((card) => {
            const active = card.dataset.template === name;
            card.classList.toggle('border-indigo-600', active);
            card.classList.toggle('bg-indigo-50', active);
            card.classList.toggle('dark:bg-indigo-950/40', active);
            card.classList.toggle('border-gray-200', !active);
            card.classList.toggle('dark:border-gray-700', !active);
            card.setAttribute('aria-pressed', active ? 'true' : 'false');
            const check = card.querySelector('.template-check');
            if (check) check.classList.toggle('hidden', !active);
        });
        if (announce && templateHint) {
            templateHint.textContent = 'Loaded “' + name + '” fields — customize them below, then enter the event name and create.';
        }
        renderFields();
    }

    function renderFields() {
        if (!fieldsBuilder) return;
        if (!fields.length) {
            fieldsBuilder.innerHTML = '<p class="text-sm text-gray-500">No fields yet. Click a template or Add field.</p>';
            return;
        }

        fieldsBuilder.innerHTML = fields.map((field, index) => {
            const optionsValue = (field.options || []).join(', ');
            const typeOptions = fieldTypes.map((t) =>
                '<option value="' + t.value + '"' + (field.field_type === t.value ? ' selected' : '') + '>' + t.label + '</option>'
            ).join('');
            return (
                '<div class="rounded-md border border-gray-200 dark:border-gray-700 p-3 grid gap-3 sm:grid-cols-12 items-start" data-index="' + index + '">' +
                    '<div class="sm:col-span-4"><label class="text-xs text-gray-500">Label</label>' +
                    '<input type="text" class="field-label mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm" value="' + escapeAttr(field.label) + '"></div>' +
                    '<div class="sm:col-span-3"><label class="text-xs text-gray-500">Type</label>' +
                    '<select class="field-type mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">' + typeOptions + '</select></div>' +
                    '<div class="sm:col-span-3"><label class="text-xs text-gray-500">Options (comma-separated)</label>' +
                    '<input type="text" class="field-options mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm" value="' + escapeAttr(optionsValue) + '" placeholder="Male, Female"></div>' +
                    '<div class="sm:col-span-2 flex flex-col gap-2 pt-5">' +
                        '<label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" class="field-required rounded border-gray-300"' + (field.is_required ? ' checked' : '') + '> Required</label>' +
                        '<button type="button" class="field-remove text-left text-xs text-red-600 hover:underline">Remove</button>' +
                    '</div>' +
                '</div>'
            );
        }).join('');

        fieldsBuilder.querySelectorAll('[data-index]').forEach((row) => {
            const index = Number(row.dataset.index);
            row.querySelector('.field-label')?.addEventListener('input', (e) => {
                fields[index].label = e.target.value;
                fields[index].field_key = keyify(e.target.value, fields[index].field_key);
            });
            row.querySelector('.field-type')?.addEventListener('change', (e) => {
                fields[index].field_type = e.target.value;
            });
            row.querySelector('.field-options')?.addEventListener('input', (e) => {
                fields[index].options = e.target.value.split(',').map((s) => s.trim()).filter(Boolean);
            });
            row.querySelector('.field-required')?.addEventListener('change', (e) => {
                fields[index].is_required = e.target.checked;
            });
            row.querySelector('.field-remove')?.addEventListener('click', () => {
                fields.splice(index, 1);
                renderFields();
            });
        });
    }

    function escapeAttr(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function syncSlug() {
        if (!nameInput || !slugInput || slugTouched) return;
        const next = slugify(nameInput.value || '');
        slugInput.value = next;
        if (slugPreview) slugPreview.textContent = next || 'your-event';
    }

    document.querySelectorAll('.template-card').forEach((card) => {
        card.addEventListener('click', () => {
            loadTemplate(card.dataset.template, true);
        });
    });

    document.getElementById('addFieldBtn')?.addEventListener('click', () => {
        fields.push({
            field_key: 'custom_field_' + (fields.length + 1),
            field_type: 'text',
            label: 'Custom field ' + (fields.length + 1),
            is_required: false,
            field_width: 'full',
            options: [],
            placeholder: '',
        });
        renderFields();
    });

    nameInput?.addEventListener('input', syncSlug);
    slugInput?.addEventListener('input', () => {
        slugTouched = true;
        if (slugPreview) slugPreview.textContent = slugInput.value || 'your-event';
    });
    publishNow?.addEventListener('change', () => {
        if (!statusSelect) return;
        statusSelect.value = publishNow.checked ? 'open' : 'draft';
    });

    form?.addEventListener('submit', () => {
        if (fieldsPayload) {
            fieldsPayload.value = JSON.stringify(fields.map((f) => ({
                field_key: f.field_key || keyify(f.label, 'field'),
                field_type: f.field_type || 'text',
                label: f.label,
                is_required: !!f.is_required,
                field_width: f.field_width || 'full',
                options: f.options || [],
                placeholder: f.placeholder || '',
            })));
        }
    });

    if (isEdit && initialFields.length) {
        fields = initialFields.map(normalizeTemplateField);
        renderFields();
    } else if (initialFields.length) {
        fields = initialFields.map(normalizeTemplateField);
        renderFields();
        if (templateInput) templateInput.value = selectedTemplate;
    } else {
        loadTemplate(selectedTemplate || 'conference', false);
    }
    syncSlug();
})();
</script>
