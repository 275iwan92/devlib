## Overview

`<x-dl::wizard>` is the standard **step-by-step form** of DevLib (Form Wizard in the Grid and Form menu). One HTML `<form>` holds several `x-dl::wizard.step` sections; a stepper on top shows them, **Next** checks the required fields of the current step, and an optional last step **Review** lists everything the user typed with a "Change" link per step. **Finish** submits.

A wizard stands alone: it has no grid and no Back button (rule in `skills/devlib/grid-form-flow.md`, section 6). Without `action` it also shows a **Done** screen instead of leaving the page, with a "Start again" button.

Pick another object when:

- the record is edited from a list (grid -> form -> back): `x-dl::form` with `panel="top"`;
- you only need a short dialog with a few fields: `x-dl::modal` with fields, or `x-dl::action-form`;
- you need to choose one of several ready options in a modal before a form opens: `x-dl::wizard-options` + `x-dl::wizard-option` (see below).

### How the four wizard tags fit together

| Tag | Role | Relation |
|---|---|---|
| `x-dl::wizard` | The `<form>`, the stepper, the footer buttons, the Review step and the Done screen. All logic (steps, validation, review, finish) lives here and in its script. | Parent of the steps. |
| `x-dl::wizard.step` | One page of the wizard: a `<section data-w-step>` with a title, an optional icon and hint, and your fields in its slot. | Child of `x-dl::wizard`; give it 2 or more. The `review` step is added by the wizard itself, never write it. |
| `x-dl::wizard-options` | A vertical list container (`dl-wiz`) for choice rows. | Independent of `x-dl::wizard`: it has no script and no props. |
| `x-dl::wizard-option` | One choice row: emoji, title, badge, description, note and your action button(s) in its slot. | Child of `x-dl::wizard-options`. |

The first two form the **form wizard** (this entry). The last two are a separate visual pattern for a **wizard in a modal**: a first dialog where the user chooses a type or a data source by pressing the button of one row, which then opens the next dialog or screen. They are *not* steps of `x-dl::wizard`: they have no field, post nothing, are not counted by the stepper and are not validated. You can put them inside a `wizard.step` (the slot accepts any content), but nothing connects them to Next or to the Review. The flow of a form wizard is therefore:

```
x-dl::wizard (form, stepper, footer)
 ├─ x-dl::wizard.step  "Identity"   ── fields (x-dl::field.*)
 ├─ x-dl::wizard.step  "Contact"    ── fields
 ├─ x-dl::wizard.step  "Finance"    ── fields
 └─ Review step (automatic, review=true)  ── summary + Change links
Footer: Previous | n / N | Next ... Finish
After Finish: POST to action, or (no action) event dl:wizard-finish + Done screen
```

and the modal pattern is `x-dl::modal` -> `x-dl::wizard-options` -> several `x-dl::wizard-option` (each with a button in its slot).

## Props

All constructor props of `Devlib\Components\Wizard` (5). Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string (`:review="false"`).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Heading above the stepper (`dl-wizard__title`). Translated through the dictionary (standard text prop). Empty = no heading. |
| `action` | `?string`, `null` | URL the form posts to. Set: a real POST with `@csrf` (method is always `POST`), the page changes after the server answers. Not set: the form has no `action`, the script cancels the submit, dispatches `dl:wizard-finish` and shows the Done screen (when `done` is set) or a "preview" toast. See Data and events. |
| `review` | `bool`, `true` | Adds the automatic last step **Review**: a summary of every field of every step, with a Change button per step. `:review="false"` removes it, and then the last of your steps shows Finish. Written as `data-review` on the form. |
| `finish-label` | `string`, `'Finish'` | Label of the submit button (an `x-dl::button preset="submit"` shown only on the last step). Use a verb of the screen (`Register`, `Create`). Not part of the dictionary text props, so write it in the language of the screen. |
| `done` | `?string`, `null` | Opt-in text of the **Done screen**: when set, a hidden block (`role="status"`) with a check icon, the word "Done", this text and a "Start again" button is rendered. It appears after Finish **only when no `action` is set**; the stepper, the body and the footer are hidden. With `action` the block is rendered but never shown. The text is not translated (it is data of your screen); "Done" and "Start again" are. |

