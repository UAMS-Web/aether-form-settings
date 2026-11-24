Written against v0.5.3.

By the end of this page every submission of a form will be saved with the page, site and visitor details it was made from, and a queued notification will know the submitter's request.

## Steps

1. Set `features.submission_data` to `true` in `config/aether-form-settings.php`, or set `FORM_SETTINGS_SUBMISSION_DATA=true` in `.env`.
2. Clear the configuration cache if the application caches it: `php artisan config:clear`.

The switch is off by default, so a submission is saved without any of this until you turn it on.

## What is saved

| Field | Value |
| --- | --- |
| `_form_handle`, `_form_title` | The form's handle and title. |
| `_submitted_url`, `_submitted_path` | The page the form was submitted from. |
| `_site_handle`, `_site_name`, `_site_url` | The site that page belongs to. |
| `_user_agent`, `_ip`, `_referrer` | The visitor's browser, IP address and referring page. |
| `_submitted_at` | The time, in ISO 8601. |

A field of the same name that an editor defined is never overwritten.

## Queued notifications

The visitor's IP address, referrer, query parameters and site are also captured when the form is submitted and carried to the notification job, so the tags that read the request render the submitter's values on a queue worker. A warning is logged when a form email is queued on a non-sync connection without that context. See [Send notifications](Send-notifications.md).

## Several sites

The add-on names the current site. A host that serves several sites binds its own `SubmissionSite` to name the site of the submitting page: see [Extending the add-on](Extending.md).
