<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Forms\Submission;
use Statamic\Events\SubmissionCreating;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Stache;
use Statamic\Fields\BlueprintRepository;
use Statamic\Sites\Site as StatamicSite;
use Statamic\Stache\Stores\Store;
use UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionSite;
use UAMSWeb\Aether\Forms\Settings\Listeners\AppendSubmissionMetadata;
use UAMSWeb\Aether\Forms\Settings\Support\CurrentSubmissionSite;

/**
 * Proves the AppendSubmissionMetadata listener appends the system `_*` context fields
 * to every form submission's stored YAML: which form, where, which site, and spam-triage
 * context (user agent, IP, referrer, timestamp), without clobbering editor fields.
 *
 * @command  vendor/bin/pest tests/Integration/Listeners/AppendSubmissionMetadataTest.php
 */
covers(AppendSubmissionMetadata::class);

/**
 * Handle of the throwaway form created for this test.
 */
const SUBMISSION_METADATA_FORM_HANDLE = 'submission_metadata_probe';

$submissionMetadataFormsDir = '';
$submissionMetadataSubsDir = '';

beforeEach(function () use (&$submissionMetadataFormsDir, &$submissionMetadataSubsDir): void {
    // Switch the feature on, so the listener is registered for the submit route
    $this->rebootWithConfig(['aether-form-settings.features.submission_data' => true]);

    // Unique test-net IP keeps this file's submissions out of other tests' rate-limit bucket.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.51']);

    // Isolate the forms directory.
    $submissionMetadataFormsDir = storage_path('framework/testing/forms-'.uniqid('', true));
    File::ensureDirectoryExists($submissionMetadataFormsDir);
    Config::set('statamic.forms.forms', $submissionMetadataFormsDir);

    // Isolate the submissions store (the booted Stache store, so a runtime config is ignored).
    $submissionMetadataSubsDir = storage_path('framework/testing/subs-'.uniqid('', true));
    File::ensureDirectoryExists($submissionMetadataSubsDir);
    Config::set('statamic.forms.submissions', $submissionMetadataSubsDir);
    /** @var Store $submissionsStore */
    $submissionsStore = Stache::store('form-submissions');
    $submissionsStore->directory($submissionMetadataSubsDir);

    app(BlueprintRepository::class)->setFallback('forms.'.SUBMISSION_METADATA_FORM_HANDLE, fn () => Blueprint::makeFromFields([
        'name' => ['type' => 'text', 'display' => 'Name'],
    ]));
});

afterEach(function () use (&$submissionMetadataFormsDir, &$submissionMetadataSubsDir): void {
    foreach ([$submissionMetadataFormsDir, $submissionMetadataSubsDir] as $dir) {
        if ($dir !== '' && File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }
    }

    $defaultSubs = storage_path('forms/'.SUBMISSION_METADATA_FORM_HANDLE);
    if (File::isDirectory($defaultSubs)) {
        File::deleteDirectory($defaultSubs);
    }
});

it('appends the system metadata fields to a stored submission', function (): void {
    $form = makeForm(SUBMISSION_METADATA_FORM_HANDLE);
    $form->title('Submission Metadata Probe');
    $form->store(true);
    $form->save();

    // Seed the submitter request context the listener reads.
    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.50',
        'HTTP_REFERER' => 'https://uams.test/clinic/contact?ref=x',
    ]);

    $this->post('/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE, ['name' => 'Pat'], ['User-Agent' => 'MetaTest/2.0'])
        ->assertRedirect();

    $submission = mustBe(Submission::class, $form->submissions()->last(), 'submission');
    $data = $submission->data()->all();

    // Which form.
    expect($data['_form_handle'])->toBe(SUBMISSION_METADATA_FORM_HANDLE);
    expect($data['_form_title'])->toBe('Submission Metadata Probe');
    // Where (derived from the Referer: the page the form was on).
    expect($data['_submitted_url'])->toBe('https://uams.test/clinic/contact?ref=x');
    expect($data['_submitted_path'])->toBe('/clinic/contact');
    // Which site.
    expect($data['_site_handle'])->toBe('default');
    expect($data['_site_name'])->toBeString();
    expect($data['_site_url'])->toBeString();
    // Spam-triage context.
    expect($data['_user_agent'])->toBe('MetaTest/2.0');
    expect($data['_ip'])->toBe('203.0.113.50');
    expect($data['_referrer'])->toBe('https://uams.test/clinic/contact?ref=x');
    expect($data['_submitted_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');

    // The editor field is preserved.
    expect($data['name'])->toBe('Pat');
});

it('does not overwrite a pre-existing key that collides with a metadata field', function (): void {
    $form = makeForm(SUBMISSION_METADATA_FORM_HANDLE);
    $form->store(true);
    $form->save();

    // A submission that already carries an `_ip` value before the listener runs.
    $submission = $form->makeSubmission();
    $submission->data(['name' => 'Pat', '_ip' => 'preset-sentinel']);
    $submission->save();

    $stored = mustBe(Submission::class, $form->submissions()->last(), 'stored');
    $data = $stored->data()->all();

    // The collision guard preserved the existing value, but other metadata still appended.
    expect($data['_ip'])->toBe('preset-sentinel');
    expect($data['_form_handle'])->toBe(SUBMISSION_METADATA_FORM_HANDLE);
});

