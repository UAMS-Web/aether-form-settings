import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import FormEmailFieldSelectFieldtype from './FormEmailFieldSelectFieldtype.vue';

const emailFields = [
    { value: 'email', label: 'Email', type: 'text' },
    { value: 'contact', label: 'Contact Address' },
];

/** Mount with the props the Control Panel always passes; `handle` is required by the mixin. */
function mountSelect(props = {}) {
    return mount(FormEmailFieldSelectFieldtype, {
        props: { handle: 'send_to', value: null, meta: { email_fields: emailFields }, ...props },
    });
}

/** The props the combobox stand-in received, as the harness serializes them. */
function comboboxProps(wrapper) {
    return JSON.parse(wrapper.find('[data-stub="Combobox"]').attributes('data-props'));
}

/** Type into the combobox's search input, then close the list. */
async function typeAndClose(wrapper, text) {
    wrapper.find('input[data-search]').element.value = text;
    wrapper.findComponent({ name: 'StubCombobox' }).vm.$emit('update:open', false);
    await wrapper.vm.$nextTick();
}

describe('FormEmailFieldSelectFieldtype options', () => {
    it('builds one option per email field, keeping only its value and label', () => {
        expect(comboboxProps(mountSelect()).options).toEqual([
            { value: 'email', label: 'Email' },
            { value: 'contact', label: 'Contact Address' },
        ]);
    });

    it('offers no options when meta lists no email fields', () => {
        expect(comboboxProps(mountSelect({ meta: { email_fields: [] } })).options).toEqual([]);
        expect(comboboxProps(mountSelect({ meta: {} })).options).toEqual([]);
    });

    it('renders each option with its label and its field handle', () => {
        const option = mountSelect().find('[data-option="contact"]');

        expect(option.findAll('span').map((span) => span.text())).toEqual(['Contact Address', 'contact']);
    });

    it('lets a typed address through and labels the combobox', () => {
        const props = comboboxProps(mountSelect());

        expect(props.taggable).toBe(true);
        expect(props.placeholder).toBe('Select an email field');
    });
});

describe('FormEmailFieldSelectFieldtype selection', () => {
    it('shows the label of a stored field handle', () => {
        const wrapper = mountSelect({ value: 'contact' });

        expect(wrapper.find('[data-model-value]').attributes('data-model-value')).toBe('"contact"');
        expect(wrapper.find('[data-selected]').text()).toBe('Contact Address');
    });

    it('shows a stored address that is not a listed field as typed', () => {
        expect(mountSelect({ value: 'owner@example.test' }).find('[data-selected]').text())
            .toBe('owner@example.test');
    });

    it('passes the chosen field handle to update()', async () => {
        const wrapper = mountSelect();

        await wrapper.find('[data-option="contact"]').trigger('click');

        expect(wrapper.emitted('update:value')).toEqual([['contact']]);
    });
});

describe('FormEmailFieldSelectFieldtype typed value', () => {
    it('commits the trimmed text when the list closes', async () => {
        const wrapper = mountSelect();

        await typeAndClose(wrapper, '  owner@example.test  ');

        expect(wrapper.emitted('update:value')).toEqual([['owner@example.test']]);
    });

    it('commits nothing when the list closes with nothing typed', async () => {
        const wrapper = mountSelect();

        await typeAndClose(wrapper, '');

        expect(wrapper.emitted('update:value')).toBeUndefined();
    });

    it('commits nothing when the typed text is only whitespace', async () => {
        const wrapper = mountSelect();

        await typeAndClose(wrapper, '   ');

        expect(wrapper.emitted('update:value')).toBeUndefined();
    });

    it('commits nothing while the list opens', async () => {
        const wrapper = mountSelect();

        wrapper.find('input[data-search]').element.value = 'owner@example.test';
        wrapper.findComponent({ name: 'StubCombobox' }).vm.$emit('update:open', true);
        await wrapper.vm.$nextTick();

        expect(wrapper.emitted('update:value')).toBeUndefined();
    });
});
