<?php

declare(strict_types=1);

use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Statamic\Facades\Blueprint;
use Statamic\Fields\BlueprintRepository;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

/**
 * Proves a form saved in the earlier shape, with a top-level `email:` list in its YAML, still
 * loads its notification rows and sends them, on the locked release and on Statamic 6.44, which
 * reads that shape into `connections.email` (UAMS-Web/aether-form-settings#117).
 *
 * @covers \UAMSWeb\Aether\Forms\Settings\Jobs\SendFormNotification
 *
 * @command  vendor/bin/pest tests/Integration/Forms/FormEmailLegacyShapeTest.php
 */
/**
 * Handle of the throwaway form created for this test.
 */
const LEGACY_SHAPE_FORM_HANDLE = 'legacy_shape_probe';

/**
 * Isolated forms directory so the throwaway form never touches resources/forms.
 *
 * @var string Filesystem path to the per-test forms directory.
 */
$legacyShapeDir = '';

beforeEach(function () use (&$legacyShapeDir): void {
    // A unique test-net address keeps this file's submissions out of the shared rate-limit bucket
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.22']);

    $legacyShapeDir = storage_path('framework/testing/forms-'.uniqid('', true));
    File::ensureDirectoryExists($legacyShapeDir);
    Config::set('statamic.forms.forms', $legacyShapeDir);

    app(BlueprintRepository::class)->setFallback('forms.'.LEGACY_SHAPE_FORM_HANDLE, fn () => Blueprint::makeFromFields([
        'name' => ['type' => 'text', 'display' => 'Name'],
    ]));

    // The earlier shape: `email:` at the top level of the form's own YAML
    File::put($legacyShapeDir.'/'.LEGACY_SHAPE_FORM_HANDLE.'.yaml', implode("\n", [
        'title: Legacy shape',
        'store: false',
        'email:',
        '  -',
        '    name: Legacy row',
        '    to: legacy@example.test',
        '    subject: Hello',
        '',
    ]));
});

afterEach(function () use (&$legacyShapeDir): void {
    if ($legacyShapeDir !== '' && File::isDirectory($legacyShapeDir)) {
        File::deleteDirectory($legacyShapeDir);
    }
});

it('loads a top-level email list and sends its row', function (): void {
    $this->post('/!/forms/'.LEGACY_SHAPE_FORM_HANDLE, ['name' => 'Pat'])->assertRedirect();

    $transport = mustBe(ArrayTransport::class, Mail::getSymfonyTransport(), 'transport');

    /** @var list<string> $recipients */
    $recipients = $transport->messages()
        ->map(function ($sent): string {
            $sent = mustBe(SentMessage::class, $sent, 'sent');
            $message = mustBe(Email::class, $sent->getOriginalMessage(), 'message');

            return strtolower($message->getTo()[0]->getAddress());
        })
        ->all();

    expect($recipients)->toBe(['legacy@example.test']);
});