`x-dl::wizard.step` has its own props (title, icon, hint), see its entry; `x-dl::wizard-options` / `x-dl::wizard-option` have their own.

### Attributes accepted on every DevLib object

`can="module.action"` (no access = not rendered) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged into the `<form>`.

## Behavior

### Generated markup

```html
<form class="dl-wizard" data-dl-wizard data-dl-guard novalidate method="POST" [action="..."] data-review="1" [data-done]>
  @csrf   <!-- only with action -->
  <div class="dl-wizard__title">title</div>
  <ol class="dl-wizard__steps" data-w-steps></ol>              <!-- built by the script, one <li data-w-go="i"> per step -->
  <div class="dl-wizard__body">
    <section class="dl-wizard__step" data-w-step data-title="..." data-icon="..."> ... </section>   <!-- your steps -->
    <section class="dl-wizard__step" data-w-step data-title="Review" data-icon="fa-clipboard-check" data-w-review><div data-w-summary></div></section>
  </div>
  <div class="dl-wizard__done" data-w-done hidden role="status"> ... </div>   <!-- only with done -->
  <div class="dl-wizard__foot">
    Previous (data-w-prev) · <span data-w-pos>2 / 4</span> · Next (data-w-next) · Finish (data-w-finish, type=submit)
  </div>
</form>
```

