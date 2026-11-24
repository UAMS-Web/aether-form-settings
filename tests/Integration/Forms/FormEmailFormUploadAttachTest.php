<?php

declare(strict_types=1);

use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Statamic\Assets\Asset as StatamicAsset;
use Statamic\Assets\AssetContainer as StatamicAssetContainer;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\Fields\BlueprintRepository;
use Statamic\Forms\Fieldtypes\Upload as FormUploadFieldtype;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use UAMSWeb\Aether\Forms\Settings\Mail\FormNotificationEmail;

/**
 * Proves the `attach_to_email` opt-in also governs the Statamic 6.44 `form_upload` field, which
 * core attaches on its own (UAMS-Web/aether-form-settings#116): within a notification whose
 * `attachments` gate is on, only a `form_upload` field flagged `attach_to_email` attaches, and it
 * attaches once.
 *
 * Statamic releases before 6.44 have no `form_upload` fieldtype, so the cases skip there.
 *
 * @covers \UAMSWeb\Aether\Forms\Settings\Mail\FormNotificationEmail
 *
 * @command  vendor/bin/pest tests/Integration/Forms/FormEmailFormUploadAttachTest.php
 */

// Safe under `pest --parallel`: the faked disk (`form_upload_attach_disk`) is used by this file
// alone, and paratest runs a file in one worker. The asset container `beforeEach` saves is
// deleted in `afterEach`, and no other test reads asset containers.

/**
 * Handle of the throwaway form created for this test.
 */
const FORM_UPLOAD_ATTACH_FORM_HANDLE = 'form_upload_attach_probe';

/**
 * Handle of the throwaway asset container created for this test.
 */
const FORM_UPLOAD_ATTACH_CONTAINER_HANDLE = 'form_upload_attach_assets';

/**
 * Isolated forms directory so the throwaway form never touches resources/forms.
 *
 * @var string Filesystem path to the per-test forms directory.
 */
$formUploadAttachDir = '';

beforeEach(function () use (&$formUploadAttachDir): void {
    // A faked asset disk + container holding two uploaded files.
    Storage::fake('form_upload_attach_disk');
    $probeContainer = mustBe(StatamicAssetContainer::class, AssetContainer::make(FORM_UPLOAD_ATTACH_CONTAINER_HANDLE), 'probe container');
    $probeContainer->disk('form_upload_attach_disk');
    $probeContainer->title('Form upload attach');
    $probeContainer->save();
    Storage::disk('form_upload_attach_disk')->put('flagged.txt', 'FLAGGED CONTENT');
    Storage::disk('form_upload_attach_disk')->put('unflagged.txt', 'UNFLAGGED CONTENT');
    $container = mustBe(StatamicAssetContainer::class, AssetContainer::find(FORM_UPLOAD_ATTACH_CONTAINER_HANDLE), 'container');
    $container->makeAsset('flagged.txt')->save();
    $container->makeAsset('unflagged.txt')->save();

    // Isolate the forms directory.
    $formUploadAttachDir = storage_path('framework/testing/forms-'.uniqid('', true));
    File::ensureDirectoryExists($formUploadAttachDir);
    Config::set('statamic.forms.forms', $formUploadAttachDir);

    // Two stored form_upload fields: one flagged to attach, one not.
    app(BlueprintRepository::class)->setFallback('forms.'.FORM_UPLOAD_ATTACH_FORM_HANDLE, fn () => Blueprint::makeFromFields([
        'flagged' => ['type' => 'form_upload', 'store' => true, 'container' => FORM_UPLOAD_ATTACH_CONTAINER_HANDLE, 'max_files' => 1, 'attach_to_email' => true],
        'unflagged' => ['type' => 'form_upload', 'store' => true, 'container' => FORM_UPLOAD_ATTACH_CONTAINER_HANDLE, 'max_files' => 1, 'attach_to_email' => false],
    ]));
});

afterEach(function () use (&$formUploadAttachDir): void {
    // Delete throwaway assets and container so later tests never hit form_upload_attach_disk.
    $container = AssetContainer::find(FORM_UPLOAD_ATTACH_CONTAINER_HANDLE);
    if ($container instanceof StatamicAssetContainer) {
        foreach (['flagged.txt', 'unflagged.txt'] as $basename) {
            $asset = $container->asset($basename);
            if ($asset instanceof StatamicAsset) {
                $asset->delete();
            }
        }

        $container->delete();
    }

    if ($formUploadAttachDir !== '' && File::isDirectory($formUploadAttachDir)) {
        File::deleteDirectory($formUploadAttachDir);
    }
});

/**
 * Build and send a notification for a submission carrying both uploads, returning the attached filenames.
 *
 * @param  array<string, mixed>  $config  The email-notification config.
 * @return list<string>
 */
function formUploadAttachSendAndGetFilenames(array $config): array
{
    $form = makeForm(FORM_UPLOAD_ATTACH_FORM_HANDLE);
    $form->save();

    $submission = $form->makeSubmission();
    $submission->data(['flagged' => 'flagged.txt', 'unflagged' => 'unflagged.txt']);

    /** @var Statamic\Sites\Site $site */
    $site = Site::default();
    Mail::mailer('array')->send(new FormNotificationEmail($submission, $config, $site));

    $transport = mustBe(ArrayTransport::class, Mail::getSymfonyTransport(), 'transport');
    $sent = mustBe(SentMessage::class, $transport->messages()->first(), 'sent');
    $message = mustBe(Email::class, $sent->getOriginalMessage(), 'message');

    return array_values(array_filter(array_map(
        static fn (DataPart $part): ?string => $part->getFilename(),
        $message->getAttachments(),
    )));
}

it('attaches only the flagged form_upload field when the notification attachments gate is on', function (): void {
    $filenames = formUploadAttachSendAndGetFilenames([
        'to' => 'recipient@example.test',
        'subject' => 'With attachments',
        'attachments' => true,
    ]);

    expect($filenames)->toBe(['flagged.txt']);
})->skip(! class_exists(FormUploadFieldtype::class), 'form_upload needs statamic/cms 6.44');

it('attaches no form_upload field when the notification attachments gate is off', function (): void {
    $filenames = formUploadAttachSendAndGetFilenames([
        'to' => 'recipient@example.test',
        'subject' => 'No attachments',
    ]);

    expect($filenames)->toBe([]);
})->skip(! class_exists(FormUploadFieldtype::class), 'form_upload needs statamic/cms 6.44');
