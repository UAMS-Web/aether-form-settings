<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Form as FormFacade;
use Statamic\Forms\Form;

/**
 * Proves the settings shapes the add-on writes to a form's YAML file come back from disk unchanged
 * (UAMS-Web/aether-form-settings#106).
 *
 * `FormRepository::find()` builds a new form and hydrates it from the file, so reading through
 * the facade discards the in-memory copy that `save()` was called on. Every other test uses the
 * saved object or a request, so a change to the reader that statamic/cms makes would otherwise
 * reach the add-on's settings without a test failing (statamic/cms#15838).
 *
 * @command  vendor/bin/pest tests/Integration/Forms/FormSettingsReadBackTest.php
 */

/**
 * Handle of the throwaway form created for this test.
 */
const FORM_SETTINGS_READ_BACK_HANDLE = 'settings_read_back_probe';

$formSettingsReadBackDir = '';

beforeEach(function () use (&$formSettingsReadBackDir): void {
    // Isolate the forms directory so the throwaway form never touches resources/forms
    $formSettingsReadBackDir = storage_path('framework/testing/forms-'.uniqid('', true));
    File::ensureDirectoryExists($formSettingsReadBackDir);
    Config::set('statamic.forms.forms', $formSettingsReadBackDir);
});

afterEach(function () use (&$formSettingsReadBackDir): void {
    if ($formSettingsReadBackDir !== '' && File::isDirectory($formSettingsReadBackDir)) {
        File::deleteDirectory($formSettingsReadBackDir);
    }
});

/**
 * The settings the add-on writes: a multi-line email body carrying a merge tag, a schedule,
 * conditional logic and confirmations with a query-string rule.
 *
 * @return array{email: list<array<string, mixed>>, config: array<string, mixed>}
 */
function formSettingsReadBackSettings(): array
{
    return [
        'email' => [
            [
                'to' => 'intake@example.test',
                'subject' => 'New request from {{ submission:name }}',
                'message' => "Hello,\n\nA request arrived from {{ submission:name }}.\nReply within two business days.\n",
                'conditional_logic' => ['if' => ['department' => 'cardiology'], 'always_save' => false],
            ],
        ],
        'config' => [
            'schedule_form_query' => true,
            'schedule_form' => [
                'start_datetime' => '2026-12-01 08:00:00',
                'end_datetime' => '2026-12-31 17:30:00',
            ],
            'confirmation_settings_default' => [
                'confirmation_type' => 'redirect',
                'confirmation_redirect_url' => 'https://uams.test/thanks',
                'confirmation_query_string' => 'source=form&topic={{ submission:department }}',
            ],
            'confirmation_additional' => [
                [
                    'confirmation_settings' => [
                        'confirmation_type' => 'message',
                        'confirmation_message' => "Thank you.\nWe will be in touch.",
                    ],
                    'conditional_logic' => ['if' => ['department' => 'oncology'], 'always_save' => false],
                ],
            ],
        ],
    ];
}

/**
 * Save a form carrying the settings, then read it back from disk as a new object.
 */
function formSettingsReadBackSavedForm(): Form
{
    $settings = formSettingsReadBackSettings();

    $form = makeForm(FORM_SETTINGS_READ_BACK_HANDLE);
    $form->title('Settings Read Back Probe');
    $form->email($settings['email']);

    foreach ($settings['config'] as $key => $value) {
        $form->set($key, $value);
    }

    $form->save();

    return formSettingsReadBackFromDisk();
}

/**
 * A new form hydrated from the file, never the object that was saved.
 */
function formSettingsReadBackFromDisk(): Form
{
    return mustBe(Form::class, FormFacade::find(FORM_SETTINGS_READ_BACK_HANDLE), 'form read from disk');
}

it('reads every saved setting back from disk equal to what was saved', function (): void {
    $settings = formSettingsReadBackSettings();

    $read = formSettingsReadBackSavedForm();

    expect($read->title())->toBe('Settings Read Back Probe')
        ->and($read->email())->toBe($settings['email']);

    foreach ($settings['config'] as $key => $value) {
        expect($read->get($key))->toBe($value, $key);
    }
});

it('reads a change made to the file on disk, so the read is not served from the saved copy', function (): void {
    formSettingsReadBackSavedForm();

    $path = formSettingsReadBackFromDisk()->path();
    $yaml = File::get($path);

    // The control must find the value it edits, or it would pass without proving anything
    expect($yaml)->toContain('2026-12-31 17:30:00');
    File::put($path, str_replace('2026-12-31 17:30:00', '2027-01-15 09:00:00', $yaml));

    expect(formSettingsReadBackFromDisk()->get('schedule_form'))->toBe([
        'start_datetime' => '2026-12-01 08:00:00',
        'end_datetime' => '2027-01-15 09:00:00',
    ]);
});
