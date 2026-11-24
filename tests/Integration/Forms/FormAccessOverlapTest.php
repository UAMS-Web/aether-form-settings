<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Stache;
use Statamic\Fields\BlueprintRepository;
use Statamic\Forms\Connectors\ConnectionLogic;
use Statamic\Stache\Stores\Store;

/**
 * Proves a form closed by Statamic 6.44's own Access settings (`close_date`, `submission_limit`)
 * and a form closed by both refuse a direct POST, and that the two
 * together behave as documented (UAMS-Web/aether-form-settings#118): core refuses first, with
 * its own closed message. The add-on's own refusals are covered by FormRestrictionsEnforcementTest,
 * which runs on both releases.
 *
 * Statamic releases before 6.44 have no Access settings, so every case skips there.
 *
 * @covers \UAMSWeb\Aether\Forms\Settings\Listeners\EnforceFormRestrictions
 *
 * @command  vendor/bin/pest tests/Integration/Forms/FormAccessOverlapTest.php
 */
$accessFormsDir = '';
$accessSubsDir = '';
$accessHandle = '';

beforeEach(function () use (&$accessFormsDir, &$accessSubsDir, &$accessHandle): void {
    // Unique IP per test, so each test's posts get their own rate-limit bucket
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.'.random_int(0, 255).'.'.random_int(1, 254)]);

    $accessHandle = 'access_overlap_probe_'.str_replace('.', '', uniqid('', true));

    $accessFormsDir = storage_path('framework/testing/forms-'.uniqid('', true));
    File::ensureDirectoryExists($accessFormsDir);
    Config::set('statamic.forms.forms', $accessFormsDir);

    $accessSubsDir = storage_path('framework/testing/subs-'.uniqid('', true));
    File::ensureDirectoryExists($accessSubsDir);
    Config::set('statamic.forms.submissions', $accessSubsDir);
    /** @var Store $submissionsStore */
    $submissionsStore = Stache::store('form-submissions');
    $submissionsStore->directory($accessSubsDir);

    app(BlueprintRepository::class)->setFallback('forms.'.$accessHandle, fn () => Blueprint::makeFromFields([
        'name' => ['type' => 'text', 'display' => 'Name'],
    ]));
});

afterEach(function () use (&$accessFormsDir, &$accessSubsDir): void {
    foreach ([$accessFormsDir, $accessSubsDir] as $dir) {
        if ($dir !== '' && File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }
});

/**
 * Count the submission files written for the handle, bypassing the Stache cache.
 */
function accessOverlapStoredCount(string $handle): int
{
    $base = Config::get('statamic.forms.submissions');
    $dir = (is_string($base) ? $base : '').'/'.$handle;

    return File::isDirectory($dir) ? count(File::files($dir)) : 0;
}

it('refuses a submission after core close_date, with the core closed message', function () use (&$accessHandle): void {
    $form = makeForm($accessHandle);
    $form->store(true);
    $form->set('close_date', '2020-01-01');
    $form->set('closed_message', 'Closed by core.');
    $form->save();

    $this->post('/!/forms/'.$accessHandle, ['name' => 'Pat'])
        ->assertSessionHasErrors(['*' => 'Closed by core.'], null, 'form.'.$accessHandle);

    expect(accessOverlapStoredCount($accessHandle))->toBe(0);
})->skip(! class_exists(ConnectionLogic::class), 'Access settings need statamic/cms 6.44');

it('refuses a submission once the core submission_limit is reached', function () use (&$accessHandle): void {
    $form = makeForm($accessHandle);
    $form->store(true);
    $form->set('submission_limit', 1);
    $form->set('closed_message', 'Full by core.');
    $form->save();

    $this->post('/!/forms/'.$accessHandle, ['name' => 'First'])->assertRedirect();
    expect(accessOverlapStoredCount($accessHandle))->toBe(1);

    $this->post('/!/forms/'.$accessHandle, ['name' => 'Second'])
        ->assertSessionHasErrors(['*' => 'Full by core.'], null, 'form.'.$accessHandle);
    expect(accessOverlapStoredCount($accessHandle))->toBe(1);
})->skip(! class_exists(ConnectionLogic::class), 'Access settings need statamic/cms 6.44');

it('answers with the core message when core and the add-on both close the form', function () use (&$accessHandle): void {
    $form = makeForm($accessHandle);
    $form->store(true);
    $form->set('close_date', '2020-01-01');
    $form->set('closed_message', 'Closed by core.');
    $form->set('limit_entries', true);
    $form->set('limit_entries_count', 0);
    $form->set('limit_entries_message', 'Full by the add-on.');
    $form->save();

    $response = $this->post('/!/forms/'.$accessHandle, ['name' => 'Pat']);

    // Core refuses before validation, so the add-on's listener never runs
    $response->assertSessionHasErrors(['*' => 'Closed by core.'], null, 'form.'.$accessHandle);
    $response->assertSessionDoesntHaveErrors([$accessHandle], null, 'form.'.$accessHandle);

    expect(accessOverlapStoredCount($accessHandle))->toBe(0);
})->skip(! class_exists(ConnectionLogic::class), 'Access settings need statamic/cms 6.44');
