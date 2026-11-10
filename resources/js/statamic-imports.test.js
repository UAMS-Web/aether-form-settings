import { readdirSync, readFileSync, statSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

/**
 * Pins the names the add-on imports from `@statamic/cms` and `@statamic/cms/ui`.
 *
 * Both package entries destructure a runtime global (`export const { Fieldtype } = __STATAMIC__.core`)
 * and the Statamic Vite plugin leaves the imports to the browser, so an export a Statamic release
 * drops builds cleanly and is `undefined` in the Control Panel. This reads the installed entries
 * (the `file:` link into `vendor/statamic/cms`), so it follows whatever Statamic version is locked.
 */
const jsRoot = dirname(fileURLToPath(import.meta.url));
const packageRoot = join(jsRoot, '..', '..', 'node_modules', '@statamic', 'cms');

/** Every `.vue` and `.js` source under `resources/js`, tests excluded. */
function sourceFiles(dir) {
	return readdirSync(dir).flatMap((name) => {
		const path = join(dir, name);

		if (statSync(path).isDirectory()) {
			return sourceFiles(path);
		}

		return /\.(vue|js)$/.test(name) && !name.endsWith('.test.js') ? [path] : [];
	});
}

/** The named imports of a source, as `{ specifier, name }` pairs; an alias reports the exported name. */
export function statamicImports(source) {
	const pattern = /import\s+(?:[\w$]+\s*,\s*)?\{([^}]*)\}\s*from\s*['"](@statamic\/cms(?:\/[\w./-]+)?)['"]/g;

	return [...source.matchAll(pattern)].flatMap(([, names, specifier]) => names
		.split(',')
		.map((name) => name.trim().split(/\s+as\s+/)[0])
		.filter((name) => name !== '')
		.map((name) => ({ specifier, name })));
}

/** The names an entry exports: destructured `export const { ... } =` blocks and plain declarations. */
export function exportedNames(entrySource) {
	const names = [];

	for (const [, body] of entrySource.matchAll(/export\s+const\s*\{([^}]*)\}\s*=/g)) {
		for (const part of body.split(',')) {
			const name = part.trim().split(':').pop().trim();

			if (name !== '') {
				names.push(name);
			}
		}
	}

	for (const [, name] of entrySource.matchAll(/export\s+(?:const|let|function|class)\s+([\w$]+)/g)) {
		names.push(name);
	}

	return names;
}

/** The imports no entry exports, as `specifier name` lines; `entries` maps a specifier to its names. */
export function missingImports(imports, entries) {
	return imports
		.filter(({ specifier, name }) => !(entries[specifier] ?? []).includes(name))
		.map(({ specifier, name }) => `${specifier} ${name}`);
}

/** The entry file a specifier resolves to, through the `exports` map of the installed package. */
function entryFile(specifier) {
	const manifest = JSON.parse(readFileSync(join(packageRoot, 'package.json'), 'utf8'));
	const key = specifier === '@statamic/cms' ? '.' : `./${specifier.slice('@statamic/cms/'.length)}`;
	const target = manifest.exports?.[key]?.default;

	if (typeof target !== 'string') {
		throw new Error(`The installed @statamic/cms exports map has no default entry for "${key}".`);
	}

	return join(packageRoot, target);
}

describe('names imported from @statamic/cms', () => {
	it('are all exported by the installed package', () => {
		const files = sourceFiles(jsRoot);

		expect(files.length).toBeGreaterThan(0);

		const imports = files.flatMap((file) => statamicImports(readFileSync(file, 'utf8'))
			.map((found) => ({ ...found, file: relative(jsRoot, file) })));

		expect(imports.length).toBeGreaterThan(0);

		const entries = {};

		for (const specifier of new Set(imports.map(({ specifier: found }) => found))) {
			entries[specifier] = exportedNames(readFileSync(entryFile(specifier), 'utf8'));
			expect(entries[specifier].length).toBeGreaterThan(0);
		}

		const missing = imports
			.filter(({ specifier, name }) => missingImports([{ specifier, name }], entries).length > 0)
			.map(({ file, specifier, name }) => `${file}: ${specifier} no longer exports ${name}`);

		expect(missing).toEqual([]);
	});
});

describe('the import check', () => {
	const entries = { '@statamic/cms': ['Fieldtype', 'FieldtypeMixin'], '@statamic/cms/ui': ['Button'] };

	it('reads default-plus-named and multi-line imports and checks an alias by its exported name', () => {
		const source = [
			"import Vue, { Button as Btn,",
			"\tSwitch } from '@statamic/cms/ui';",
			"import { FieldtypeMixin as Fieldtype } from '@statamic/cms';",
			"import { ref } from 'vue';",
		].join('\n');

		expect(statamicImports(source)).toEqual([
			{ specifier: '@statamic/cms/ui', name: 'Button' },
			{ specifier: '@statamic/cms/ui', name: 'Switch' },
			{ specifier: '@statamic/cms', name: 'FieldtypeMixin' },
		]);
	});

	it('reports a name the entry does not export, and only that name', () => {
		const imports = statamicImports("import { Button, Switch } from '@statamic/cms/ui';");

		expect(missingImports(imports, entries)).toEqual(['@statamic/cms/ui Switch']);
	});

	it('reports every name of a specifier no entry was read for', () => {
		const imports = statamicImports("import { Other } from '@statamic/cms/api';");

		expect(missingImports(imports, entries)).toEqual(['@statamic/cms/api Other']);
	});

	it('reads destructured, renamed and declared exports of an entry', () => {
		const entry = "export const {\n\tFieldtype,\n\tOld: Renamed,\n} = __STATAMIC__.core;\nexport function helper() {}\n";

		expect(exportedNames(entry)).toEqual(['Fieldtype', 'Renamed', 'helper']);
		expect(exportedNames('// nothing exported')).toEqual([]);
	});
});
