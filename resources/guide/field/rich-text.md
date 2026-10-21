## Overview

`<x-dl::field.rich-text>` is a small rich-text note box: a toolbar (Bold, Italic, Underline, bulleted list, numbered list, clear formatting) above an editable area. The text is kept as HTML in a hidden `<textarea>`, and that HTML string is what the form posts. It is built on a `contenteditable` element and `document.execCommand`; there is no editor library.

It extends the abstract `Devlib\Components\Field\Field` (label row, error line, hint line, 14 inherited props) and adds one own prop, `rows`. The behaviour is in the JS class `Rich` (`resources/assets/js/components/rich.js`, selector `[data-dl-rich]`).

### What it is not

- Not a sanitiser. DevLib never cleans the HTML, in or out. Sanitising is the server's job (see Rules and pitfalls); the class comment says the same ("must be filtered before saving or showing to other users").
- Not a full editor: no headings, links, images, tables, colours, undo button or HTML source view.
- Not for plain text: for a multi-line plain note use `x-dl::field.text` with `multiline`.

## Props

Own prop (Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string):

| Prop | Type, default | Meaning |
|---|---|---|
| `rows` | `int`, `5` | Minimum height of the editing area: inline `min-height: calc(<rows> * 1.5em)`. The area grows with its content (no maximum, no inner scrollbar unless you set a height with CSS). It is not a row count of text lines. |

### Inherited props

All 14 props come from `Field` (full reference: `x-dl::field.text`). Where this control differs:

