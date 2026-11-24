Written against v0.5.3.

The `quiz` and `survey` tags show a scored submission. They read the submission from the confirmation message or the notification email they run in, and they render nothing outside a submission or for a form without quiz or Likert scale fields.

The tags are available while `quiz_scoring` is on. While `quiz_tags_in_user_content` is also on, the add-on adds them to Statamic's `antlers.allowedContentTags` so they resolve in a message (see [Configuration](Configuration.md)).

## `quiz`

| Tag | Result |
| --- | --- |
| `{{ quiz }}` | Every quiz question with the answers given, the correct answers and the explanation. |
| `{{ quiz handle="q1" }}` | One question's breakdown. |
| `{{ quiz:score }}` | The total score. |
| `{{ quiz:score handle="q1" }}` | One question's score. |
| `{{ quiz:percent }}` | The percentage score as a bare number, such as `85`. Add the `%` in the message. |
| `{{ quiz:grade }}` | The letter grade. Empty when the form defines no letter grades. |
| `{{ quiz:passfail }}` | `Passed` or `Failed`. Empty when the form defines no passing percentage. |

Grading is set in the form's **Quiz Settings** section: see [Score a quiz](Score-a-quiz.md).

## `survey`

| Tag | Result |
| --- | --- |
| `{{ survey }}` | Every Likert scale question with the answer given. |
| `{{ survey handle="q1" }}` | One question's answer. |
| `{{ survey:score }}` | The total score. |
| `{{ survey:score handle="q1" }}` | One question's score. |

See [Score a survey](Score-a-survey.md).

## A field named `quiz` or `survey`

Antlers resolves a variable before a tag, so a submitted field whose handle is `quiz` hides the bare `{{ quiz }}` tag, and a field named `survey` hides `{{ survey }}`. Use the `:` forms (`{{ quiz:score }}`) or `handle="..."`, or give the field another handle.
