<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Forms\Submission;
use Statamic\Events\FormSubmitted;
use Statamic\Events\SubmissionCreating;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Stache;
use Statamic\Fields\BlueprintRepository;
use Statamic\Stache\Stores\Store;
use UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionContext;
use UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionSite;
use UAMSWeb\Aether\Forms\Settings\Features;
use UAMSWeb\Aether\Forms\Settings\Listeners\AppendSubmissionMetadata;
use UAMSWeb\Aether\Forms\Settings\Listeners\CaptureFormRequestContext;
use UAMSWeb\Aether\Forms\Settings\Support\CurrentSubmissionSite;
use UAMSWeb\Aether\Forms\Settings\Support\FormRequestSubmissionContext;
use UAMSWeb\Aether\Forms\Settings\Support\NullSubmissionContext;
use UAMSWeb\Aether\Forms\Settings\Tests\Fixtures\Host\HostEventServiceProvider;
use UAMSWeb\Aether\Forms\Settings\Tests\Fixtures\Host\RefuseEverySubmission;

/**
 * The `submission_data` switch is opt-in: off, a submission saves with none of the added
 * metadata and the notification job captures nothing; on, the listeners are registered
 * after a host's own and the request context is carried to the job.
 *
 * The add-on reads the switch while booting, so each test reboots with it set first.
 *
 * @command  vendor/bin/pest tests/Integration/SubmissionDataTest.php
 */
$submissionDataFormsDir = '';
$submissionDataSubsDir = '';
$submissionDataHandle = '';

beforeEach(function () use (&$submissionDataFormsDir, &$submissionDataSubsDir, &$submissionDataHandle): void {
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.'.random_int(0, 255).'.'.random_int(1, 254)]);

    $submissionDataHandle = 'submission_data_'.str_replace('.', '', uniqid('', true));

    $submissionDataFormsDir = storage_path('framework/testing/forms-'.uniqid('', true));
    File::ensureDirectoryExists($submissionDataFormsDir);
    Config::set('statamic.forms.forms', $submissionDataFormsDir);

    $submissionDataSubsDir = storage_path('framework/testing/subs-'.uniqid('', true));
    File::ensureDirectoryExists($submissionDataSubsDir);
    Config::set('statamic.forms.submissions', $submissionDataSubsDir);
    /** @var Store $submissionsStore */
    $submissionsStore = Stache::store('form-submissions');
    $submissionsStore->directory($submissionDataSubsDir);

    app(BlueprintRepository::class)->setFallback('forms.'.$submissionDataHandle, fn () => Blueprint::makeFromFields([
        'name' => ['type' => 'text', 'display' => 'Name'],
    ]));
});

afterEach(function () use (&$submissionDataFormsDir, &$submissionDataSubsDir): void {
    foreach ([$submissionDataFormsDir, $submissionDataSubsDir] as $dir) {
        if ($dir !== '' && File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }
});

/**
 * The listeners the dispatcher holds for an event, in the order it calls them.
 *
 * @param  string  $event  The event class.
 * @return list<mixed> The listeners as they were registered.
 */
function submissionDataListeners(string $event): array
{
    $raw = Event::getRawListeners()[$event] ?? [];

    return is_array($raw) ? array_values($raw) : [];
}

it('is off unless the config switches it on', function (): void {
    expect(Features::submissionData())->toBeFalse();

    config(['aether-form-settings.features' => []]);
    expect(Features::submissionData())->toBeFalse();

    config(['aether-form-settings.features.submission_data' => true]);
    expect(Features::submissionData())->toBeTrue();
});

it('registers no listener and keeps the null context when it is off', function (): void {
    expect(submissionDataListeners(SubmissionCreating::class))->not->toContain(AppendSubmissionMetadata::class)
        ->and(submissionDataListeners(FormSubmitted::class))->not->toContain(CaptureFormRequestContext::class)
        ->and(app(SubmissionContext::class))->toBeInstanceOf(NullSubmissionContext::class);
});

it('saves a submission with none of the added metadata and no error when it is off', function () use (&$submissionDataHandle): void {
    $form = makeForm($submissionDataHandle);
    $form->store(true);
    $form->save();

    $this->post('/!/forms/'.$submissionDataHandle, ['name' => 'Pat'])->assertRedirect();

    $data = mustBe(Submission::class, $form->submissions()->last(), 'submission')->data()->all();

    expect($data['name'])->toBe('Pat')
        ->and(array_filter(array_keys($data), static fn (string|int $key): bool => str_starts_with((string) $key, '_')))->toBe([]);
});

it('saves the metadata when it is on', function () use (&$submissionDataHandle): void {
    $this->rebootWithConfig(['aether-form-settings.features.submission_data' => true]);

    $form = makeForm($submissionDataHandle);
    $form->store(true);
    $form->save();

    $this->post('/!/forms/'.$submissionDataHandle, ['name' => 'Pat'])->assertRedirect();

    $data = mustBe(Submission::class, $form->submissions()->last(), 'submission')->data()->all();

    expect($data['_form_handle'])->toBe($submissionDataHandle);
});

it('registers the listeners and the capturing context when it is on', function (): void {
    $this->rebootWithConfig(['aether-form-settings.features.submission_data' => true]);

    expect(submissionDataListeners(SubmissionCreating::class))->toContain(AppendSubmissionMetadata::class)
        ->and(submissionDataListeners(FormSubmitted::class))->toContain(CaptureFormRequestContext::class)
        ->and(app(SubmissionContext::class))->toBeInstanceOf(FormRequestSubmissionContext::class);
});

it('registers each listener once when it is on', function (): void {
    $this->rebootWithConfig(['aether-form-settings.features.submission_data' => true]);

    $counts = array_count_values(array_filter(
        [...submissionDataListeners(SubmissionCreating::class), ...submissionDataListeners(FormSubmitted::class)],
        is_string(...),
    ));

    expect($counts[AppendSubmissionMetadata::class] ?? 0)->toBe(1)
        ->and($counts[CaptureFormRequestContext::class] ?? 0)->toBe(1);
});

it('runs the capture after a host listener that refuses the submission', function () use (&$submissionDataHandle): void {
    $this->rebootWithConfig(['aether-form-settings.features.submission_data' => true]);
    $this->rebootWithHostProviders([HostEventServiceProvider::class]);

    $listeners = submissionDataListeners(FormSubmitted::class);
    $host = array_search(RefuseEverySubmission::class, $listeners, true);
    $capture = array_search(CaptureFormRequestContext::class, $listeners, true);

    expect($host)->toBeInt()
        ->and($capture)->toBeInt()
        ->and($capture)->toBeGreaterThan($host);
});

it('names the current site unless the host binds one', function (): void {
    expect(app(SubmissionSite::class))->toBeInstanceOf(CurrentSubmissionSite::class);

    app()->bind(SubmissionSite::class, fn () => new CurrentSubmissionSite);
    expect(app(SubmissionSite::class)->forSubmission()->handle())->toBe('default');
});
