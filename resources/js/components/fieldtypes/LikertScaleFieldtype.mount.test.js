import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LikertScaleFieldtype from './LikertScaleFieldtype.vue';

const options = [
    { value: 1, label: 'Disagree' },
    { value: 2, label: 'Neutral' },
    { value: 3, label: 'Agree' },
];

/** Mount with the props the Control Panel always passes; `handle` is required by the mixin. */
function mountLikert(props = {}) {
    return mount(LikertScaleFieldtype, {
        props: { handle: 'rating', value: null, meta: { options }, ...props },
    });
}

/** The labels of the options inside one scope, in order. */
function labels(scope) {
    return scope.findAll('[data-stub="Radio"]').map((radio) => radio.text());
}

/** The props a stub received, as the harness serializes them. */
function propsOf(stub) {
    return JSON.parse(stub.attributes('data-props'));
}

describe('LikertScaleFieldtype single row', () => {
    it('renders one option per scale point, in order', () => {
        const wrapper = mountLikert();

        expect(wrapper.findAll('[data-stub="RadioGroup"]')).toHaveLength(1);
        expect(labels(wrapper)).toEqual(['Disagree', 'Neutral', 'Agree']);
        expect(wrapper.find('.likert-scale-row').exists()).toBe(false);
    });

    it('passes the stored value to the group as its model value', () => {
        const wrapper = mountLikert({ value: 2 });

        expect(wrapper.find('[data-stub="RadioGroup"]').attributes('data-model-value')).toBe('2');
    });

    it('passes the clicked scale point to update()', async () => {
        const wrapper = mountLikert();

        await wrapper.findAll('[data-stub="Radio"]')[2].trigger('click');

        expect(wrapper.emitted('update:value')).toEqual([[3]]);
    });

    it('lays the options out inline unless meta turns it off', () => {
        expect(propsOf(mountLikert().find('[data-stub="RadioGroup"]')).inline).toBe(true);
        expect(propsOf(mountLikert({ meta: { options, inline: false } }).find('[data-stub="RadioGroup"]')).inline)
            .toBe(false);
    });

    it('renders nothing selectable when meta carries no options', () => {
        const wrapper = mountLikert({ meta: {} });

        expect(wrapper.find('[data-stub="RadioGroup"]').exists()).toBe(true);
        expect(wrapper.findAll('[data-stub="Radio"]')).toHaveLength(0);
    });

    it('disables the options when the field is read only', () => {
        const wrapper = mountLikert({ readOnly: true });
        const radios = wrapper.findAll('[data-stub="Radio"]');

        expect(radios).toHaveLength(3);
        expect(radios.every((radio) => radio.attributes('disabled') !== undefined)).toBe(true);
    });

    it('leaves the options enabled when the field is editable', () => {
        const radios = mountLikert().findAll('[data-stub="Radio"]');

        expect(radios).toHaveLength(3);
        expect(radios.every((radio) => radio.attributes('disabled') === undefined)).toBe(true);
    });
});

describe('LikertScaleFieldtype matrix', () => {
    const matrix = {
        value: { Speed: 1, Quality: 2 },
        meta: { options, enable_multiple_rows: true, multiple_rows: ['Speed', 'Quality'] },
    };

    it('renders one labeled group per row, each with every option', () => {
        const wrapper = mountLikert(matrix);
        const rows = wrapper.findAll('.likert-scale-row');

        expect(rows.map((row) => row.find('.likert-scale-row-label').text())).toEqual(['Speed', 'Quality']);
        expect(rows.map(labels)).toEqual([
            ['Disagree', 'Neutral', 'Agree'],
            ['Disagree', 'Neutral', 'Agree'],
        ]);
    });

    it('passes each row its own stored point', () => {
        const groups = mountLikert(matrix).findAll('[data-stub="RadioGroup"]');

        expect(groups.map((group) => group.attributes('data-model-value'))).toEqual(['1', '2']);
    });

    it('keeps the other rows when one matrix row changes', async () => {
        const wrapper = mountLikert(matrix);

        await wrapper.findAll('.likert-scale-row')[1].findAll('[data-stub="Radio"]')[2].trigger('click');

        expect(wrapper.emitted('update:value')).toEqual([[{ Speed: 1, Quality: 3 }]]);
    });

    it('starts a map when the stored value is not one yet', async () => {
        const wrapper = mountLikert({ ...matrix, value: null });

        await wrapper.findAll('.likert-scale-row')[0].findAll('[data-stub="Radio"]')[1].trigger('click');

        expect(wrapper.emitted('update:value')).toEqual([[{ Speed: 2 }]]);
    });

    it('falls back to the single row when the matrix has no row labels', () => {
        const wrapper = mountLikert({ meta: { options, enable_multiple_rows: true, multiple_rows: [] } });

        expect(wrapper.find('.likert-scale-row').exists()).toBe(false);
        expect(wrapper.findAll('[data-stub="RadioGroup"]')).toHaveLength(1);
    });

    it('disables every row when the field is read only', () => {
        const radios = mountLikert({ ...matrix, readOnly: true }).findAll('[data-stub="Radio"]');

        expect(radios).toHaveLength(6);
        expect(radios.every((radio) => radio.attributes('disabled') !== undefined)).toBe(true);
    });
});
