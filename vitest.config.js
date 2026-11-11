import vue from '@vitejs/plugin-vue';
import { configDefaults, defineConfig } from 'vitest/config';
import statamicStubs from './resources/js/testing/statamic-stubs-plugin.js';

// Two Vitest projects, so the existing tests keep their `node` environment:
//   - `scripts`: the repository's own Node scripts (the guards in `scripts/`) and the Control Panel's
//     plain JavaScript modules. The add-on's PHP tests run under Pest, not here.
//   - `components`: the mounted tests of the Control Panel's `.vue` files (`*.mount.test.js`), in
//     `jsdom`, with `@statamic/cms` and `@statamic/cms/ui` replaced by the stubs in
//     `resources/js/testing`. It uses `vue()` from `@vitejs/plugin-vue`, not the `statamic()` plugin,
//     which rewrites `vue` to `window.Vue`, a global that exists only in the Control Panel.
//
// `.claude/worktrees/` holds full checkouts of other branches, each carrying its own copy of every
// test file; without the exclude the primary collects them all.
const exclude = [...configDefaults.exclude, '.claude/worktrees/**'];

export default defineConfig({
    test: {
        projects: [
            {
                test: {
                    name: 'scripts',
                    include: ['scripts/**/*.test.mjs', 'resources/js/**/*.test.js'],
                    exclude: [...exclude, 'resources/js/**/*.mount.test.js'],
                    environment: 'node',
                },
            },
            {
                plugins: [vue(), statamicStubs({ sources: ['resources/js'] })],
                test: {
                    name: 'components',
                    include: ['resources/js/**/*.mount.test.js'],
                    exclude,
                    environment: 'jsdom',
                    setupFiles: [
                        'resources/js/testing/statamic-harness.js',
                        'resources/js/testing/addon-stubs.js',
                    ],
                },
            },
        ],
    },
});
