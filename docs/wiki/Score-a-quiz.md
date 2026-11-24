Written against v0.5.3.

By the end of this page a form will hold quiz questions, grade each submission, and show the visitor their result in the confirmation message or a notification.

Quiz scoring is on unless `quiz_scoring` is off (see [Configuration](Configuration.md)). Off, the Quiz Settings section and the quiz tags go away, and the quiz fieldtype still renders on public forms.

## 1. Add quiz questions

In the form's blueprint add a field of the **Quiz Question** fieldtype (handle `quiz`) for each question. In its settings:

- **Question Fieldtype**: **Radio Buttons** (the default), **Checkboxes** or **Drop-Down List**.
- **Answers**: at least two rows, each with an **Answer Label** (required) and a **Correct Answer** toggle.
- **Enable Weighted Score**: off, a question scores `1` when answered correctly and `0` otherwise. On, each answer row gains a **Weighted Score** and a question scores the sum of the selected answers' weights, whether or not they are correct.
- **Randomize Order of Answers**: shows the answers in a different order each time.
- **Enable Answer Explanation** and **Answer Explanation**: text shown with the question's results.

A visitor's answer is stored as the answer's label. A submitted answer that is not one of the labels is rejected.

## 2. Choose how to grade

On the form's settings screen open **Quiz Settings** and set **Grading**:

| Choice | Fields | `{{ quiz:grade }}` and `{{ quiz:passfail }}` |
| --- | --- | --- |
| **No grading** (default) | none | Both are empty: no grades or passing percentage are stored. |
| **Pass / Fail** | **Passing Percentage**, 0 to 100 | `{{ quiz:passfail }}` is `Passed` when the score percentage reaches it, otherwise `Failed`. |
| **Letter grades** | **Letter Grades**, each a **Grade** and a **Minimum %** | `{{ quiz:grade }}` is the highest grade whose minimum the percentage meets or exceeds. |

## 3. Show the result

Add the tags to the confirmation message or the notification message. For example:

```antlers
You scored {{ quiz:score }} ({{ quiz:percent }}%).
{{ quiz:passfail }}
{{ quiz }}
```

## How a question scores

- **Unweighted:** one point when every correct answer is selected and no incorrect answer is, otherwise none.
- **Weighted:** the sum of the weights of the selected answers. The question's maximum is the sum of the positive weights for checkboxes, or the highest single weight for radio buttons and drop-down lists.
- The percentage is the total score over the total maximum, rounded for display. Grades and pass or fail use the unrounded value.

## Reference

- [Quiz and survey tags](Quiz-and-survey-tags.md): every tag and parameter.
- [Fieldtypes](Fieldtypes.md): the settings of the quiz fieldtype.
