// Vitest setup file: the stand-ins this add-on's components need beyond the shared harness
// (`statamic-harness.js`, a byte copy of the `testing-statamic-cp-components` skill's template).
//
// Each stub carries the props, slots and events the add-on's own markup uses and nothing of how
// Statamic renders or behaves. A passing test says a component works against these stand-ins, not
// that Statamic still behaves like them; the import-surface test (`statamic-imports.test.js`) and a
// browser run against the Control Panel cover that.
import { config } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { FieldtypeMixin, stubCore, stubUi } from './statamic-harness.js';

// `__()` is a Control Panel global used in templates and in script; the stand-in returns the key.
const translate = (key) => key;

globalThis.__ = translate;
config.global.mocks = { ...config.global.mocks, __: translate };

/**
 * Stand-in for `HasInputOptions`: turns an options object, a list of strings or a list of
 * `{ value, label }` into the `{ value, label }` list the selects take.
 */
function normalizeInputOptions(options) {
    if (Array.isArray(options)) {
        return options.map((option) => (option !== null && typeof option === 'object'
            ? { value: option.value ?? option.key, label: option.label ?? option.value ?? option.key }
            : { value: option, label: option }));
    }

    return Object.entries(options ?? {}).map(([value, label]) => ({ value, label }));
}

stubCore('HasInputOptionsMixin', { methods: { normalizeInputOptions } });

// `Fieldtype` is the composition-API entry a `<script setup>` fieldtype uses: the same props and
// emits as the mixin, and `use()` returning what the component exposes.
stubCore('Fieldtype', {
    props: FieldtypeMixin.props,
    emits: FieldtypeMixin.emits,
    use: () => ({ expose: {} }),
});

/** The model value, whichever spelling the parent used: `:model-value` or `v-model`. */
function modelOf(attrs) {
    return attrs['model-value'] ?? attrs.modelValue;
}

stubUi('Button', defineComponent({
    name: 'StubButton',
    inheritAttrs: false,
    emits: ['click'],
    render() {
        return h(
            'button',
            {
                type: 'button',
                'data-stub': 'Button',
                'data-props': JSON.stringify(this.$attrs),
                onClick: (event) => this.$emit('click', event),
            },
            this.$slots.default?.() ?? this.$attrs.text,
        );
    },
}));

stubUi('Field', defineComponent({
    name: 'StubField',
    inheritAttrs: false,
    render() {
        return h('div', { 'data-stub': 'Field', 'data-props': JSON.stringify(this.$attrs) }, [
            h('label', { 'data-field-label': '' }, this.$attrs.label),
            h('p', { 'data-field-instructions': '' }, this.$attrs.instructions),
            this.$slots.default?.(),
        ]);
    },
}));

stubUi('Input', defineComponent({
    name: 'StubInput',
    inheritAttrs: false,
    emits: ['update:modelValue'],
    render() {
        return h('input', {
            'data-stub': 'Input',
            'data-props': JSON.stringify(this.$attrs),
            type: this.$attrs.type ?? 'text',
            placeholder: this.$attrs.placeholder,
            value: modelOf(this.$attrs) ?? '',
            onInput: (event) => this.$emit('update:modelValue', event.target.value),
        });
    },
}));

stubUi('Select', defineComponent({
    name: 'StubSelect',
    inheritAttrs: false,
    emits: ['update:modelValue'],
    render() {
        const options = this.$attrs.options ?? [];

        return h(
            'select',
            {
                'data-stub': 'Select',
                'data-props': JSON.stringify(this.$attrs),
                value: modelOf(this.$attrs),
                onChange: (event) => this.$emit('update:modelValue', event.target.value),
            },
            options.map((option) => h('option', { value: option.value }, option.label)),
        );
    },
}));

stubUi('Switch', defineComponent({
    name: 'StubSwitch',
    inheritAttrs: false,
    emits: ['update:modelValue'],
    render() {
        return h('input', {
            type: 'checkbox',
            'data-stub': 'Switch',
            'data-props': JSON.stringify(this.$attrs),
            checked: modelOf(this.$attrs) === true,
            onChange: (event) => this.$emit('update:modelValue', event.target.checked),
        });
    },
}));

// A combobox: a search input (what the components read on close), one button per option rendered
// through the `option` slot, and the `selected-option` slot for the current value. Choosing an
// option emits `update:modelValue`; a test emits `update:open` on the component to close it.
stubUi('Combobox', defineComponent({
    name: 'StubCombobox',
    inheritAttrs: false,
    emits: ['update:modelValue', 'update:open'],
    render() {
        const options = this.$attrs.options ?? [];
        const current = modelOf(this.$attrs);
        // The selected-option slot renders only once there is a value, with the listed option or,
        // for a typed value, just `{ value }`: the Control Panel's own `Condition` template reads
        // `option.value` unguarded (statamic/cms v6.38.0 bundle, 2026-11-11).
        const selected = current === null || current === undefined || current === ''
            ? undefined
            : (options.find((option) => option.value === current) ?? { value: current });

        return h(
            'div',
            {
                'data-stub': 'Combobox',
                'data-props': JSON.stringify(this.$attrs),
                'data-model-value': JSON.stringify(current ?? null),
            },
            [
                h('input', { ref: 'search', 'data-search': '' }),
                h('div', { 'data-selected': '' }, selected ? this.$slots['selected-option']?.({ option: selected }) : undefined),
                ...options.map((option) => h(
                    'button',
                    {
                        type: 'button',
                        'data-option': String(option.value),
                        onClick: () => this.$emit('update:modelValue', option.value),
                    },
                    this.$slots.option?.(option),
                )),
            ],
        );
    },
}));
