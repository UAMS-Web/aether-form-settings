# Form Settings

A Statamic add-on that extends the Control Panel's form settings screen and what a form does after a submission: notification emails with recipient routing and conditions, a confirmation message or redirect, restrictions on who can submit and when, and quiz and survey scoring.

## Requirements

PHP 8.4 or later and Statamic 6.35 or later.

## Installation

```bash
composer require uams-web/aether-form-settings
```

Statamic discovers the add-on through Composer. To change its settings, publish the config file:

```bash
php artisan vendor:publish --tag=aether-form-settings-config
```

## Features

Each feature has its own switch in `config/aether-form-settings.php`, and all are on by default except `submission_data`. Turning one off disables that feature alone; the others keep working. Stored form settings are kept either way, so turning a feature back on restores it as it was.

| Switch | Environment variable | What it covers |
| --- | --- | --- |
| `control_panel` | `FORM_SETTINGS_CONTROL_PANEL` | The added sections on the form settings screen, the email grid's routing, conditions and sender name, and saving them. |
| `notifications` | `FORM_SETTINGS_NOTIFICATIONS` | The notification job: the Active toggle, conditional notifications, recipient routing, the sender name and the email body view. |
| `confirmations` | `FORM_SETTINGS_CONFIRMATIONS` | The message or redirect a visitor sees after submitting. |
| `restrictions` | `FORM_SETTINGS_RESTRICTIONS` | Entry limits, the form schedule and required login, on submit and in the `form_restriction` tag. |
| `submission_data` | `FORM_SETTINGS_SUBMISSION_DATA` | Off by default. The `_*` metadata saved with each submission and the submitter's request context for queued notification emails. |

With `submission_data` on, every submission is saved with `_form_handle`, `_form_title`, `_submitted_url`, `_submitted_path`, `_site_handle`, `_site_name`, `_site_url`, `_user_agent`, `_ip`, `_referrer` and `_submitted_at`, and an editor field of the same name is never overwritten. The submitter's IP, referrer, query parameters and site are also captured when the form is submitted and carried to the notification job, so tags that read the request render the submitter's values from a queue worker. A warning is logged when a form email is queued on a non-sync connection without that context. Off, a submission is saved with none of this and no error. The add-on names the site with the current site; a host that serves several sites binds its own `UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionSite` (see Extending).

With `notifications` on, the add-on points `statamic.forms.send_email_job` at its own job. A host that has set that option to a job of its own keeps it.

Two more keys adapt the form settings screen to a host: `confirmation_page_collections` (default `['pages']`) and `article_bard_extra_buttons` (default none). See the wiki's Configuration page.

`quiz_scoring` (`FORM_SETTINGS_QUIZ_SCORING`, default on) switches quiz and survey scoring: the `quiz` and `survey` tags, the Quiz Settings section and the quiz merge-tag help in the notification email instructions. Off, the tags render nothing and the section and help are absent. The `quiz` and `likert_scale` fieldtypes and their public form views stay registered either way, so a stored form that uses them keeps rendering.

While scoring is on, the add-on adds `quiz`, `quiz:*`, `survey` and `survey:*` to `statamic.antlers.allowedContentTags`, after the host's own entries, so the tags resolve in a confirmation message or a notification email, which Antlers parses as user content. A host that wants to manage that list itself sets `quiz_tags_in_user_content` (`FORM_SETTINGS_QUIZ_TAGS_IN_USER_CONTENT`, default on) to false.

## Templates

The `form_restriction` tag reports a form's restriction state, so a page can show a closed message instead of the form:

```antlers
{{ form_restriction handle="contact" }}
    {{ if open }}
        {{ form:contact }} ... {{ /form:contact }}
    {{ else }}
        <p>{{ message }}</p>
    {{ /if }}
{{ /form_restriction }}
```

It exposes `open`, `state` (`open`, `login_required`, `pending`, `expired`, `schedule_unreadable` or `limit_reached`) and `message`.

The `quiz` and `survey` tags show a scored submission in a confirmation message or a notification email. `{{ quiz }}` lists each question with its answers, the correct answer and the explanation; `{{ quiz:score }}`, `{{ quiz:percent }}`, `{{ quiz:grade }}` and `{{ quiz:passfail }}` give the result, by the grading set in the form's Quiz Settings. `{{ survey }}` lists each Likert question with the answer given, and `{{ survey:score }}` the total. `{{ quiz }}`, `{{ quiz:score }}`, `{{ survey }}` and `{{ survey:score }}` take `handle="..."` for a single question.

## Extending

- **Submission context.** A notification sent from a queue worker cannot see the submitter's request. A host that keeps that context binds its own implementation of `UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionContext`: the job calls it to capture the context when the form is submitted, and again to make that context active while the email is sent. The add-on's default captures nothing, and the `submission_data` feature binds one that carries the request context.
- **Submission site.** The `submission_data` feature asks `UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionSite` which site a submission was made from, both for the `_site_*` metadata and for the captured request context. The default answers with the current site. A host whose form endpoint sits under one site's prefix binds an implementation that names the site of the submitting page.
- **More settings sections.** Another package adds a section to the form settings screen by tagging an implementation of `UAMSWeb\Aether\Forms\Settings\Contracts\FormSettingsSection` with `FormSettingsSection::TAG` in its service provider's `register()`.

## Upgrading from `statamic-form-settings`

The package was renamed from `uams-web/statamic-form-settings` to `uams-web/aether-form-settings`, and Composer does not redirect a package name. The add-on handle, config key, publish tags and published asset path follow the package name, so a site changes four things:

1. Run `composer remove uams-web/statamic-form-settings` and `composer require uams-web/aether-form-settings`.
2. Run `php artisan vendor:publish --tag=aether-form-settings --force`, then delete `public/vendor/statamic-form-settings/`. The Control Panel build now publishes to `public/vendor/aether-form-settings/build`.
3. Where a site published the configuration, rename `config/statamic-form-settings.php` to `config/aether-form-settings.php`. The add-on registers no settings blueprint, so there is no stored add-on settings file to move.
4. Update references to the PHP namespace, which changed from `UAMSWeb\FormSettings` to `UAMSWeb\Aether\Forms\Settings`, and to the translation namespace `statamic-form-settings::`, which is now `aether-form-settings::`.

Environment variables, view namespaces and the form tags are unchanged.

## License

MIT. See [`LICENSE`](LICENSE).

## Development

Developing against a host application is covered in [the development guide](docs/DEVELOPMENT.md), and the Packagist and Marketplace steps in [the submission checklist](docs/SUBMISSION.md). The gate is `composer ci`.
