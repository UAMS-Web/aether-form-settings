Written against v0.5.3.

The **Conditional Logic** field appears on each notification and each additional confirmation. It decides whether that notification is sent or that confirmation is chosen, from what the visitor submitted.

## How it is evaluated

- **No conditions always passes.** Leave the field empty to always send or always choose.
- **All or any.** A condition group written with `if` passes when every condition passes. The `_any` forms (`if_any`) pass when at least one does. The `unless` and `hide_when` forms invert the result.
- **Keys** the evaluator reads: `if`, `if_any`, `show_when`, `show_when_any`, `unless`, `unless_any`, `hide_when` and `hide_when_any`. Only the first recognized key in a field is evaluated, and `always_save` is ignored.
- **Operators:** `equals`, `not`, `contains`, `contains_any`, `includes_any`, `===`, `!==`, `>`, `>=`, `<`, `<=` and `custom`. An unknown operator is evaluated as `equals`. `includes_any` tests membership in a list, where `contains_any` tests for a substring (Statamic 6.35 and later).

A condition names a field handle, an operator and a value.

## Where it applies

- [Send notifications](Send-notifications.md): the **Conditional Logic** column, and the rows of **Conditional Routing**.
- [Choose what visitors see after submitting](Choose-a-confirmation.md): each additional confirmation.