| Prop | Effect on `rich-text` |
|---|---|
| `name` | Name of the hidden `<textarea>` that is posted; also `data-dl-field` and the id part. Without a name nothing is posted. |
| `label` | Label above the box (the label's `for` is the editing `div`, see Accessibility). `''` removes the label row, the `*` and the tooltip. Translated. |
| `tooltip` | (i) bubble beside the label, only with a non-empty `label`. |
| `hint` | Help line under the box, rendered whenever not `null`. |
| `error` | Server error under the box; non-empty adds `is-invalid`. Not translated. |
| `value` | Initial content as an HTML string. Output twice: raw (`{!! !!}`) into the editing area, escaped into the hidden textarea. Must be sanitised HTML (see pitfalls). |
| `placeholder` | Written to `data-placeholder`; CSS shows it while the area is `:empty`. Translated like other text props. |
| `required` | Red `*`; `x-dl::form` Save/Confirm marks the field "Required" when the hidden textarea is blank. |
| `disabled` | `contenteditable="false"` (text cannot be edited). The hidden textarea is not disabled, so the value is still posted. |
| `readonly` | Same as `disabled` for this control: `contenteditable="false"`, value posted. The root also gets `is-readonly`. |
| `icon` | Unused. |
| `size` | `sm` (default) or `lg`: only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` on the label (auth screens only). |
| `source` | Catalog text for the data origin; no visual effect. |

Attributes accepted on every DevLib object: `can="module.action"` (no access removes the field) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged onto the root `div.dl-field`.

## Behavior

### Markup

```html
<div class="dl-field dl-field--rich-text dl-field--sm" data-dl-field="catatan">
  <div class="dl-field__labelrow"><label class="dl-field__label" for="dlf-catatan-...">Notes</label></div>
  <div class="dl-rich" data-dl-rich>
    <div class="dl-rich__bar"><button type="button" data-cmd="bold" title="Bold"><b>B</b></button> ... </div>
    <div id="dlf-catatan-..." class="dl-rich__area" contenteditable="true" style="min-height:calc(5 * 1.5em)" data-placeholder="">initial HTML</div>
    <textarea name="catatan" hidden data-dl-rich-value>initial HTML (escaped)</textarea>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

### Toolbar

Each button has `data-cmd` holding an `execCommand` name:

| Button (title) | `data-cmd` | HTML typically produced |
|---|---|---|
| B (Bold) | `bold` | `<b>` |
| I (Italic) | `italic` | `<i>` |
| U (Underline) | `underline` | `<u>` |
| list (Bulleted list) | `insertUnorderedList` | `<ul><li>` |
| list (Numbered list) | `insertOrderedList` | `<ol><li>` |
| eraser (Clear formatting) | `removeFormat` | removes inline formatting of the selection |

The buttons react to `mousedown` (with `preventDefault`, so the text selection and focus stay in the area), run `document.execCommand(cmd)` and sync. The exact markup is decided by the browser (it varies between browsers and versions; `execCommand` is a deprecated API), so never depend on a particular tag set on the server. Buttons are `type="button"` and never submit the form. There is no keyboard shortcut code of its own; the browser's built-in Ctrl+B / I / U work inside a `contenteditable`.

### Syncing the value

`Rich.sync()` copies `area.innerHTML` into the hidden textarea. It is called after a toolbar command and on the area's `input` event (typing, deleting, pasting, drag-drop of text). Until the user changes something, the textarea holds the original `value` as passed, not the browser-normalised HTML. After the user clears everything, the posted value can be `''` or something like `<br>` / `<div><br></div>`, because browsers leave a line break behind.

The script strips and filters nothing: pasted content keeps whatever HTML the browser accepts from the clipboard (formatting spans, links, images and so on), and is posted as it is.

### Placeholder

CSS `.dl-rich__area:empty::before` shows `data-placeholder` while the area has no child nodes at all. After typing and deleting, many browsers leave a `<br>`, and then the placeholder does not come back.

### Locked states

`disabled` / `readonly` set `contenteditable="false"`. The toolbar stays enabled but its commands have nothing to edit. In a disabled `fieldset` (a locked `x-dl::form`, `mode="view"`) the toolbar buttons and the hidden textarea are disabled (the value is not posted), but the editing area itself is still `contenteditable`: the user can type into it, with no effect on the form. Pass `readonly` for a note that must look locked.

### Accessibility

As implemented: the toolbar buttons have a `title` (tooltip and fallback name) and an icon or letter, no `aria-label` or `aria-pressed`. The editing area is a plain `div` with `contenteditable` (focusable by Tab) but no `role="textbox"`, `aria-multiline` or `aria-labelledby`; the label's `for` points to that `div`, which is not a labelable element, so the label text is not its accessible name.

## Data and events

### What the server receives

One text parameter named `name`: the HTML string of the area (for example `Please send <b>before the 30th</b>.`). Always treat it as untrusted input. The field posts nothing when `name` is empty, and nothing when the textarea is disabled by a locked form.

No `enctype` is needed.

### Interaction with the form object

- Required check (`x-dl::form`, Save/Confirm): the textarea value must be non-blank after `trim()`. A leftover `<br>` or `<div><br></div>` counts as a value, so "empty" rich text can pass `required`.
- Undo snapshot, QR extra button and `dl:form-submit` payload: the HTML string is a normal value in them.
- Wizard Review summary shows the raw HTML string.
- Dirty guard: it listens to `input`/`change` events whose target has a `name`; typing happens in the unnamed area, so see the oddity note.

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-rich` | root `.dl-rich`; JS class `Rich` |
| `.dl-rich__area` | the `contenteditable` area (also gets the field id) |
| `data-dl-rich-value` | the hidden `<textarea name>` |
| `data-cmd` | each toolbar button (`execCommand` name) |
| `data-placeholder` | placeholder text on the area |

### Events

No `dl:*` event, and no `change` event is fired by the script (it assigns `textarea.value` directly). The area fires native `input` events (bubbling) when the user types or uses the toolbar through the browser.

### JavaScript

`el.dlComponent` on the `.dl-rich` element is the `Rich` instance: `area`, `val` (the textarea), `sync()`. To set the content from code, set both sides:

```js
const r = document.querySelector('[data-dl-rich]').dlComponent;
r.area.innerHTML = sanitisedHtml; r.sync();
```

## Rules and pitfalls

- Sanitise on the server with an allow-list (for example only `b`, `i`, `u`, `ul`, `ol`, `li`, `br`, `p`, `div`, `strong`, `em`, no attributes, no `script`, no `on*`, no `javascript:` URLs), for example with HTMLPurifier or a similar library, before saving and again before showing the text to anybody else. A stored `<img onerror=...>` is a classic stored XSS.
- `value` is written raw into the page. If you pass unsanitised data, the edit page itself runs it. Sanitise on the way in and pass only clean HTML; escape (`e($plain)`) when the source is plain text.
- Render the saved HTML with `{!! $clean !!}` only after sanitising; otherwise `{{ }}` shows the tags as text.
- Do not use `required` as the only emptiness check: browsers leave `<br>` behind. Strip tags and trim on the server (`trim(strip_tags($html)) !== ''`).
- Limit the length on the server (the HTML is longer than the visible text) and make the column type big enough.
- Pasting from Word or the web brings a lot of markup; the server allow-list must cope with it. Ask users to use "Clear formatting" if it looks odd.
- `execCommand` is deprecated; it still works in current browsers, but DevLib depends on the browser for the markup.
- `disabled` / `readonly` still post the value (unlike a disabled `x-dl::field.text`), and a locked form's area stays typable but is not posted.
- Not for large documents or collaborative editing.
- Code oddity (not fixed): (1) `resources/assets/js/components/rich.js` `sync()` writes the textarea without an event, and typing fires `input` on the unnamed `div`, so the dirty guard (`core.js` `markDirty`, needs `e.target.name`) never sees edits: editing only this field does not mark the form dirty (no leave warning, the form's Undo stays disabled). (2) The form's own "clear the error on input" handler (`form.js` line 23) runs in the capture phase before `sync()`, so it reads the old textarea value; a rich field marked "Required" is cleared one keystroke late. (3) `rich-text.blade.php` line 7 gives `disabled` no `disabled` on the textarea (value still posted) and a disabled fieldset leaves the area editable. (4) The form's Undo and `Devlib.fill()` set the textarea only, so the visible area keeps the edited text while the posted value is the restored one. (5) `value` is output with `{!! !!}` (line 7).

## Example

Basic (shape of the demo, with an initial value):

```blade
<x-dl::field.rich-text name="catatan" label="Notes for Supplier" placeholder="Write a note..." value="Please send <b>before the 30th</b>." />
```

Taller box, from the record (already sanitised), with the server error:

```blade
<x-dl::field.rich-text name="remarks" label="Remarks" :rows="8" :value="$po->remarks_clean" :error="$errors->first('remarks')"
    hint="Bold, italic, underline and lists only." />
```

Read-only display of a note on a locked document:

```blade
<x-dl::field.rich-text name="remarks" label="Remarks" :value="$po->remarks_clean" readonly />
```

Server side (allow-list sanitising, here with HTMLPurifier as one possible library):

```php
$clean = $purifier->purify($request->input('remarks'), ['HTML.Allowed' => 'b,i,u,strong,em,ul,ol,li,br,p,div']);
abort_if($required && trim(strip_tags($clean)) === '', 422);
```

## Related

- `x-dl::field.text`: full reference of the 14 inherited props; also the plain multi-line text field (`multiline`).
- `x-dl::form`: required check, dirty guard, Undo that read this value.
- `x-dl::field.display`: read-only text for showing plain values.
- `x-dl::field.upload` and `x-dl::field.signature`: the other non-text controls of the Form & fields family.
- `skills/devlib/form.md`: project rules for forms.
