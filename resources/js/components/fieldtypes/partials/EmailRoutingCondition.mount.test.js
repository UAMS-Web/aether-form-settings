import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EmailRoutingCondition from './EmailRoutingCondition.vue';

const suggestableFields = [
    { handle: 'department', config: { display: 'Department', type: 'text' } },
    { handle: 'newsletter', config: { display: 'Newsletter', type: 'toggle' } },
    { handle: 'topic', config: { display: 'Topic', type: 'select', options: { press: 'Press', sales: 'Sales' } } },
    { handle: 'first_name', config: { type: 'text' } },
    { handle: 'routing', config: { display: 'Routing', type: 'text' } },
];

const blank = { _id: 'a1', field: null, operator: 'equals', value: null, email: null };

/** Mount one rule row; `conditions` is the full rule list the parent passes. */
function mountRule(condition = blank, props = {}) {
    return mount(EmailRoutingCondition, {
        props: {
            config: { handle: 'routing' },
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

/** The field combobox is the first one; the value dropdown is the second. */
function comboboxes(wrapper) {
    return wrapper.findAll('[data-stub="Combobox"]');
}

/** The last payload of the `updated` event. */
function lastUpdated(wrapper) {
    return wrapper.emitted('updated').at(-1)[0];
}

describe('EmailRoutingCondition field choices', () => {
    it('offers every field except the one holding the rule and those other rules use', () => {
        const rules = [{ ...blank, field: 'department' }, { ...blank, _id: 'b2', field: 'topic' }];
        const wrapper = mountRule(rules[0], { conditions: rules });

        expect(propsOf(comboboxes(wrapper)[0]).options).toEqual([
            { value: 'newsletter', label: 'Newsletter' },
            { value: 'first_name', label: 'First Name' },
        ]);
    });

    it('offers every other field to a blank rule, naming a field with no display from its handle', () => {
        expect(propsOf(comboboxes(mountRule())[0]).options.map((option) => option.label))
            .toEqual(['Department', 'Newsletter', 'Topic', 'First Name']);
    });

    it('shows the display name of the field the rule uses', () => {
        const wrapper = mountRule({ ...blank, field: 'department' });

        expect(comboboxes(wrapper)[0].attributes('data-model-value')).toBe('"department"');
        expect(wrapper.find('[data-selected]').text()).toBe('Department');
    });

    it('reports the chosen field, keeping the rest of the rule', async () => {
        const wrapper = mountRule();

        await wrapper.find('[data-option="topic"]').trigger('click');

        expect(wrapper.emitted('updated')).toEqual([[{ ...blank, field: 'topic' }]]);
    });

    it('commits a typed field handle, trimmed, when the list closes', async () => {
        const wrapper = mountRule();

        wrapper.find('input[data-search]').element.value = ' custom_field ';
        wrapper.findAllComponents({ name: 'StubCombobox' })[0].vm.$emit('update:open', false);

        expect(wrapper.emitted('updated')).toEqual([[{ ...blank, field: 'custom_field' }]]);
    });

    it('commits nothing when the list closes with nothing typed', () => {
        const wrapper = mountRule();

        wrapper.findAllComponents({ name: 'StubCombobox' })[0].vm.$emit('update:open', false);

        expect(wrapper.emitted('updated')).toBeUndefined();
    });
});

describe('EmailRoutingCondition operator', () => {
    it('lists the operators the rule can use', () => {
        const select = mountRule().find('select[data-stub="Select"]');

        expect(propsOf(select).options.map((option) => option.value)).toEqual([
            'equals', 'not', 'contains', 'contains_any', '===', '!==', '>', '>=', '<', '<=', 'custom',
        ]);
        expect(select.element.value).toBe('equals');
    });

    it('reports the chosen operator, keeping the rest of the rule', async () => {
        const wrapper = mountRule();

        await wrapper.find('select[data-stub="Select"]').setValue('contains');

        expect(wrapper.emitted('updated')).toEqual([[{ ...blank, operator: 'contains' }]]);
    });
});

describe('EmailRoutingCondition value control', () => {
    it('uses a text input for a plain field', async () => {
        const wrapper = mountRule({ ...blank, field: 'department', value: 'Billing' });
        const input = wrapper.findAll('input[data-stub="Input"]').find((i) => i.attributes('type') !== 'email');

        expect(input.element.value).toBe('Billing');
        expect(wrapper.find('[data-stub="Switch"]').exists()).toBe(false);
        expect(comboboxes(wrapper)).toHaveLength(1);

        await input.setValue('Sales');

        expect(lastUpdated(wrapper)).toEqual({ ...blank, field: 'department', value: 'Sales' });
    });

    it('uses a switch for a toggle field and saves true or false as text', async () => {
        const wrapper = mountRule({ ...blank, field: 'newsletter', value: 'true' });
        const toggle = wrapper.find('input[data-stub="Switch"]');

        expect(toggle.element.checked).toBe(true);
        expect(comboboxes(wrapper)).toHaveLength(1);

        await toggle.setValue(false);
        expect(lastUpdated(wrapper).value).toBe('false');

        await toggle.setValue(true);
        expect(lastUpdated(wrapper).value).toBe('true');
    });

    it('falls back to a text input for a toggle field with an operator a switch cannot express', () => {
        const wrapper = mountRule({ ...blank, field: 'newsletter', operator: 'contains', value: 'true' });

        expect(wrapper.find('[data-stub="Switch"]').exists()).toBe(false);
    });

    it('uses a dropdown of the field options for a select field', async () => {
        const wrapper = mountRule({ ...blank, field: 'topic', value: 'press' });

        expect(comboboxes(wrapper)).toHaveLength(2);
        expect(propsOf(comboboxes(wrapper)[1]).options).toEqual([
            { value: 'press', label: 'Press' },
            { value: 'sales', label: 'Sales' },
        ]);
        expect(propsOf(comboboxes(wrapper)[1]).taggable).toBe(false);

        await comboboxes(wrapper)[1].find('[data-option="sales"]').trigger('click');

        expect(lastUpdated(wrapper)).toEqual({ ...blank, field: 'topic', value: 'sales' });
    });
});

describe('EmailRoutingCondition email and removal', () => {
    it('shows the stored address and saves an edit, keeping the rest of the rule', async () => {
        const rule = { ...blank, field: 'department', value: 'Billing', email: 'billing@example.test' };
        const wrapper = mountRule(rule);
        const email = wrapper.find('input[type="email"]');

        expect(email.element.value).toBe('billing@example.test');
        expect(email.attributes('placeholder')).toBe('Email address(es)');

        await email.setValue('accounts@example.test');

        expect(wrapper.emitted('updated')).toEqual([[{ ...rule, email: 'accounts@example.test' }]]);
    });

    it('reports removal from the trash button', async () => {
        const wrapper = mountRule();
        const trash = wrapper.findAll('[data-stub="Button"]').find((b) => propsOf(b).icon === 'trash');

        await trash.trigger('click');

        expect(wrapper.emitted('removed')).toHaveLength(1);
    });

    it('draws the field instructions only on the first row', () => {
        expect(mountRule(blank, { index: 0 }).find('[data-stub="Description"]').exists()).toBe(true);
        expect(mountRule(blank, { index: 1 }).find('[data-stub="Description"]').exists()).toBe(false);
    });
});
