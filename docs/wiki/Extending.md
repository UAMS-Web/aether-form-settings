Written against v0.5.3.

The add-on has three extension points, each an interface in the `UAMSWeb\Aether\Forms\Settings\Contracts` namespace that a host or another package binds in a service provider.

## Submission context

`SubmissionContext` carries a request across the queue. A notification sent from a worker cannot see the submitter's request. A host that keeps that context binds its own implementation of the interface:

- `capture(Submission $submission): ?array` runs when the form is submitted and returns a plain array that serializes onto the job, or `null`.
- `during(array $context, callable $send): mixed` makes that context active while the email is sent, and returns what `$send` returns.

The add-on's default binding captures nothing. The `submission_data` switch binds one that carries the request's IP address, referrer, query parameters and site (see [Save submission data](Save-submission-data.md)). A host's own binding is kept.

## Submission site

`SubmissionSite` names the site a submission was made from, for the `_site_*` metadata and the captured context: `forSubmission(?Submission $submission = null): Site`. The default answers with the current site. A host whose form endpoint sits under one site's prefix binds an implementation that names the site of the submitting page.

## More settings sections

`FormSettingsSection` adds a section to the form settings screen: `section(Form $form): array` returns the section definition. Tag an implementation with `FormSettingsSection::TAG` (`aether-form-settings.sections`) in a service provider's `register()`, and the screen shows it after the add-on's own sections, while the `control_panel` switch is on.

## Reference

- [Configuration](Configuration.md): the switches that turn each feature on and off.
- [Form settings screen](Form-settings-screen.md): the sections the add-on adds.
