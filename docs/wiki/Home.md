Written against v0.5.3.

Form Settings is a Statamic add-on that extends the Control Panel's form settings screen and what a form does after a submission: notification emails with recipient routing and conditions, a confirmation message or redirect, restrictions on who can submit and when, quiz and survey scoring, and saved submission data. This wiki holds the walkthrough, the task guides, the reference and troubleshooting. The [README](https://github.com/UAMS-Web/aether-form-settings#readme) keeps the overview, the requirements and installation.

## Getting started

- [Installation (README)](https://github.com/UAMS-Web/aether-form-settings#installation): add the package to a Statamic install.
- [Your first form settings](Your-first-form-settings.md): give one form a confirmation message and an entry limit.

## Guides

- [Send notifications](Send-notifications.md): switch an email on or off, choose its recipients and send it only for some submissions.
- [Choose what visitors see after submitting](Choose-a-confirmation.md): a message or a redirect, different for different answers.
- [Close a form on a date](Close-a-form-on-a-date.md): schedule a form and show a closed message in a template.
- [Score a quiz](Score-a-quiz.md): quiz questions, grading and showing the result.
- [Score a survey](Score-a-survey.md): Likert scale questions and a total score.
- [Save submission data](Save-submission-data.md): keep the page, site and visitor details with each submission.
- [Turn a feature off](Turn-a-feature-off.md): switch one feature off without losing its stored settings.

## Troubleshooting

- [Troubleshooting](Troubleshooting.md): one entry per symptom, with its cause and fix.

## Reference

- [Configuration](Configuration.md): the five feature switches, the other config keys and their environment variables.
- [Form settings screen](Form-settings-screen.md): every section and field the add-on adds.
- [Restriction states](Restriction-states.md): what the `form_restriction` tag reports.
- [Conditional logic](Conditional-logic.md): how a condition is written and evaluated.
- [Merge tags](Merge-tags.md): what a message and a subject can contain.
- [Quiz and survey tags](Quiz-and-survey-tags.md): the `quiz` and `survey` tags.
- [Fieldtypes](Fieldtypes.md): the quiz question and Likert scale fieldtypes.
- [Extending the add-on](Extending.md): the interfaces a host or another package binds.
- [README](https://github.com/UAMS-Web/aether-form-settings#readme): the overview, the requirements, the feature switches and the extension points.

## Project

- [Development](Development.md): where the add-on is developed and how to run it against a host.
- [Report a problem](https://github.com/UAMS-Web/aether-form-settings/issues): open an issue.
- [Releases](https://github.com/UAMS-Web/aether-form-settings/releases): what changed in each version.
