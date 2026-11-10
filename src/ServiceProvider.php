<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Forms\Settings;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;
use Statamic\Events\FormSubmitted;
use Statamic\Events\SubmissionCreating;
use Statamic\Forms\SendEmail;
use Statamic\Http\Controllers\CP\Forms\FormsController as BaseFormsController;
use Statamic\Providers\AddonServiceProvider;
use UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionContext;
use UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionSite;
use UAMSWeb\Aether\Forms\Settings\Http\Controllers\FormsController;
use UAMSWeb\Aether\Forms\Settings\Http\Middleware\ApplyFormConfirmation;
use UAMSWeb\Aether\Forms\Settings\Jobs\SendFormNotification;
use UAMSWeb\Aether\Forms\Settings\Listeners\AppendSubmissionMetadata;
use UAMSWeb\Aether\Forms\Settings\Listeners\CaptureFormRequestContext;
use UAMSWeb\Aether\Forms\Settings\Listeners\EnforceFormRestrictions;
use UAMSWeb\Aether\Forms\Settings\Listeners\WarnWhenFormEmailQueued;
use UAMSWeb\Aether\Forms\Settings\Support\CurrentSubmissionSite;
use UAMSWeb\Aether\Forms\Settings\Support\FormRequestSubmissionContext;
use UAMSWeb\Aether\Forms\Settings\Support\NullSubmissionContext;

/**
 * Service provider for the form settings add-on.
 *
 * The host application discovers it through `extra.laravel.providers` in
 * `composer.json`. The fieldtypes and the `form_restriction` tag register
 * through Statamic's discovery of `src/Fieldtypes` and `src/Tags`, and the
 * restrictions listener in {@see register()}, always, so a stored blueprint
 * or template that names them keeps working with any feature switched off;
 * the tag and the listener check the `restrictions` switch themselves.
 * Everything else registers in {@see bootAddon()}, once the add-on's config
 * is merged, and only when its feature is on ({@see Features}).
 */
class ServiceProvider extends AddonServiceProvider
{
    // A property default has no executable line for the coverage driver, so its mutants can never be covered.
    // The declared value is pinned by tests/Unit/Fieldtypes/DeclaredDefaultsTest.php through reflection instead.
    // @pest-mutate-ignore: RemoveArrayItem
    /**
     * The Vite build of the Control Panel assets. `input` and `publicDirectory`
     * must match `vite.config.js`; the built files are committed under
     * `resources/dist`.
     *
     * @var array{input: list<string>, publicDirectory: string}
     */
    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    /**
     * Bind the default submission context, which a host's own binding replaces,
     * and listen for form submissions ahead of the host.
     *
     * Laravel calls the listeners for an event in the order they were registered,
     * and registers a discovered package's providers before a host's own, so a
     * listener registered here runs before any `FormSubmitted` listener the host
     * registers. A host that lists this provider itself, after its own, can still
     * register a listener ahead of it from its own `register()`. Statamic's
     * discovery of `src/Listeners` registers its listeners once every provider
     * has booted, after the host's, so a restricted form would otherwise report a
     * host listener's refusal instead of its own (#25). Unlike that discovery,
     * this registers the listener even when Statamic cannot resolve the add-on;
     * the listener still reads the `restrictions` switch when it runs.
     */
    #[\Override]
    public function register(): void
    {
        // Capture nothing unless the host binds a context of its own
        $this->app->bindIf(SubmissionContext::class, NullSubmissionContext::class);

        // Name the current site as a submission's site unless the host binds one that knows better
        $this->app->bindIf(SubmissionSite::class, CurrentSubmissionSite::class);

        // Refuse a restricted submission before the host's own listeners see it
        $this->app->make(Dispatcher::class)->listen(FormSubmitted::class, EnforceFormRestrictions::class);
    }

    /**
     * Register no listeners from Statamic's discovery of `src/Listeners`.
     *
     * The restrictions listener is registered in {@see register()}, so that it
     * runs ahead of the host's; discovering it as well would register it twice.
     * The submission data listeners are registered in {@see registerSubmissionData()},
     * and only while that feature is on. A listener added to `src/Listeners` is
     * registered by one of the two.
     */
    #[\Override]
    public function bootEvents(): static
    {
        return $this;
    }

    /**
     * Register each feature that is switched on, now that the config is merged.
     */
    #[\Override]
    public function bootAddon(): void
    {
        // Render the quiz and Likert scale fields on a public form. Like the fieldtypes they draw, these
        // register whatever the switches say, so a stored form that names one keeps rendering
        $this->addStatamicViewsAfterHostOverrides(__DIR__.'/../resources/field-views');

        // Let the quiz and survey tags resolve in confirmation messages and emails, unless the host opts out.
        // The add-on's config always supplies the key, so its fallback is never read.
        // @pest-mutate-ignore: TrueToFalse
        if (Features::quizScoring() && config('aether-form-settings.quiz_tags_in_user_content', true)) {
            $this->allowScoringTagsInUserContent();
        }

        // Replace Statamic's form settings controller with the one that adds the sections
        if (Features::enabled(Features::CONTROL_PANEL)) {
            $this->app->bind(BaseFormsController::class, FormsController::class);
        }

        // Send notifications through the add-on's job, and render them with its body view
        if (Features::enabled(Features::NOTIFICATIONS)) {
            // Leave a host that names a job of its own alone
            if (config('statamic.forms.send_email_job') === SendEmail::class) {
                config(['statamic.forms.send_email_job' => SendFormNotification::class]);
            }

            $this->addStatamicViewsAfterHostOverrides(__DIR__.'/../resources/views');
        }

        // Answer a submission with its confirmation message or redirect
        if (Features::enabled(Features::CONFIRMATIONS)) {
            $this->app->make(Router::class)->pushMiddlewareToGroup('web', ApplyFormConfirmation::class);
        }

        // Save the `_*` metadata and carry the request context to queued emails
        if (Features::submissionData()) {
            $this->registerSubmissionData();
        }
    }

