Written against v0.5.3.

A form should answer a successful submission with a message or a redirect of your choosing, and a different one for some visitors.

With the `confirmations` switch off (see [Configuration](Configuration.md)), none of this applies and Statamic's own response returns.

## Steps

1. Open the form's settings screen and go to **Confirmations**.
2. Under **Default Confirmation**, choose a **Confirmation Type** and fill in the fields it shows (see the table below). Save the form.
3. To answer some submissions differently, add one or more **Additional Confirmations**. Each has a **Confirmation Name**, its own **Confirmation Settings** and **Conditional Logic**.

For each submission the first additional confirmation whose conditions pass is used, in the order listed. When none passes, the default is used. A confirmation with no conditions always passes, so place it last. A form with no confirmation settings at all keeps Statamic's own response.

## Confirmation types

| Type | Fields | What the visitor gets |
| --- | --- | --- |
| **Show Message** | **Confirmation Message** | The message, in place of Statamic's generic success text. |
| **Send to External URL** | **External Confirmation URL**, **Query String** | A redirect to the address. |
| **Send to Page Entry** | **Confirmation Page Entry**, **Query String** | A redirect to the entry's URL. |
| **Replace Form With Page Entry Content** | **Confirmation Page Content**, **Query String** | A redirect to the entry's URL. |

The message is rich text, and Antlers runs over it with the submission available, so `{{ submission:name }}` and the other [merge tags](Merge-tags.md) resolve. The quiz and survey tags resolve in it too while quiz scoring is on (see [Quiz and survey tags](Quiz-and-survey-tags.md)). A **Query String** is added to the destination after its merge tags resolve, for example `source=form&topic={{ submission:department }}`.

**Note:** a redirect is followed only when its address is on the application. An address on another site is refused, and the visitor sees the confirmation message instead, when one is set. The type named **Send to External URL** therefore redirects only to a URL on your own site.

**Note:** **Open in New Tab**, **Delay Redirect and Show Message**, **Confirmation Redirect Delay Time** and **Confirmation Redirect Message** are stored with the form. The add-on does not read them: the redirect happens at once. A template or host that wants a delay reads them from the form.

## Reference

- [Form settings screen](Form-settings-screen.md): every field the screen adds.
- [Conditional logic](Conditional-logic.md): how conditions are written and evaluated.
