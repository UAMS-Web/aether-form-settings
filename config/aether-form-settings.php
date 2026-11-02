<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Form settings
|--------------------------------------------------------------------------
|
| Statamic merges this file into the application's config under the add-on's
| handle (the package name without its vendor: `aether-form-settings`), so
| `config('aether-form-settings.features.notifications')` reads it. The host
| publishes it to its own `config/` directory with:
|
|     php artisan vendor:publish --tag=aether-form-settings-config
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | One switch per feature, each on by default so installing the add-on
    | changes nothing. Turning one off disables that feature alone; the others
    | keep working. Stored form settings are kept either way, so turning a
    | feature back on restores it as it was.
    |
    | control_panel  The form settings screen: the added sections, the email
    |                grid's routing, conditions and sender name, and saving
    |                them. Off, Statamic's own settings screen returns.
    |
    | notifications  The notification job: the Active toggle, conditional
    |                notifications, recipient routing, the sender name and the
    |                email body view. Off, notifications send the way
    |                Statamic's own job sends them.
    |
    | confirmations  The message or redirect a visitor sees after submitting.
    |                Off, Statamic's own response after a submission returns.
    |
    | restrictions   Entry limits, the form schedule and required login, on
    |                submit and in the `form_restriction` tag. Off, no
    |                submission is refused and the tag reports every form open.
    |
    | submission_data  Opt-in, off by default. Saves `_*` metadata with each
    |                submission (form, page, site, user agent, IP, referrer,
    |                time) and carries the submitter's request context to
    |                queued notification emails. A host that serves several
    |                sites binds `SubmissionSite` to name the page's site.
    |
    | Each takes true or false, and reads the environment variable named
    | beside it.
    |
    */

    'features' => [
        'control_panel' => (bool) env('FORM_SETTINGS_CONTROL_PANEL', true),
        'notifications' => (bool) env('FORM_SETTINGS_NOTIFICATIONS', true),
        'confirmations' => (bool) env('FORM_SETTINGS_CONFIRMATIONS', true),
        'restrictions' => (bool) env('FORM_SETTINGS_RESTRICTIONS', true),
        'submission_data' => (bool) env('FORM_SETTINGS_SUBMISSION_DATA', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Confirmation page collections
    |--------------------------------------------------------------------------
    |
    | The collections the confirmation page pickers offer. Defaults to
    | `pages`; list the handles of the collections that hold your pages.
    |
    */

    'confirmation_page_collections' => ['pages'],

    /*
    |--------------------------------------------------------------------------
    | Extra Bard buttons
    |--------------------------------------------------------------------------
    |
    | Buttons added to the Article Bard field's toolbar after the heading
    | buttons, for a button that comes from another package, such as
    | `bts_attributes`. Empty by default.
    |
    */

    'article_bard_extra_buttons' => [],

    /*
    |--------------------------------------------------------------------------
    | Quiz scoring
    |--------------------------------------------------------------------------
    |
    | Quiz and survey scoring: the `quiz` and `survey` tags that show a
    | submission's results, the Quiz Settings section on the form settings
    | screen, and the notification email help that lists those tags. On by
    | default. Off, the tags render nothing and the section and help are
    | absent; the `quiz` and `likert_scale` fieldtypes still render on public
    | forms either way, so a stored form that uses them keeps working.
    |
    | Takes true or false, and reads the environment variable named beside it.
    |
    */

    'quiz_scoring' => (bool) env('FORM_SETTINGS_QUIZ_SCORING', true),

    /*
    |--------------------------------------------------------------------------
    | Quiz and survey tags in user content
    |--------------------------------------------------------------------------
    |
    | A confirmation message or notification email is user content, which
    | Antlers parses in its sandbox, and Statamic's default allowlist names
    | only its own tags and the host's. While quiz scoring is on, the add-on
    | adds `quiz`, `quiz:*`, `survey` and `survey:*` to
    | `statamic.antlers.allowedContentTags`, keeping the host's own entries
    | (and Statamic's defaults when the host lists none), so `{{ quiz:score }}`
    | and the rest resolve there. Turn this off to keep the host's allowlist
    | exactly as it is; the tags then resolve only where the host allows them.
    |
    | Takes true or false, and reads the environment variable named beside it.
    |
    */

    'quiz_tags_in_user_content' => (bool) env('FORM_SETTINGS_QUIZ_TAGS_IN_USER_CONTENT', true),

];
