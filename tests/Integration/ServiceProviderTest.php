<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;
use Statamic\Facades\Addon;
use UAMSWeb\Aether\Forms\Settings\ServiceProvider;

/**
 * The add-on boots inside a minimal Statamic.
 *
 * @command  vendor/bin/pest tests/Integration/ServiceProviderTest.php
 */
it('is discovered by Statamic as an add-on', function (): void {
    // Expect the add-on among those Statamic booted
    expect(Addon::all()->map->package()->all())->toContain('uams-web/aether-form-settings');
});

it('publishes its config file under the tag the README names', function (): void {
    // Read the paths published under the config tag
    $paths = ServiceProvider::pathsToPublish(null, 'aether-form-settings-config');

    // Expect the package config to publish to the app's config directory
    expect(array_map(realpath(...), array_keys($paths)))->toBe([realpath(__DIR__.'/../../config/aether-form-settings.php')])
        ->and(array_values($paths))->toBe([config_path('aether-form-settings.php')]);
});

it('registers its field views in the statamic namespace after the host overrides', function (): void {
    $hints = mustBe(FileViewFinder::class, View::getFinder(), 'view finder')->getHints()['statamic'] ?? [];

    // Expect the add-on's directory, exactly as the finder stores it, among the namespace paths
    expect(array_map(realpath(...), $hints))->toContain(realpath(__DIR__.'/../../resources/field-views'));
});

it('keeps a host override first and the add-on views before the rest, whichever way a hint is stored', function (): void {
    // A temp tree per run keeps parallel workers apart.
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'aether-hints-'.uniqid('', true);
    $override = $root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'statamic';
    mkdir($override, 0777, true);
    mkdir($root.DIRECTORY_SEPARATOR.'sub');

    $originalFinder = View::getFinder();
    $paths = config('view.paths');

    try {
        config(['view.paths' => [$root]]);

        // The same directory spelled with a `..`, as a finder that stores hints as given would hold it.
        $spelled = $root.DIRECTORY_SEPARATOR.'sub'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'statamic';

        // A finder that keeps hints exactly as given, which is how Laravel releases before v13.39 stored them.
        View::setFinder(new class(new Filesystem, [$root]) extends FileViewFinder
        {
            #[Override]
            public function replaceNamespace($namespace, $hints): void
            {
                $this->hints[$namespace] = (array) $hints;
            }
        });
        View::replaceNamespace('statamic', ['/elsewhere', $spelled, '/after']);

        $method = new ReflectionMethod(ServiceProvider::class, 'addStatamicViewsAfterHostOverrides');
        $method->invoke(app()->getProvider(ServiceProvider::class), '/addon');

        expect(mustBe(FileViewFinder::class, View::getFinder(), 'view finder')->getHints()['statamic'])->toBe([$spelled, '/addon', '/elsewhere', '/after']);
    } finally {
        config(['view.paths' => $paths]);
        View::setFinder($originalFinder);
        rmdir($root.DIRECTORY_SEPARATOR.'sub');
        rmdir($override);
        rmdir($root.DIRECTORY_SEPARATOR.'vendor');
        rmdir($root);
    }
});
