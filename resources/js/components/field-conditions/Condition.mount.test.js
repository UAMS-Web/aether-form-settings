import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Condition from './Condition.vue';

const suggestableFields = [
    { handle: 'department', config: { display: 'Department', type: 'text' } },
    { handle: 'newsletter', config: { display: 'Newsletter', type: 'toggle' } },
    { handle: 'topic', config: { display: 'Topic', type: 'select', options: { press: 'Press', sales: 'Sales' } } },
    { handle: 'first_name', config: { type: 'text' } },
    { handle: 'confirmation', config: { display: 'Confirmation', type: 'text' } },
];

const blank = { _id: 'a1', field: null, operator: 'equals', value: null };

/** Mount one condition row; `conditions` is the full list the parent passes. */
function mountCondition(condition = blank, props = {}) {
    return mount(Condition, {
        props: {
            config: { handle: 'confirmation' },
            condition,
            conditions: [condition],
            index: 0,
            suggestableFields,
            ...props,
        },
    });
}

/** The props a stub received, as the harness serializes them. */
function propsOf(stub) {
    return JSON.parse(stub.attributes('data-props'));
}

function comboboxes(wrapper) {
    return wrapper.findAll('[data-stub="Combobox"]');
}

describe('Condition field choices', () => {
    it('offers every field except the one holding the condition and the one already chosen', () => {
        const wrapper = mountCondition({ ...blank, field: 'department' });

        expect(propsOf(comboboxes(wrapper)[0]).options).toEqual([
            { value: 'newsletter', label: 'Newsletter' },
            { value: 'topic', label: 'Topic' },
            { value: 'first_name', label: 'First Name' },
        ]);
    });

    it('still offers a field another condition uses', () => {
        const rules = [{ ...blank, field: 'department' }, { ...blank, _id: 'b2', field: 'topic' }];
        const wrapper = mountCondition(rules[0], { conditions: rules });

        expect(propsOf(comboboxes(wrapper)[0]).options.map((option) => option.value)).toContain('topic');
    });

    it('reports the chosen field, keeping the rest of the condition', async () => {
        const wrapper = mountCondition();

        await wrapper.find('[data-option="topic"]').trigger('click');

        expect(wrapper.emitted('updated')).toEqual([[{ ...blank, field: 'topic' }]]);
    });
});

describe('Condition operator', () => {
    it('lists the operators, including includes_any', () => {
        const select = mountCondition().find('select[data-stub="Select"]');

        expect(propsOf(select).options.map((option) => option.value)).toEqual([
            'equals', 'not', 'contains', 'contains_any', 'includes_any', '===', '!==', '>', '>=', '<', '<=', 'custom',
        ]);
        expect(select.element.value).toBe('equals');
    });

    it('reports the chosen operator, keeping the rest of the condition', async () => {
        const wrapper = mountCondition();

        await wrapper.find('select[data-stub="Select"]').setValue('not');

        expect(wrapper.emitted('updated')).toEqual([[{ ...blank, operator: 'not' }]]);
    });
});

describe('Condition value control', () => {
    it('uses a text input for a plain field and saves the typed text', async () => {
        const wrapper = mountCondition({ ...blank, field: 'department', value: 'Billing' });
        const input = wrapper.find('input[data-stub="Input"]');

        expect(input.element.value).toBe('Billing');
        expect(wrapper.find('[data-stub="Switch"]').exists()).toBe(false);
        expect(comboboxes(wrapper)).toHaveLength(1);

        await input.setValue('Sales');

        expect(wrapper.emitted('updated')).toEqual([[{ ...wrapper.props('condition'), value: 'Sales' }]]);
    });

    it('uses a switch for a toggle field and saves the boolean as text', async () => {
        const wrapper = mountCondition({ ...blank, field: 'newsletter', value: 'true' });
        const toggle = wrapper.find('input[data-stub="Switch"]');

        expect(toggle.element.checked).toBe(true);

        await toggle.setValue(false);

        expect(wrapper.emitted('updated').at(-1)[0].value).toBe('false');
    });

    it('uses a dropdown of the field options for a select field', async () => {
        const wrapper = mountCondition({ ...blank, field: 'topic', value: 'press' });

        expect(comboboxes(wrapper)).toHaveLength(2);
        expect(propsOf(comboboxes(wrapper)[1]).options).toEqual([
            { value: 'press', label: 'Press' },
            { value: 'sales', label: 'Sales' },
        ]);

        await comboboxes(wrapper)[1].find('[data-option="sales"]').trigger('click');

        expect(wrapper.emitted('updated')).toEqual([[{ ...wrapper.props('condition'), value: 'sales' }]]);
    });

    it('falls back to a text input for a select field with an operator a dropdown cannot express', () => {
        const wrapper = mountCondition({ ...blank, field: 'topic', operator: 'contains', value: 'press' });

        expect(comboboxes(wrapper)).toHaveLength(1);
        expect(wrapper.find('input[data-stub="Input"]').exists()).toBe(true);
    });
});

describe('Condition removal and instructions', () => {
    it('reports removal from the trash button', async () => {
        const wrapper = mountCondition();
        const trash = wrapper.findAll('[data-stub="Button"]').find((b) => propsOf(b).icon === 'trash');

        await trash.trigger('click');

        expect(wrapper.emitted('removed')).toHaveLength(1);
    });

    it('draws the field instructions only on the first row', () => {
        expect(mountCondition(blank, { index: 0 }).find('[data-stub="Description"]').exists()).toBe(true);
        expect(mountCondition(blank, { index: 1 }).find('[data-stub="Description"]').exists()).toBe(false);
    });
});
