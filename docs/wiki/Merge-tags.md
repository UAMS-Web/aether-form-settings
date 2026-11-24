Written against v0.5.3.

A notification's message and subject and a confirmation message are Antlers templates, and the form's submission is in scope. The email **Message** field lists the tags below in its own instructions.

| Tag | Result |
| --- | --- |
| `{{ admin_email }}` | The site's default email address. |
| `{{ form:title }}` | The form's title. |
| `{{ form:handle }}` | The form's handle. |
| `{{ submission:id }}` | The submission's ID. |
| `{{ submission:<handle> }}` | A submitted field's value. Replace `<handle>` with the field's handle, for example `{{ submission:email }}`. |
| `{{ all_fields }}` | Every submitted field, formatted. |
| `{{ referrer }}` | The referring page's URL. |
| `{{ ip }}` | The submitter's IP address. |
| `{{ today }}` | The current date, for example `Jan. 1, 2025`. |
| `{{ today:time }}` | The current time. |
| `{{ today:timestamp }}` | The Unix timestamp. |
| `{{ today format="Y-m-d" }}` | The date in a format of your choosing. |

`{{ all_fields }}` takes `show_value="true"` for the stored value instead of the label, `show_empty="true"` to include fields with no value, and `hide_hidden="true"` to leave out hidden field types. A replicator field in `{{ all_fields }}` lists each set under its label with each subfield's label and value, instead of an encoded blob.

While quiz scoring is on, the help also lists the quiz and survey tags: see [Quiz and survey tags](Quiz-and-survey-tags.md).

Where the tags run: [Send notifications](Send-notifications.md) and [Choose what visitors see after submitting](Choose-a-confirmation.md).
