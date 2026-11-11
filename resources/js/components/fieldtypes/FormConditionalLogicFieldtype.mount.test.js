import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import FormConditionalLogicFieldtype from './FormConditionalLogicFieldtype.vue';

const suggestableFields = [
    { handle: 'department', config: { display: 'Department', type: 'text' } },
    { handle: 'topic', config: { display: 'Topic', type: 'select', options: { press: 'Press' } } },
];

/** Mount with the props the Control Panel always passes; `handle` is required by the mixin. */
function mountLogic(props = {}) {
    return mount(FormConditionalLogicFieldtype, {
        props: {
            handle: 'conditions',
            value: null,
            config: { handle: 'conditions' },
            meta: { suggestable_fields: suggestableFields },
            ...props,
        },
    });
}

/** The top-level selects: "when" first, then the condition type once conditions apply. */
function selects(wrapper) {
    return wrapper.findAll('select[data-stub="Select"]');
}

/** The rows' own inputs: one text value each for a plain field. */
function conditionRows(wrapper) {
    return wrapper.findAll('button[data-stub="Button"]')
        .filter((button) => JSON.parse(button.attributes('data-props')).icon === 'trash');
}

function button(wrapper, text) {
    return wrapper.findAll('[data-stub="Button"]').find((b) => b.text() === text);
}

/** The "Always Save" switch is the only switch in the second field block. */
function alwaysSaveSwitch(wrapper) {
    return wrapper.findAll('[data-stub="Field"]')[1].find('input[data-stub="Switch"]');
}

/** The last value passed to update(). */
function lastUpdate(wrapper) {
    return wrapper.emitted('update:value').at(-1)[0];
}

describe('FormConditionalLogicFieldtype stored value', () => {
    it('starts as always trigger, with no condition controls, when nothing is stored', () => {
        const wrapper = mountLogic();

        expect(selects(wrapper)).toHaveLength(1);
        expect(selects(wrapper)[0].element.value).toBe('always');
        expect(button(wrapper, 'Add Condition')).toBeUndefined();
        expect(conditionRows(wrapper)).toHaveLength(0);
        expect(alwaysSaveSwitch(wrapper).element.checked).toBe(false);
    });

    it('labels the two blocks and gives the default instructions', () => {
        const wrapper = mountLogic();

        expect(wrapper.findAll('[data-field-label]').map((label) => label.text())).toEqual(['Conditions', 'Always Save']);
        expect(wrapper.find('[data-field-instructions]').text())
            .toBe('Configure when this confirmation should be triggered based on form field values.');
    });

    it('takes the instructions from the field config', () => {
        const wrapper = mountLogic({ meta: { suggestable_fields: suggestableFields, config: { instructions: 'Pick a rule.' } } });

        expect(wrapper.find('[data-field-instructions]').text()).toBe('Pick a rule.');
    });

    it('loads a stored condition into an editable row', () => {
        const wrapper = mountLogic({ value: { if: { department: 'equals Billing' }, always_save: true } });

        expect(selects(wrapper)[0].element.value).toBe('if');
        expect(selects(wrapper)[1].element.value).toBe('all');
        expect(conditionRows(wrapper)).toHaveLength(1);
        expect(wrapper.find('input[data-stub="Input"]').element.value).toBe('Billing');
        expect(wrapper.find('[data-stub="Combobox"]').attributes('data-model-value')).toBe('"department"');
        expect(alwaysSaveSwitch(wrapper).element.checked).toBe(true);
    });

    it('reads an unless_any key as do-not-trigger-when with any condition passing', () => {
        const wrapper = mountLogic({ value: { unless_any: { department: 'equals Billing' } } });

        expect(selects(wrapper)[0].element.value).toBe('unless');
        expect(selects(wrapper)[1].element.value).toBe('any');
    });

    it('reads a stored method name as a custom condition', () => {
        const wrapper = mountLogic({ value: { if: 'isBillingQuestion' } });

        expect(selects(wrapper)[1].element.value).toBe('custom');
        expect(wrapper.find('input[data-stub="Input"]').element.value).toBe('isBillingQuestion');
        expect(conditionRows(wrapper)).toHaveLength(0);
    });

    it('keeps every condition a field has', () => {
        const wrapper = mountLogic({ value: { if: { department: ['equals Billing', 'equals Sales'] } } });

        expect(conditionRows(wrapper)).toHaveLength(2);
    });
});

describe('FormConditionalLogicFieldtype saving', () => {
    it('saves a condition typed into a row', async () => {
        const wrapper = mountLogic({ value: { if: { department: 'equals Billing' } } });

        await wrapper.find('input[data-stub="Input"]').setValue('Sales');

        expect(lastUpdate(wrapper)).toEqual({ if: { department: 'equals Sales' }, always_save: false });
    });

    it('saves under the any key when any condition may pass', async () => {
        const wrapper = mountLogic({ value: { if: { department: 'equals Billing' } } });

        await selects(wrapper)[1].setValue('any');

        expect(lastUpdate(wrapper)).toEqual({ if_any: { department: 'equals Billing' }, always_save: false });
    });

    it('saves under the unless key when the trigger is inverted', async () => {
        const wrapper = mountLogic({ value: { if: { department: 'equals Billing' } } });

        await selects(wrapper)[0].setValue('unless');

        expect(lastUpdate(wrapper)).toEqual({ unless: { department: 'equals Billing' }, always_save: false });
    });

    it('saves no conditions at all once the trigger is always', async () => {
        const wrapper = mountLogic({ value: { if: { department: 'equals Billing' } } });

        await selects(wrapper)[0].setValue('always');

        expect(lastUpdate(wrapper)).toEqual({ always_save: false });
    });

    it('saves the custom method name', async () => {
        const wrapper = mountLogic({ value: { if: 'isBillingQuestion' } });

        await wrapper.find('input[data-stub="Input"]').setValue('isSalesQuestion');

        expect(lastUpdate(wrapper)).toEqual({ if: 'isSalesQuestion', always_save: false });
    });

    it('saves the always-save flag', async () => {
        const wrapper = mountLogic({ value: { if: { department: 'equals Billing' } } });

        await alwaysSaveSwitch(wrapper).setValue(true);

        expect(lastUpdate(wrapper)).toEqual({ if: { department: 'equals Billing' }, always_save: true });
    });

    it('leaves out a condition that has no field or no value', async () => {
        const wrapper = mountLogic({ value: { if: { department: 'equals Billing' } } });

        await button(wrapper, 'Add Condition').trigger('click');

        expect(conditionRows(wrapper)).toHaveLength(2);
        expect(lastUpdate(wrapper)).toEqual({ if: { department: 'equals Billing' }, always_save: false });
    });

    it('drops a removed condition from the saved value', async () => {
        const wrapper = mountLogic({ value: { if: { department: ['equals Billing', 'equals Sales'] } } });

        await conditionRows(wrapper)[0].trigger('click');

        expect(conditionRows(wrapper)).toHaveLength(1);
        expect(lastUpdate(wrapper)).toEqual({ if: { department: 'equals Sales' }, always_save: false });
    });
});