The form has `novalidate` (the browser does not validate) and `data-dl-guard` (the dirty guard of DevLib: leaving the page after editing asks Save / Don't Save / Cancel, closing the tab shows the browser warning).

### Stepper and navigation

- The stepper is built from the steps: a circle with the step icon (or the number when the step has no icon), the small text `Step n` (translated) and the step title. States: `is-active` (current), `is-done` (before the current one; the connecting line turns green), `is-reached` (visited before, now ahead of the current one). Below 640 px only the circles show.
- **Next** validates the current step; with errors the step does not change. **Previous** goes back without any check and is disabled on the first step. The position text shows `n / N` (the Review step counts).
- On the last step Next is hidden and Finish is shown; on the other steps the reverse.
- Clicking the stepper: a step already reached (`index <= highest reached`) can be opened at any time, in any direction, without validation; the step right after the current one can be opened only if the current one is valid; steps further ahead that were never reached cannot be opened. So the user cannot skip an unchecked step by clicking.
- Entering a step dispatches `dl:wizard-step`.

### Validation per step

- Next checks one step: every `x-dl::field.*` inside the step that has the red `*` (`required`, together with a non-empty `label`, because the star lives in the label row) and no value. The value test is the same as in `x-dl::form`: any named input with text, or a checked checkbox / radio; companion inputs such as currency or country code do not count.
- Each empty required field gets `is-invalid` and the message "Required"; a red toast says `N required fields in step "Title" are empty.`; focus goes to the first one. The error clears as soon as the user types or changes that field.
- There is no other client check (no pattern, no email test): `type="email"` etc. only change the keyboard. The server must validate everything again.
- Finish validates **all** steps in order. The first step with an empty required field is opened and the submit is cancelled, so a user who jumped back and cleared a field is caught.

### Review step

On entering Review the summary is rebuilt from the DOM: for every step a block with the step title, a **Change** button (goes to that step) and, for each `.dl-field` of the step, the label and the displayed value. The value is the text shown to the user: the selected option text of a select or lookup, the displayed date, the phone with its prefix, the toggle label, a check mark with the word "Signed" for a signature, the checked radio / checkbox labels (joined with a comma), the text of an input or textarea; a password is shown as `••••••`; an empty value is shown as a dash. Fields without a label give an empty term.

### Finish, Done and Start again

- With `action`: Finish performs a normal POST of the form, with CSRF, to that URL.
- Without `action`: the submit is cancelled; the script builds an object from the form data (without `_token`), dispatches `dl:wizard-finish` with it, then shows the Done screen when `done` is set, otherwise the toast "Done: the data is collected (preview, not sent)." Nothing is sent anywhere; the screen must send the data itself in the event listener if needed.
- The Done screen hides the stepper, the body and the footer and clears the dirty mark. **Start again** resets the form (`form.reset()`), forgets the reached steps and returns to step 1. There is no Back: the wizard has no grid to return to.

### Accessibility as implemented

Steps not shown carry the `hidden` attribute. The Next / Previous / Finish buttons are native buttons. The stepper is an `ol` of `li` with a click handler: it is not focusable and has no `aria-current`; the Done block has `role="status"`. Required fields are marked by the visual `*` only. Keyboard: Tab through fields and buttons; pressing Enter in a single-line field uses the browser's implicit submit through the Finish button (the only `type="submit"` button, even while hidden). By browser default (not tested here) this runs the all-steps check described above, so it can finish the wizard before the last step when every required field is already filled.

## Data and events

### What the server receives (with `action`)

A POST with `_token` and **all named fields of all steps** (the Review step has no fields). Steps that are not shown are only hidden (`hidden` attribute), not disabled, so their fields are posted too. Each field posts what its own `x-dl::field.*` object posts (for example the number fields post a canonical `1234.5`, see their entries). The server must validate everything and redirect (PRG); on failure it returns to the wizard with `:error` on the fields and a flash message. After a reload the wizard starts again at step 1 (it does not jump to the step that has the error).

### Events (bubble from the `<form>`)

| Event | `detail` | When |
|---|---|---|
| `dl:wizard-step` | `{index, title}` | Every time a step is entered, including the first one when the wizard starts (a listener added after the script has started does not see that one), and also when Review is entered. |
| `dl:wizard-finish` | object `{fieldName: value}` of all fields without `_token` (the last value wins when a name repeats, e.g. `name[]`; files are `File` objects) | Finish without `action`, after all steps are valid. Not cancelable. |

### Markup hooks and JavaScript

| Hook | Where |
|---|---|
| `data-dl-wizard` | the form; JS class `Wizard` |
| `data-w-step` | each step section; `data-title` and `data-icon` are read for the stepper |
| `data-w-review`, `data-w-summary` | the Review step and its summary box |
| `data-w-steps`, `data-w-go="<index>"` | the stepper and its items (the Change buttons use `data-w-go` too) |
| `data-w-prev`, `data-w-next`, `data-w-finish`, `data-w-pos` | footer parts |
| `data-w-done`, `data-w-restart` | Done screen and Start again |
| `data-review`, `data-done` | the flags of the props |
| classes | `dl-wizard`, `dl-wizard__step`, `is-active`, `is-done`, `is-reached`, `is-invalid` on a field |

`el.dlComponent` on the form is the `Wizard` instance: `at` (current index), `reached`, `steps`, `go(i)`, `check(i)` (validates one step, returns true/false), `summary()`, `restart()`, `showDone(bool)`.

## Rules and pitfalls

- A wizard has no grid and no Back button. If the user came from a list, use `x-dl::form`.
- Mark the required fields with `required` **and a label**: the check looks for the `*` in the label row. A required field without a label is not checked.
- Never trust the client check. The server must validate all fields and enforce order or business rules.
- Fields in the Review are listed from the page, not from the server: do not put fields with secrets in a step unless it is fine to show them (passwords are masked, other values are not).
- The wizard has no conditional or skipped steps and no per-step server call. Keep the number of steps small (the stepper scrolls sideways when narrow).
- `done` works only without `action`. In a real project set `action`; use `done` for demos and for pages that send the data themselves from `dl:wizard-finish`.
- `finish-label` and `done` are written as given: put the text in the language of the screen.
- After a failed server validation the wizard returns to step 1; show the error summary in a flash message and mark the fields with `:error`.
- Do not nest a wizard in an `x-dl::form` or another `<form>`: it is itself a form.
- The step title is the label of the Review block and the stepper: keep titles short and unique.
- `Start again` uses `form.reset()` (initial HTML values). Test it with the custom widgets used in your steps (select, date, lookup): their displayed text is not touched by the script.
- No lint rule (`devlib:lint`) is specific to this object.
- Code oddity (not fixed): `resources/assets/js/core.js:465` clears the dirty mark on every `submit` event in the capture phase, before `resources/assets/js/components/wizard.js:22` validates the steps, so a Finish that fails validation also clears the mark of the dirty guard.
- Code oddity (not fixed): the Review step title is the fixed English text `Review` (`src/Components/Wizard.php:45`) and is not passed through the dictionary, while the other fixed texts of the wizard are.

## Example

The catalog demo `wizard-customer`, shortened (fictional data; no `action`, so Finish shows the Done screen):

```blade
<x-dl::wizard title="Registrasi Customer Baru" finish-label="Daftarkan" done="Customer registered. The wizard has no grid, so there is no Back (demo: nothing is stored).">
    <x-dl::wizard.step title="Identitas" icon="fa-building" hint="Data badan usaha sesuai NPWP.">
        <x-dl::form.row>
            <x-dl::field.text name="company" label="Nama Perusahaan" required />
            <x-dl::field.masked name="npwp" label="NPWP" mask="99.999.999.9-999.999" />
        </x-dl::form.row>
        <x-dl::form.row>
            <x-dl::field.select name="category" label="Kategori" :options="['FMCG', 'Farmasi', 'Kosmetik', 'Makanan & Minuman']" required />
            <x-dl::field.radio name="type" label="Bentuk Usaha" :options="['PT' => 'PT', 'CV' => 'CV', 'UD' => 'UD / Perorangan']" value="PT" />
        </x-dl::form.row>
    </x-dl::wizard.step>
    <x-dl::wizard.step title="Kontak" icon="fa-address-book">
        <x-dl::form.row>
            <x-dl::field.text name="pic" label="Nama PIC" required />
            <x-dl::field.phone name="pic_phone" label="No. HP PIC" required />
        </x-dl::form.row>
        <x-dl::field.text name="address" label="Alamat" multiline required />
    </x-dl::wizard.step>
    <x-dl::wizard.step title="Keuangan" icon="money">
        <x-dl::form.row>
            <x-dl::field.money name="credit_limit" label="Limit Kredit" />
            <x-dl::field.select name="term" label="Syarat Bayar" :options="['COD', 'Net 14', 'Net 30', 'Net 45']" required />
        </x-dl::form.row>
        <x-dl::field.toggle name="ppn" label="Faktur Pajak (PPN)" on-label="PPN" off-label="Non PPN" :value="true" />
    </x-dl::wizard.step>
</x-dl::wizard>
```

A real project: post to the server, no Review step, and listen to the steps:

```blade
<x-dl::wizard title="New Customer" :action="route('customer.store')" :review="false" finish-label="Register">
    <x-dl::wizard.step title="Identity" icon="fa-building"> ... fields ... </x-dl::wizard.step>
    <x-dl::wizard.step title="Contact" icon="fa-address-book"> ... fields ... </x-dl::wizard.step>
</x-dl::wizard>
<script>
  document.querySelector('[data-dl-wizard]').addEventListener('dl:wizard-step', e => console.log(e.detail.index, e.detail.title));
</script>
```

## Related

- `x-dl::wizard.step`: one step (title, icon, hint) of this wizard.
- `x-dl::wizard-options` and `x-dl::wizard-option`: the choice rows of a wizard in a modal (separate pattern, see "How the four wizard tags fit together").
- `x-dl::form`: the record form of a grid -> form flow; same required-field rule, but with Save / Confirm / Back.
- `x-dl::field.text`, `x-dl::field.select`, `x-dl::field.radio`, `x-dl::field.phone`, `x-dl::field.money` and the other `x-dl::field.*`: the inputs inside the steps (`required` and `label` drive the step check).
- `x-dl::form.row`, `x-dl::form.col`: field layout inside a step.
- `x-dl::button`: Previous, Next, Finish and Start again are `x-dl::button`.
- `skills/devlib/grid-form-flow.md` (section 6): the rules of the wizard.
