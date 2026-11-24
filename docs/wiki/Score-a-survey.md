Written against v0.5.3.

By the end of this page a form will hold Likert scale questions and show the visitor their answers and a total score.

Survey scoring is on unless `quiz_scoring` is off (see [Configuration](Configuration.md)). Off, the survey tags render nothing, and the Likert scale fieldtype still renders on public forms.

## 1. Add Likert scale questions

In the form's blueprint add a field of the **Likert Scale** fieldtype (handle `likert_scale`) for each question. In its settings:

- **Scale Type**: **Agreement**, **Experience**, **Frequency (With "Always")**, **Frequency (Without "Always")**, **Likelihood**, **Quality** or **Satisfaction**.
- **Number of Points**: from **Two Points** to **Ten Points**. Each point has a numeric value, with the highest rating worth the most.
- **Enable Zero-Point Option** and **Zero-Point Option Label** (default `Not Applicable`): adds a choice that scores `0`.
- **Scale Display Order**: **Low to High** or **High to Low**.
- **Reverse Scale**: gives the lowest conceptual value the highest number.
- **Enable Multiple Rows** and **Row Labels**: one scale for several statements. Every row label must be filled in.
- **Inline**: shows the choices side by side.

## 2. Show the result

```antlers
{{ survey }}
Total: {{ survey:score }}
```

A question scores the number of the point the visitor chose, and a multiple row question the sum of its rows. The survey's score is the sum of every Likert scale question.

## Reference

- [Quiz and survey tags](Quiz-and-survey-tags.md): every tag and parameter.
- [Fieldtypes](Fieldtypes.md): the settings of the Likert scale fieldtype.
