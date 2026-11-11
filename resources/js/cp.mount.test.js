import { readdirSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { beforeAll, describe, expect, it, vi } from 'vitest';
import FormConditionalLogicFieldtype from './components/fieldtypes/FormConditionalLogicFieldtype.vue';
import FormEmailFieldSelectFieldtype from './components/fieldtypes/FormEmailFieldSelectFieldtype.vue';
import FormEmailRoutingFieldtype from './components/fieldtypes/FormEmailRoutingFieldtype.vue';
import FormSettingsSubsectionFieldtype from './components/fieldtypes/FormSettingsSubsectionFieldtype.vue';
import LikertScaleFieldtype from './components/fieldtypes/LikertScaleFieldtype.vue';

/**
 * Pins the component handles `cp.js` registers against the handles the PHP fieldtypes resolve.
 *
 * Statamic looks a fieldtype's Vue component up by `<handle>-fieldtype`, so a handle that drifts
 * from its PHP class registers a component nothing asks for. The expected handles are read from
 * the PHP classes, not repeated here, so the two cannot drift apart unnoticed.
 */
const jsRoot = dirname(fileURLToPath(import.meta.url));
const fieldtypesDir = join(jsRoot, '..', '..', 'src', 'Fieldtypes');

const registered = new Map();

beforeAll(async () => {
    vi.stubGlobal('Statamic', {
        booting: (callback) => callback(),
        $components: { register: (handle, component) => registered.set(handle, component) },
    });

    await import('./cp.js');
});

/** `FormEmailRouting` to `form_email_routing`, the handle Statamic derives from the class name. */
function snake(className) {
    return className.replace(/(?<!^)([A-Z])/g, '_$1').toLowerCase();
}

/**
 * The component handle a PHP fieldtype resolves: its `$component` property, else the handle
 * Statamic derives from the class name. A fieldtype that overrides `component()` is skipped,
 * because it names a built-in Statamic component rather than one this add-on registers.
 */
function phpComponents() {
    return readdirSync(fieldtypesDir)
        .filter((file) => file.endsWith('.php'))
        .flatMap((file) => {
            const source = readFileSync(join(fieldtypesDir, file), 'utf8');

            if (/function\s+component\s*\(/.test(source)) {
                return [];
            }

            const declared = source.match(/protected\s+\$component\s*=\s*'([^']+)'/);

            return [declared ? declared[1] : snake(file.replace(/\.php$/, ''))];
        })
        .sort();
}

describe('cp.js component registration', () => {
    it('registers the five fieldtype components, each under its handle', () => {
        expect(Object.fromEntries(registered)).toEqual({
            'form_conditional_logic-fieldtype': FormConditionalLogicFieldtype,
            'form_email_field_select-fieldtype': FormEmailFieldSelectFieldtype,
            'form_email_routing-fieldtype': FormEmailRoutingFieldtype,
            'form_settings_subsection-fieldtype': FormSettingsSubsectionFieldtype,
            'likert_scale-fieldtype': LikertScaleFieldtype,
        });
    });

    it('registers exactly the components the PHP fieldtypes resolve', () => {
        const handles = [...registered.keys()].map((handle) => handle.replace(/-fieldtype$/, '')).sort();

        expect(phpComponents()).toHaveLength(5);
        expect(handles).toEqual(phpComponents());
    });

    it('suffixes every registered handle with -fieldtype', () => {
        expect([...registered.keys()].every((handle) => handle.endsWith('-fieldtype'))).toBe(true);
    });
});
