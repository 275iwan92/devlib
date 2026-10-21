## Overview

`<x-dl::field.tags>` is a multi-label input: the user types a word, presses `Enter` or `,`, and it becomes a chip; a click on the `x` of a chip removes it. The result is a list of free-text strings. Use it for keywords, categories or any "list of short words" where the values are not rows of a master table. For values that must come from a fixed list use `x-dl::field.multi-select`; for name/value pairs use `x-dl::field.key-value`.

It sits in the shared field frame (`Devlib\Components\Field\Field`): label row, the control, the error line and the optional hint. Extra HTML attributes (`id`, `class`, `data-*`) land on the root `<div class="dl-field dl-field--tags ...">`. `can="module.action"` removes the whole field when the user lacks the ability.

### What it is not

- Not restricted to the `suggestions`: any text can be added.
- Not a "choose from master" control (see `x-dl::field.lookup`) and it holds no keys, only the text of each tag.
- Not a validator: no maximum count, no length limit, no format check, in the browser or on the server.

## Props

Constructor of `Devlib\Components\Field\Tags` (15 props). Blade names are kebab-case; arrays use the `:` binding.

### Own props

| Prop | Type, default | Meaning |
|---|---|---|
| `suggestions` | `array`, `[]` | Proposals for the browser's native autocomplete: when not empty a `<datalist id="<uid>-list">` with one `<option value>` per item is rendered and linked to the text input (`list=` attribute). The browser filters it while typing. It is only a help: free text is still accepted, and already chosen tags are not removed from the list. Items are plain strings (not translated). |

### Inherited props

Defined once in `Field`; the full reference is in `x-dl::field.text`. Differences for this control:

| Prop | Type, default | In this field |
|---|---|---|
| `name` | `string`, `''` | Base name. Every chip carries a hidden input named `<name>[]`, so write the name **without** brackets. Also `data-dl-field`, the `data-name` of the control, and the slug of the generated id placed on the text input (the `<label for>` target). |
| `label` | `string`, `''` | Label text (translated). Empty = no label row. |
| `tooltip` | `?string`, `null` | Text of the (i) bubble (translated). |
| `hint` | `?string`, `null` | Help line under the control (translated). |
| `error` | `?string`, `null` | Server error text; non-empty adds `is-invalid`. Not translated. |
| `value` | `mixed`, `null` | Initial tags: an array of strings (`(array) $value` is looped, so a single string becomes one tag, `'a,b'` is **one** tag `a,b`, `null` is none). For an associative array only the values are used. |
| `placeholder` | `?string`, `null` | Placeholder of the text input (translated); default is the dictionary text "Type, then Enter...". It is visible whenever the input is empty, also when chips exist. |
| `required` | `bool`, `false` | Red `*` and the `x-dl::form` "Required" check (at least one chip). No HTML `required`, no server check. |
| `disabled` | `bool`, `false` | Disables the **text input** (nothing can be typed), the `x` buttons of the chips and their hidden inputs, so nothing is posted. |
| `readonly` | `bool`, `false` | Same as `disabled` for the text input and the `x` buttons (the chips are still posted), plus the class `is-readonly`. Forced on while `Field::$readonlyAll` is true. |
| `icon` | `?string`, `null` | Not used by this control. |
| `size` | `string`, `'sm'` | `sm` or `lg`; only the CSS class `dl-field--sm\|lg`. |
| `i18n` | `?string`, `null` | Written to `data-i18n` on the label for auth screens. |
| `source` | `?string`, `null` | Free text for the developer catalog (default `input "<name>"`); no visual effect. |

## Behavior

### Markup

```
div.dl-field.dl-field--tags[data-dl-field=name]
  label[for=uid] ...
  div.dl-tags[data-dl-tags][data-name=name]
    div.dl-tags__box
      span.dl-tags__chip  "text" + input[type=hidden][name="name[]"] + button[data-dl-tag-x] ×     (one per tag)
      input#uid.dl-tags__input[type=text][list=uid-list]                                          (no name, not posted)
    datalist#uid-list > option...                                                                 (only with suggestions)
```

### Adding a tag

The class `Tags` (`components/tags.js`) listens on the text input:

| Input | Effect |
|---|---|
| `Enter` or `,` | The default action is cancelled (so `Enter` does **not** submit the form and no comma is typed) and the whole current text is added. |
| leaving the input (the native `change` event fires on blur when the text changed) | The text is added the same way, so a word typed and then left (for example by clicking Save) is not lost. A suggestion picked from the datalist is committed by `Enter` or by leaving the input. |
| `Backspace` with an empty input | The last chip is removed (no confirmation). |
| a click on the box | Focuses the text input. |
| a click on a chip's `x` | That chip is removed. |

Adding trims the text, drops one trailing comma, ignores an empty result and ignores a **duplicate** (case-insensitive comparison with the chips already present; the first spelling wins and the input is cleared). A new chip is inserted before the input, its text is HTML-escaped. There is no limit on the number or length of tags.

