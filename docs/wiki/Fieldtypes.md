Written against v0.5.3.

The add-on registers two fieldtypes that editors add to a form's blueprint, and four that only its own settings screen uses. The two below stay registered whatever the switches say, so a stored form that uses them keeps rendering.

## Quiz Question (`quiz`)

Found under **Controls** in the fieldtype selector, for collection and form blueprints. It renders as radio buttons, checkboxes or a drop-down list, and the correct answers never reach the visitor's browser. A submitted value must be one of the answer labels. A quiz question has no default value.

| Setting | Handle | Notes |
| --- | --- | --- |
| Question Fieldtype | `question_fieldtype` | `radio` (default), `checkboxes` or `select`. |
| Enable Weighted Score | `weighted_score_query` | Off by default. |
| Answers | `options` | A grid, at least two rows: `is_correct`, `label` (required) and, with weighted scoring on, `weighted_score`. |
| Randomize Order of Answers | `randomize_order` | Off by default. |
| Enable Answer Explanation | `answer_explanation_query` | Off by default. |
| Answer Explanation | `answer_explanation` | Required while the explanation is on. |

Guide: [Score a quiz](Score-a-quiz.md).

## Likert Scale (`likert_scale`)

A scale of preset answers, as one row or a matrix of rows. It has no default value.

| Setting | Handle | Notes |
| --- | --- | --- |
| Scale Type | `scale_type` | `agreement` (default), `experience`, `frequency`, `frequency_no_always`, `likelihood`, `quality` or `satisfaction`. |
| Number of Points | `number_of_points` | 2 to 10. Default 5. |
| Enable Zero-Point Option | `enable_not_applicable` | Off by default. |
| Zero-Point Option Label | `not_applicable_label` | Default `Not Applicable`. |
| Scale Display Order | `display_order` | `asc` (Low to High, default) or `desc`. |
| Reverse Scale | `reverse_scale` | Off by default. |
| Enable Multiple Rows | `enable_multiple_rows` | Off by default. |
| Row Labels | `multiple_rows` | A list, required with multiple rows, no blank labels. |
| Inline | `inline` | Shows the choices side by side. |

Guide: [Score a survey](Score-a-survey.md).

## Fieldtypes for the settings screen

`form_conditional_logic`, `form_email_field_select`, `form_email_routing` and `form_settings_subsection` draw the add-on's own fields on the form settings screen. They are not selectable in a blueprint.
