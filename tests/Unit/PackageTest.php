<?php

declare(strict_types=1);

use UAMSWeb\Aether\Forms\Settings\ServiceProvider;

/**
 * `composer.json` registers the add-on's service provider for Laravel package
 * discovery, which is how a host application boots it.
 *
 * @command  vendor/bin/pest tests/Unit/PackageTest.php
 */
it('lists its service provider in composer.json so the host discovers it', function (): void {
    // Read composer.json and confirm it was readable
    $contents = file_get_contents(__DIR__.'/../../composer.json');
    expect($contents)->toBeString();

    // Decode the manifest
    /** @var array{extra: array{laravel: array{providers: list<string>}}} $manifest */
    $manifest = json_decode((string) $contents, true, flags: JSON_THROW_ON_ERROR);

    // Expect the provider among the discovered providers
    expect($manifest['extra']['laravel']['providers'])->toContain(ServiceProvider::class);
});

/**
 * The PSR-4 prefix in `composer.json` is the add-on's public namespace, so a
 * rename that updates the files but not the autoload map must fail here.
 */
it('maps the Aether namespace to src and autoloads the provider from it', function (): void {
    // Read and decode the manifest
    $contents = file_get_contents(__DIR__.'/../../composer.json');
    expect($contents)->toBeString();

    /** @var array{name: string, autoload: array{'psr-4': array<string, string>}} $manifest */
    $manifest = json_decode((string) $contents, true, flags: JSON_THROW_ON_ERROR);

    // Expect the package and the namespace to carry the new name
    expect($manifest['name'])->toBe('uams-web/aether-form-settings')
        ->and($manifest['autoload']['psr-4'])->toBe(['UAMSWeb\\Aether\\Forms\\Settings\\' => 'src/']);

    // Expect the class to load through the autoloader, not an include
    expect(class_exists(ServiceProvider::class))->toBeTrue();
});
