import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import FormEmailRoutingFieldtype from './FormEmailRoutingFieldtype.vue';

const suggestableFields = [
    { handle: 'department', config: { display: 'Department', type: 'text' } },
    { handle: 'topic', config: { display: 'Topic', type: 'text' } },
];

const stored = [
    { field: 'department', operator: 'equals', value: 'Billing', email: 'billing@example.test' },
    { field: 'topic', operator: 'contains', value: 'press', email: 'press@example.test' },
];

/** Mount with the props the Control Panel always passes; `handle` is required by the mixin. */
function mountRouting(props = {}) {
    return mount(FormEmailRoutingFieldtype, {
        props: {
            handle: 'routing',
            value: null,
            config: { handle: 'routing' },
            meta: { suggestable_fields: suggestableFields },
            ...props,
        },
    });
}

/** One rule row per email input; each row renders exactly one. */
function rows(wrapper) {
    return wrapper.findAll('input[type="email"]');
}

/** The last value passed to update(). */
function lastUpdate(wrapper) {
    return wrapper.emitted('update:value').at(-1)[0];
}

/** A named button: the Add Rule button has text, a row's trash button has an icon. */
function button(wrapper, predicate) {
    return wrapper.findAll('[data-stub="Button"]').find(predicate);
}

describe('FormEmailRoutingFieldtype initial rules', () => {
    it('seeds one blank rule when nothing is stored', () => {
        const wrapper = mountRouting();

        expect(rows(wrapper)).toHaveLength(1);
        expect(rows(wrapper)[0].element.value).toBe('');
        expect(wrapper.text()).not.toContain('No routing rules configured.');
    });

    it('replaces the seeded rule with the stored rules, in order', () => {
        const wrapper = mountRouting({ value: stored });

        expect(rows(wrapper).map((row) => row.element.value)).toEqual(['billing@example.test', 'press@example.test']);
    });

    it('shows the empty state, and no rows, for a stored empty list', () => {
        const wrapper = mountRouting({ value: [] });

        expect(rows(wrapper)).toHaveLength(0);
        expect(wrapper.text()).toContain('No routing rules configured.');
    });

    it('always offers Add Rule', () => {
        expect(button(mountRouting({ value: [] }), (b) => b.text() === 'Add Rule')).toBeDefined();
        expect(button(mountRouting({ value: stored }), (b) => b.text() === 'Add Rule')).toBeDefined();
    });

    it('draws the field instructions on the first row only', () => {
        const wrapper = mountRouting({ value: stored });

        expect(wrapper.findAll('[data-stub="Description"]')).toHaveLength(1);
    });
});

describe('FormEmailRoutingFieldtype saving', () => {
    it('adds a blank rule with Add Rule and saves the rules without their row ids', async () => {
        const wrapper = mountRouting({ value: stored });

        await button(wrapper, (b) => b.text() === 'Add Rule').trigger('click');

        expect(rows(wrapper)).toHaveLength(3);
        expect(lastUpdate(wrapper)).toEqual([
            ...stored,
            { field: null, operator: 'equals', value: null, email: null },
        ]);
        expect(lastUpdate(wrapper).every((rule) => !('_id' in rule))).toBe(true);
    });

    it('removes the rule whose trash button is clicked', async () => {
        const wrapper = mountRouting({ value: stored });
        const trash = wrapper.findAll('[data-stub="Button"]').filter((b) => JSON.parse(b.attributes('data-props')).icon === 'trash');

        expect(trash).toHaveLength(2);

        await trash[0].trigger('click');

        expect(rows(wrapper).map((row) => row.element.value)).toEqual(['press@example.test']);
        expect(lastUpdate(wrapper)).toEqual([stored[1]]);
    });

    it('saves an edited email and keeps the rest of the rule', async () => {
        const wrapper = mountRouting({ value: stored });

        await rows(wrapper)[1].setValue('media@example.test');

        expect(lastUpdate(wrapper)).toEqual([stored[0], { ...stored[1], email: 'media@example.test' }]);
    });

    it('saves a changed operator', async () => {
        const wrapper = mountRouting({ value: stored });

        await wrapper.findAll('select[data-stub="Select"]')[0].setValue('not');

        expect(lastUpdate(wrapper)).toEqual([{ ...stored[0], operator: 'not' }, stored[1]]);
    });

    it('saves an emptied list after the last rule is removed', async () => {
        const wrapper = mountRouting({ value: [stored[0]] });
        const trash = wrapper.findAll('[data-stub="Button"]').find((b) => JSON.parse(b.attributes('data-props')).icon === 'trash');

        await trash.trigger('click');

        expect(lastUpdate(wrapper)).toEqual([]);
        expect(wrapper.text()).toContain('No routing rules configured.');
    });
});