it('falls back to the request URL/path when no Referer is present', function (): void {
    $form = makeForm(SUBMISSION_METADATA_FORM_HANDLE);
    $form->store(true);
    $form->save();

    // No HTTP_REFERER seeded (beforeEach sets only REMOTE_ADDR).
    $this->post('/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE, ['name' => 'Pat'])->assertRedirect();

    $submission = mustBe(Submission::class, $form->submissions()->last(), 'submission');
    $data = $submission->data()->all();

    expect($data['_submitted_url'])->toContain('/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE);
    expect($data['_submitted_path'])->toBe('/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE);
    expect($data['_referrer'])->toBe('');
});

it('ignores a malformed Referer, falling back to a safe URL and path', function (): void {
    $form = makeForm(SUBMISSION_METADATA_FORM_HANDLE);
    $form->store(true);
    $form->save();

    // A garbage, client-controlled Referer must not land in _submitted_url / _submitted_path.
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.51', 'HTTP_REFERER' => 'not a url with spaces']);
    $this->post('/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE, ['name' => 'Pat'])->assertRedirect();

    $submission = mustBe(Submission::class, $form->submissions()->last(), 'submission');
    $data = $submission->data()->all();

    expect($data['_submitted_url'])->toContain('/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE);
    expect($data['_submitted_path'])->toBe('/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE);
    // _referrer still records the raw header verbatim (it is literally the Referer).
    expect($data['_referrer'])->toBe('not a url with spaces');
});

/**
 * Run the listener directly against an unsaved submission, with the given request bound.
 *
 * @return array<string, mixed> The submission data after the listener ran.
 */
function submissionMetadataHandleWith(Request $request): array
{
    app()->instance('request', $request);

    $submission = makeForm(SUBMISSION_METADATA_FORM_HANDLE)->makeSubmission();
    $submission->data(['name' => 'Pat']);

    (new AppendSubmissionMetadata)->handle(new SubmissionCreating($submission));

    /** @var array<string, mixed> */
    return $submission->data()->all();
}

it('ignores an event whose submission is not a submission', function (): void {
    $event = new SubmissionCreating(new stdClass);

    (new AppendSubmissionMetadata)->handle($event);

    expect($event->submission)->toEqual(new stdClass);
});

it('records empty strings when the request has no user agent, IP or Referer', function (): void {
    $request = Request::create('https://uams.test/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE, 'POST');
    $request->headers->remove('User-Agent');
    $request->server->remove('REMOTE_ADDR');

    $data = submissionMetadataHandleWith($request);

    expect($data['_user_agent'])->toBe('')
        ->and($data['_ip'])->toBe('')
        ->and($data['_referrer'])->toBe('')
        ->and($data['_submitted_url'])->toBe('https://uams.test/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE);
});

it('records the root path when the Referer has no path', function (): void {
    $request = Request::create('https://uams.test/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE, 'POST');
    $request->headers->set('Referer', 'https://uams.test');

    $data = submissionMetadataHandleWith($request);

    expect($data['_submitted_url'])->toBe('https://uams.test')
        ->and($data['_submitted_path'])->toBe('/');
});

it('names the site with the default submission site, the current site', function (): void {
    expect(app(SubmissionSite::class))->toBeInstanceOf(CurrentSubmissionSite::class);

    $data = submissionMetadataHandleWith(Request::create('https://uams.test/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE, 'POST'));

    expect($data['_site_handle'])->toBe('default')
        ->and($data['_site_name'])->toBeString()
        ->and($data['_site_url'])->toBeString();
});

it('names the site with the submission site the host binds, given the submission', function (): void {
    // A host's site: it names the site from the submission it is given
    $site = new StatamicSite('health', ['name' => 'Health', 'locale' => 'en_US', 'url' => 'https://health.test/']);
    $seen = null;
    app()->instance(SubmissionSite::class, new class($site, $seen) implements SubmissionSite
    {
        public function __construct(private readonly StatamicSite $site, public mixed &$seen) {}

        #[\Override]
        public function forSubmission(?Submission $submission = null): StatamicSite
        {
            $this->seen = $submission;

            return $this->site;
        }
    });

    app()->instance('request', Request::create('https://uams.test/!/forms/'.SUBMISSION_METADATA_FORM_HANDLE, 'POST'));
    $submission = makeForm(SUBMISSION_METADATA_FORM_HANDLE)->makeSubmission();
    $submission->data(['name' => 'Pat']);

    (new AppendSubmissionMetadata)->handle(new SubmissionCreating($submission));

    expect($submission->get('_site_handle'))->toBe('health')
        ->and($submission->get('_site_name'))->toBe('Health')
        ->and($submission->get('_site_url'))->toBe('https://health.test')
        ->and($seen)->toBe($submission);
});