    /**
     * Register the submission data listeners and the context that carries a request to a queue worker.
     *
     * This runs in {@see bootAddon()}, after the host's own providers have registered their
     * listeners, so the listeners here run after a host's check on the same events, such as a
     * limit on how many submissions a choice may take. A submission that check refuses is then
     * never given metadata.
     */
    private function registerSubmissionData(): void
    {
        // Hand the captured request context to the notification job instead of capturing nothing
        $this->app->bind(SubmissionContext::class, FormRequestSubmissionContext::class);

        $events = $this->app->make(Dispatcher::class);
        $events->listen(SubmissionCreating::class, AppendSubmissionMetadata::class);
        $events->listen(FormSubmitted::class, CaptureFormRequestContext::class);
        $events->listen(JobQueued::class, WarnWhenFormEmailQueued::class);
    }

    /**
     * Add the quiz and survey tags to the patterns Antlers allows in user content.
     *
     * Statamic builds the allowlist from `statamic.antlers.allowedContentTags` each time it makes a
     * parser, so a change made while the add-on boots reaches every parse after it. Statamic reads
     * the key as a list, and only `null` means its defaults; a host list that leaves out `@default`
     * replaces them. So with no list at all the defaults are kept by naming `@default` first, and any
     * other value is read as a list, as Statamic reads it. The host's own entries stay, in their order.
     */
    private function allowScoringTagsInUserContent(): void
    {
        $allowed = config('statamic.antlers.allowedContentTags');
        $patterns = $allowed === null ? ['@default'] : array_values((array) $allowed);

        foreach (['quiz', 'quiz:*', 'survey', 'survey:*'] as $pattern) {
            // Allowlist entries are strings, which compare the same loosely or strictly.
            // @pest-mutate-ignore: TrueToFalse
            if (! \in_array($pattern, $patterns, true)) {
                $patterns[] = $pattern;
            }
        }

        config(['statamic.antlers.allowedContentTags' => $patterns]);
    }

    /**
     * Add a path to the `statamic` view namespace, after a host's overrides of it.
     *
     * Laravel lists a host's own `resources/views/vendor/statamic` ahead of Statamic's
     * views, which is how a host overrides a package view. The add-on's views go
     * between the two, so they replace Statamic's and a host's copy still wins.
     *
     * @param  string  $path  The add-on's view directory.
     */
    private function addStatamicViewsAfterHostOverrides(string $path): void
    {
        // Read the namespace's current paths
        $finder = View::getFinder();
        // Laravel's view factory always holds a FileViewFinder, so the guard is for PHPStan and the
        // other branch cannot be reached from a test.
        // @pest-mutate-ignore: InstanceOfToTrue
        $hints = $finder instanceof FileViewFinder ? $finder->getHints()['statamic'] ?? [] : [];

        // The override directories Laravel looks for under each of the host's view paths, resolved
        // as the finder resolves a hint: since Laravel v13.39 a hint is stored through realpath(), so
        // a view path that goes through a symlink would otherwise never match its own hint (#37)
        $viewPaths = config('view.paths');
        $overrides = array_map(
            // A view path that is not a string names no directory, so any text in its place matches no hint.
            // @pest-mutate-ignore: EmptyStringToNotEmpty
            static fn (mixed $viewPath): string => self::resolveViewPath((\is_string($viewPath) ? $viewPath : '').'/vendor/statamic'),
            \is_array($viewPaths) ? $viewPaths : [],
        );

        // Keep the host's overrides first, then the add-on's views, then the rest
        $first = [];
        $rest = [];
        foreach ($hints as $hint) {
            // Resolve the hint too, for a Laravel release that stores hints as given
            $resolved = \is_string($hint) ? self::resolveViewPath($hint) : $hint;

            // Hints and overrides are both directory paths, which compare the same loosely or strictly.
            // @pest-mutate-ignore: TrueToFalse
            if (\in_array($resolved, $overrides, true)) {
                $first[] = $hint;
            } else {
                $rest[] = $hint;
            }
        }

        View::replaceNamespace('statamic', [...$first, $path, ...$rest]);
    }

    /**
     * Resolve a view directory the way Laravel's view finder resolves a namespace hint.
     *
     * @param  string  $path  The directory as configured.
     * @return string The directory with its symlinks resolved, or as given when it does not exist.
     */
    private static function resolveViewPath(string $path): string
    {
        return realpath($path) ?: $path;
    }
}
