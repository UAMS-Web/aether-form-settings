import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import FormSettingsSubsectionFieldtype from './FormSettingsSubsectionFieldtype.vue';

describe('FormSettingsSubsectionFieldtype', () => {
    it('renders nothing: the field wrapper draws the heading', () => {
        const wrapper = mount(FormSettingsSubsectionFieldtype, {
            props: { handle: 'notifications_heading', value: null },
        });

        expect(wrapper.element.nodeType).toBe(Node.COMMENT_NODE);
        expect(wrapper.text()).toBe('');
        expect(wrapper.find('*').exists()).toBe(false);
    });

    it('takes the fieldtype props the Control Panel passes', () => {
        const wrapper = mount(FormSettingsSubsectionFieldtype, {
            props: { handle: 'notifications_heading', value: null, meta: { a: 1 }, config: { display: 'Heading' } },
        });

        expect(wrapper.props()).toMatchObject({
            handle: 'notifications_heading',
            meta: { a: 1 },
            config: { display: 'Heading' },
        });
    });
});
