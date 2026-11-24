<?php

declare(strict_types=1);

use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\Fields\BlueprintRepository;
use Statamic\Forms\Connectors\ConnectionLogic;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use UAMSWeb\Aether\Forms\Settings\Mail\FormNotificationEmail;

/**
 * Proves the add-on override of `forms/automagic-email` renders the connection `body` that
 * Statamic 6.44 passes as `email_config:body` (UAMS-Web/aether-form-settings#116), and still
 * lists the fields when no body is set.
 *
 * Statamic releases before 6.44 pass no `email_config`, so the body case skips there.
 *
 * @covers \UAMSWeb\Aether\Forms\Settings\Mail\FormNotificationEmail
 *
 * @command  vendor/bin/pest tests/Integration/Forms/FormEmailConnectionBodyTest.php
 */

/**
 * Handle of the throwaway form created for each test.
 */
const FORM_EMAIL_CONNECTION_BODY_HANDLE = 'connection_body_probe';

/**
 * Isolated forms directory so the throwaway form never touches resources/forms.
 *
 * @var string Filesystem path to the per-test forms directory.
 */
$formEmailConnectionBodyDir = '';

beforeEach(function () use (&$formEmailConnectionBodyDir): void {
    // Isolate the forms directory so the throwaway form is self-contained
    $formEmailConnectionBodyDir = storage_path('framework/testing/forms-'.uniqid('', true));
    File::ensureDirectoryExists($formEmailConnectionBodyDir);
    Config::set('statamic.forms.forms', $formEmailConnectionBodyDir);

    app(BlueprintRepository::class)->setFallback('forms.'.FORM_EMAIL_CONNECTION_BODY_HANDLE, fn () => Blueprint::makeFromFields([
        'name' => ['type' => 'text', 'display' => 'Name'],
    ]));
});

afterEach(function () use (&$formEmailConnectionBodyDir): void {
    // Remove the isolated forms directory
    if ($formEmailConnectionBodyDir !== '' && File::isDirectory($formEmailConnectionBodyDir)) {
        File::deleteDirectory($formEmailConnectionBodyDir);
    }
});

/**
 * Send a notification for a submission and return its HTML part.
 *
 * @param  array<string, mixed>  $config  The email-notification config.
 */
function formEmailConnectionBodySendAndGetHtml(array $config): string
{
    $form = makeForm(FORM_EMAIL_CONNECTION_BODY_HANDLE);
    $form->save();

    $submission = $form->makeSubmission();
    $submission->data(['name' => 'Ada Lovelace']);

    /** @var Statamic\Sites\Site $site */
    $site = Site::default();
    Mail::mailer('array')->send(new FormNotificationEmail($submission, $config, $site));

    $transport = mustBe(ArrayTransport::class, Mail::getSymfonyTransport(), 'transport');
    $sent = mustBe(SentMessage::class, $transport->messages()->first(), 'sent');
    $message = mustBe(Email::class, $sent->getOriginalMessage(), 'message');

    return mustBeString($message->getHtmlBody(), 'html');
}

it('renders the connection body in place of the field list', function (): void {
    $html = formEmailConnectionBodySendAndGetHtml([
        'to' => 'recipient@example.test',
        'subject' => 'With a body',
        'body' => 'Thanks for writing, we will reply soon.',
    ]);

    expect($html)->toContain('Thanks for writing, we will reply soon.')
        ->and($html)->not->toContain('Ada Lovelace');
})->skip(! class_exists(ConnectionLogic::class), 'the connection body needs statamic/cms 6.44');

it('lists the submitted fields when no body is set', function (): void {
    $html = formEmailConnectionBodySendAndGetHtml([
        'to' => 'recipient@example.test',
        'subject' => 'No body',
    ]);

    expect($html)->toContain('<b>Name:</b>')
        ->and($html)->toContain('Ada Lovelace');
});
