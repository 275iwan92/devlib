## Overview

`<x-dl::wizard.step>` is one page of an `x-dl::wizard`: a `<section>` with a title, an optional icon and hint line, and your fields in its slot. The wizard reads every `data-w-step` section inside it, builds the stepper from their titles and icons, shows one step at a time, and checks the required fields of a step before **Next**.

It is only meaningful as a direct content of `x-dl::wizard`. Outside a wizard it renders a plain, never-hidden section with data attributes that no script reads. Use `x-dl::tabs` or `x-dl::accordion` for free navigation between panels, and `x-dl::form` with `tabs` for a record form.

How it fits with the other wizard tags: `x-dl::wizard` (form, stepper, footer, Review, Done) > `x-dl::wizard.step` (this object, 2 or more) > your fields. The choice rows `x-dl::wizard-options` / `x-dl::wizard-option` are a separate modal pattern and are not steps (see `resources/guide/wizard.md`). The last **Review** step is added automatically by the wizard (`review`, default true): never write it yourself.

## Props

All constructor props of `Devlib\Components\Wizard\Step` (3). Blade names are kebab-case.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `''` | Name of the step: shown in the stepper (with the small text `Step n`), in the Review block, in the error toast `N required fields in step "Title" are empty.` and in the `dl:wizard-step` event. Translated through the dictionary (standard text prop); written to `data-title`. Keep it short and unique. An empty title leaves an empty label in the stepper. |
| `icon` | `?string`, `null` | Content of the stepper circle for this step. `null` or empty = the step number. A DevLib icon meaning (`money`, `user`, `calendar`, `email`, ... the keys of `Devlib\Icon::MAP`) is turned into its Font Awesome class; anything else is used as written, so give a full class such as `fa-building`. The script always writes `<i class="fa-solid ...">`, so only solid-style icon names work. Written to `data-icon`. |
| `hint` | `?string`, `null` | A help line at the top of the step (`<p class="dl-wizard__hint">`), above the fields. Translated. Rendered only when not empty (`null` and `''` render nothing). |

### Attributes accepted on every DevLib object

`can="module.action"` (no access = the step is not rendered at all, so it is also not counted in the stepper and its fields are not posted) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged into the `<section>`.

## Behavior

### Generated markup

```html
<section class="dl-wizard__step" data-dl="x-dl::wizard.step" data-w-step data-title="Contact" data-icon="fa-address-book">
  <p class="dl-wizard__hint">hint</p>   <!-- only with hint -->
  ... slot: your fields ...
</section>
```

The wizard script sets the `hidden` attribute on every step except the current one. Hidden steps are not disabled, so their fields are still posted.

### Order and numbering

The order of the steps in the Blade is the order of the stepper (no sorting, no conditional skipping). The position text of the wizard counts the steps and the Review step: `2 / 4`.

### Validation of the step

When the user presses Next (or opens the next step from the stepper), the wizard checks this step: every `x-dl::field.*` inside the section that shows the red `*` (`required` and a non-empty `label`) and has no value is marked invalid ("Required"), a toast names the step and the number of empty fields, and focus goes to the first one. A field that is required but has no label is not checked. Fields that are not `x-dl::field.*` (plain HTML inputs in the slot) are not checked.

### Review

For the Review step the wizard reads every `.dl-field` of this step: its label and its displayed value, under a heading with the step title and a Change button that comes back to this step. The `hint` is not shown in the Review.

## Data and events

- A step posts nothing by itself: the form of the parent wizard posts all named fields of all steps (see `resources/guide/wizard.md`).
- Entering this step makes the wizard dispatch `dl:wizard-step` with `{index, title}`; it bubbles from the `<form>`, not from the section.
- Hooks: `data-w-step` (the section), `data-title`, `data-icon`; stepper items refer to the step by index (`data-w-go`).
- There is no JavaScript instance for the step; the `Wizard` instance of the form holds all steps (`el.dlComponent.steps`).

## Rules and pitfalls

- Use it only inside `x-dl::wizard`, and give the wizard at least two steps; one step with Review is a form, not a wizard.
- Do not add a step called Review: the wizard appends its own (`review` prop). Set `:review="false"` on the wizard to remove it.
- Mark required fields with `required` **and a label**, otherwise the step check ignores them.
- Do not use the same field `name` in two steps; both are posted.
- Put field layout (`x-dl::form.row`, `x-dl::form.col`) inside the step, not around it.
- `icon` takes one icon; a name that is neither a `Devlib\Icon::MAP` key nor a `fa-*` class gives a circle with no icon.
- `title` and `hint` are dictionary texts; wrap data values in `raw()`.
- Do not nest an `x-dl::wizard` in a step: the inner form would be a form inside a form.
- No lint rule (`devlib:lint`) is specific to this object.
- The stepper adds `fa-solid` itself (`resources/assets/js/components/wizard.js:13`) to the class from `Step.php:34`, so a brand or regular-style icon cannot be used in the stepper.

## Example

Three steps of the catalog demo `wizard-customer` (shortened, fictional data):

```blade
<x-dl::wizard title="Registrasi Customer Baru" finish-label="Daftarkan" done="Customer registered.">
    <x-dl::wizard.step title="Identitas" icon="fa-building" hint="Data badan usaha sesuai NPWP.">
        <x-dl::form.row>
            <x-dl::field.text name="company" label="Nama Perusahaan" required />
            <x-dl::field.masked name="npwp" label="NPWP" mask="99.999.999.9-999.999" />
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
    </x-dl::wizard.step>
</x-dl::wizard>
```

`icon="money"` is a DevLib icon meaning (becomes `fa-money-bill-wave`); `icon="fa-building"` is a Font Awesome class used as written.

## Related

- `x-dl::wizard`: the parent form (stepper, Next / Previous / Finish, Review, Done screen).
- `x-dl::wizard-options` and `x-dl::wizard-option`: choice rows for a wizard in a modal; not steps.
- `x-dl::form.row`, `x-dl::form.col`: layout of the fields in the step.
- `x-dl::field.text` and the other `x-dl::field.*` objects: the inputs; their `required` flag and `label` drive the step check.
- `x-dl::icon`: the icon meanings (`Devlib\Icon::MAP`) that `icon` accepts.
- `x-dl::tabs`: free navigation between panels when no sequence is wanted.