Pasting `a,b,c` and pressing `Enter` creates one tag `a,b,c`: only a trailing comma is stripped, there is no splitting.

### Read-only and disabled

The text input and the `x` buttons of the chips carry `disabled` for both flags (a disabled button gets no click). The hidden inputs are disabled by `disabled` (nothing is posted) and stay enabled for `readonly`. Chips added by script after load get an enabled `x`. Inside a locked `x-dl::form` (disabled fieldset, or `mode="view"`) the browser disables the buttons and the inputs, so the chips cannot be edited and are not submitted.

## Data and events

### What the server receives

A repeated request field `name[]`, one per chip, in display order, each with the exact text. **With no chips the field is absent** from the request (not an empty array); read it with a default and treat "absent" as "no tags".

```php
$keywords = collect($request->input('keywords', []))->map(fn ($t) => trim($t))->filter()->unique()->values();
$request->validate(['keywords' => ['array', 'max:10'], 'keywords.*' => ['string', 'max:30']]);
```

The text input and the datalist are not posted. Text that is typed but not yet committed (no `Enter`, no blur) is not part of the request.

### DOM events and hooks

| Hook | Detail |
|---|---|
| events | **None.** Adding or removing a chip dispatches no event at all (no `change`, no `input`, no `dl:*`). The only events seen are the native `input`/`change` of the unnamed text input. |
| `data-dl-tags`, `data-name` | The `.dl-tags` element of this control and the base name used for new chips. |
| `.dl-tags__chip`, `[data-dl-tag-x]`, `.dl-tags__input`, `.dl-tags__box` | A chip, its remove button, the text input, the box. |
| `data-dl-field="<name>"` | Root attribute (used by validation). |
| `el.dlComponent` | On the `[data-dl-tags]` element it is the `Tags` instance; `add(text)` adds a tag by script (`el.querySelector('[data-dl-tags]').dlComponent.add('new')`). To read the tags use `form.querySelectorAll('input[name="keywords[]"]')`. |

### Form integration

- The Save/Confirm check marks the field "Required" when there is no chip (the hidden inputs count as values; the typed text does not).
- Adding or removing a chip dispatches a bubbling `change` on the root `[data-dl-tags]` (it carries `data-dl-reports`, which the dirty guard accepts in place of a field name), so the form becomes dirty, Undo is enabled and a "Required" error disappears when a chip exists. Setting chips from script (`Devlib.fill`, Undo) fires nothing.
- `Devlib.fill`, `Devlib.setField` and the form's Undo / Cancel-in-view-mode restore the chips: the key `<name>[]` (or `<name>`) takes an array of strings, `Tags.set(values)` replaces the chips; `clear=true` removes them when the key is absent.

## Rules and pitfalls

- Write the `name` without `[]`.
- An empty list is simply missing in the request. Update stored data with `$request->input('x', [])`, otherwise the last tag cannot be removed.
- The server must normalise and validate the strings (trim, unique, max count, max length, allowed characters). The browser removes only exact case-insensitive duplicates of the current chips.
- `disabled`/`readonly` freeze the chips that were rendered; `disabled` also keeps them out of the request.
- Typed-but-uncommitted text is lost on a scripted submit that does not blur the input; commit it with `Enter` or by leaving the field first. Submitting with the Save button works because the click blurs the input first.
- Tags are not translated; the placeholder is.
- Keyboard: the text input is a normal input (`Tab`, typing, `Enter`/`,` to add, `Backspace` to delete the last chip); each chip's `x` is a `<button>` that can be reached with `Tab` and pressed with `Enter`/`Space`, but it has no accessible name (only the "x" glyph) and the chip list is not announced. The native datalist supports the arrow keys.

## Example

Keywords with suggestions (as in the catalog demo):

```blade
<x-dl::field.tags name="keywords" label="Product keywords" tooltip="Type a word, then press Enter or a comma."
    :value="old('keywords', $product->keywords ?? ['box', 'duplex'])"
    :suggestions="['box', 'duplex', 'ivory', 'lamination', 'hot print', 'emboss']" :error="$errors->first('keywords')" />
```

Without suggestions and with its own placeholder:

```blade
<x-dl::field.tags name="skills" label="Skills" placeholder="Add a skill..." hint="Maximum 10 tags." :value="$employee->skills ?? []" />
```

Controller side:

```php
$product->keywords = collect($request->input('keywords', []))->map(fn ($t) => trim($t))->filter()->unique()->values()->all();
```

## Related

- `x-dl::field.text`: the full reference of the 14 inherited props and the shared field frame.
- `x-dl::field.multi-select`: choose several values from a fixed list (also posted as `name[]`).
- `x-dl::field.key-value`: a list of name/value pairs with add/remove row.
- `x-dl::field.lookup`: choose one master row instead of free text.
- `x-dl::form`: required check, dirty guard and Undo.
- `skills/devlib/form.md`, `skills/devlib/components.md`: project rules for forms and fields.
