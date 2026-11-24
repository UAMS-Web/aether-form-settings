Written against v0.5.3.

Each notification email on a form can be switched off, sent only when conditions pass, and sent to recipients chosen from the submission. These are the columns the add-on adds to Statamic's own **Emails** setting on the form settings screen.

With the `control_panel` switch off the added columns are not shown, and with the `notifications` switch off notifications send the way Statamic's own job sends them (see [Configuration](Configuration.md)).

## Steps

1. Open the form's settings screen and add an email under **Emails**, or open an existing one.
2. Give it a **Name**, a **Subject** (required) and a **Message** (required). The message is rich text with [merge tags](Merge-tags.md).
3. Choose who receives it: **TO**, **CC**, **BCC** and **Reply To** each have a recipient type (see below).
4. Optionally set a **From Name**, and **Conditional Logic** to send only for some submissions.
5. Save the form.

## Added columns

| Column | Handle | Effect |
| --- | --- | --- |
| **Active** | `enabled` | On by default. Off, the notification is skipped. |
| **Name** | `name` | A name for the notification, used in the log line when it is skipped. |
| **TO**, **CC**, **BCC** and **Reply To Recipient Type** | `to_type`, `cc_type`, `bcc_type`, `reply_to_type` | How each recipient is chosen. |
| **From Name** | `from_name` | The name shown as the sender, shown while a **From** address is set. |
| **Message** | `message` | The body, in rich text. |
| **Conditional Logic** | `conditional_logic` | Send only when the conditions pass. See [Conditional logic](Conditional-logic.md). |

## Recipient types

| Type | Fields | Recipients |
| --- | --- | --- |
| **Enter One or More Email Addresses** | the recipient field | The addresses written there. A merge tag in them is resolved when the email is sent. |
| **Select a Form Field** | **Recipient(s) Field** | The address the visitor submitted in that field. |
| **Configure Conditional Routing** | **Recipients Conditional Routing** | A list of rows (a field, an operator, a value and an address). Every row that matches the submission adds its address. |

Addresses taken from the submission (a form field or routing) are kept only when they are valid email addresses and hold no template characters (`{` or `}`) or control characters. A notification that resolves no TO recipient is not sent.

## When a notification is sent

For each notification, in order: an inactive one is skipped, then **Conditional Logic** is evaluated and a failing one is skipped, then recipients are resolved and one with no TO recipient is skipped. Notifications are decided independently, so one skipped notification never stops another.

## Queued notifications

A notification sent from a queue worker cannot see the visitor's request. With the `submission_data` switch on, the add-on captures the visitor's IP address, referrer, query parameters and site when the form is submitted and hands them back while the email renders, so `{{ ip }}` and `{{ referrer }}` stay correct on a worker. With the switch off nothing is captured and those tags render the worker's own state. A warning is logged when a notification is queued without a captured context. See [Save submission data](Save-submission-data.md).

## Reference

- [Merge tags](Merge-tags.md): what the message and subject can contain.
- [Configuration](Configuration.md): the `notifications` and `control_panel` switches.
