Written against v0.5.3.

By the end of this page one form will show a custom confirmation message after a submission and will stop accepting entries after a set number.

It assumes the add-on is installed (see [Installation (README)](https://github.com/UAMS-Web/aether-form-settings#installation)) on Statamic 6.35 or later, and that a form already exists.

## 1. Open the form's settings

In the Control Panel, open the form and then its settings screen. With the add-on installed and the `control_panel` switch on, the screen gains **Heading and Description**, **Submit**, **Confirmations**, **Restrictions** and **Quiz Settings** sections beside Statamic's own. [Form settings screen](Form-settings-screen.md) lists them all.

## 2. Set a confirmation message

In **Confirmations**, under **Default Confirmation**, set **Confirmation Type** to **Show Message** and write the text in **Confirmation Message**. Save the form.

Submit the form on the site. The visitor sees the message instead of Statamic's default response.

**Note:** the other confirmation types redirect the visitor to a URL on your site or to a page entry. They are chosen in the same place: see [Choose what visitors see after submitting](Choose-a-confirmation.md).

## 3. Limit the entries

In **Restrictions**, switch on **Limit the Number of Entries**, set **Number of Entries Allowed**, and choose a **Type of Limit**: **Total Entries**, **Per Day**, **Per Week**, **Per Month** or **Per Year**. Write the text visitors should see in **Message to Display When the Entry Limit is Reached**, then save.

Once the form has that many entries for the chosen period, a submission is refused and the visitor sees that message.

## Go further

- [Close a form on a date](Close-a-form-on-a-date.md): schedule a form and show a closed message on the page.
- [Restriction states](Restriction-states.md): the states a restricted form can report.
- [Send notifications](Send-notifications.md): email on a submission, to recipients chosen from it.
