Written against v0.5.3.

One entry per symptom, as you meet it: the behavior, its cause, and the fix.

- [The form_restriction tag says every form is open](#the-form_restriction-tag-says-every-form-is-open)
- [Notifications ignore the Active toggle and routing](#notifications-ignore-the-active-toggle-and-routing)
- [The form settings screen has no added sections](#the-form-settings-screen-has-no-added-sections)
- [A redirect confirmation shows a message instead](#a-redirect-confirmation-shows-a-message-instead)
- [The quiz and survey tags render nothing](#the-quiz-and-survey-tags-render-nothing)
- [Fixed](#fixed)

## The form_restriction tag says every form is open

A form has a schedule or an entry limit set, and the page still shows the form with `open` true.

**Cause:** the `restrictions` switch is off, so nothing is refused and the tag reports every form open. It is also what the tag reports when the `handle` names no form.

**Fix:** set `features.restrictions` to `true` (or `FORM_SETTINGS_RESTRICTIONS=true`), clear the configuration cache, and check that `handle` is the form's handle. See [Configuration](Configuration.md).

## Notifications ignore the Active toggle and routing

Recipient routing and conditions are saved, and notifications go out as if they were not set.

**Cause:** the add-on points `statamic.forms.send_email_job` at its own job only while that option still holds Statamic's default. A host that has set the option to a job of its own keeps it, and the add-on's routing does not run. The `notifications` switch being off has the same effect.

**Fix:** remove the host's `statamic.forms.send_email_job` override so the add-on can replace Statamic's default, and confirm `features.notifications` is `true`. The add-on's job is final, so a host job cannot extend it.

## The form settings screen has no added sections

The Confirmations, Restrictions and the other added sections do not appear on a form's settings screen.

**Cause:** the `control_panel` switch is off, which returns Statamic's own screen, or the add-on is not installed in the application that serves the Control Panel.

**Fix:** set `features.control_panel` to `true`, then check that `composer show uams-web/aether-form-settings` lists the package and clear the configuration cache.

## A redirect confirmation shows a message instead

A confirmation of type **Send to External URL** is set, and the visitor stays on the form page with the confirmation message.

**Cause:** the add-on follows a redirect only to an address on the application. An address on another site is refused, as Statamic's own form redirect refuses it.

**Fix:** use an address on your site, or choose the confirmation type **Show Message**. See [Choose what visitors see after submitting](Choose-a-confirmation.md).

## The quiz and survey tags render nothing

`{{ quiz:score }}` or `{{ survey }}` in a confirmation message or a notification prints nothing.

**Cause:** quiz scoring is off (`quiz_scoring`), the form has no quiz or Likert scale fields, or the tag is not allowed in user content because `quiz_tags_in_user_content` is off and the host's `statamic.antlers.allowedContentTags` does not list it. A submitted field whose handle is `quiz` or `survey` also hides the bare tag.

**Fix:** set quiz scoring back on with `FORM_SETTINGS_QUIZ_SCORING=true`, check the form's fields, and either turn `quiz_tags_in_user_content` on or add `quiz`, `quiz:*`, `survey` and `survey:*` to the host's list. See [Quiz and survey tags](Quiz-and-survey-tags.md).

## Fixed

Problems fixed in a release stay here, with the release that fixed them, instead of being deleted.

- **v0.2.0:** a host's own submission listener could refuse a submission of a restricted form and report its own message instead of the form's restriction. The add-on's restriction check now runs ahead of the host's listeners.
- **v0.2.0:** a form whose start or end date could not be read was left open. It is now closed with the state `schedule_unreadable`.
- **v0.5.1:** a host's overrides of Statamic's views stopped taking precedence over the add-on's views when the host's view path went through a symlink. They keep precedence now.
