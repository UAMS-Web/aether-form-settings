Written against v0.5.3.

With the `control_panel` switch on (see [Configuration](Configuration.md)), the form settings screen in the Control Panel gains five sections beside Statamic's own. This page lists each one and every field in it. A setting is stored with the form, in its YAML file, under the handle shown.

| Section | What it holds | Guide |
| --- | --- | --- |
| Heading and Description | `heading`, `description` | This page |
| Submit | `submit` | This page |
| Confirmations | `confirmation_settings_default`, `confirmation_additional` | [Choose what visitors see after submitting](Choose-a-confirmation.md) |
| Restrictions | `limit_entries`, `schedule_form_query`, `require_login_query` and their messages | [Restriction states](Restriction-states.md) |
| Quiz Settings | `quiz_grading`, `quiz_pass_percent`, `quiz_grades` | [Score a quiz](Score-a-quiz.md) |

The email notifications Statamic already shows on this screen gain columns too: see [Send notifications](Send-notifications.md). Another package can add a section of its own: see [Extending the add-on](Extending.md).

## Heading and Description

| Field | Handle | Notes |
| --- | --- | --- |
| Form Heading | `heading` | A brief heading that a template may show with the form. |
| Description | `description` | What the form is for and what visitors can expect. A template may show it with the form. |

The add-on stores both and shows neither: a template reads them from the form.

## Submit

| Field | Handle | Notes |
| --- | --- | --- |
| Submit Button Label | `submit` | Required. Defaults to `Submit`. A template reads it for the button text. |

## Restrictions

| Field | Handle | Notes |
| --- | --- | --- |
| Limit the Number of Entries | `limit_entries` | Switch on to close the form once it has enough entries. |
| Number of Entries Allowed | `limit_entries_count` | Required while the limit is on. |
| Type of Limit | `limit_entries_period` | **Total Entries**, **Per Day**, **Per Week**, **Per Month** or **Per Year**. |
| Message to Display When the Entry Limit is Reached | `limit_entries_message` | Shown for the `limit_reached` state. |
| Schedule the Form | `schedule_form_query` | Switch on to accept submissions only inside a window. |
| Form Schedule | `schedule_form` | **Start Date and Time** (`start_datetime`) and **End Date and Time** (`end_datetime`). |
| Message to Display When the Form is Pending | `schedule_pending_message` | Shown before the start date. |
| Message to Display When the Form Has Expired | `schedule_expired_message` | Shown after the end date. |
| Require the User to Be Logged In | `require_login_query` | Switch on to refuse visitors who are not logged in. |
| Message to Display When the User is Not Logged In | `require_login_message` | Shown for the `login_required` state. |

The states these produce, and the order they are checked in, are on [Restriction states](Restriction-states.md).
