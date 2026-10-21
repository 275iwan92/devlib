## Overview

`<x-dl::field.upload>` is a drag-and-drop / click-to-choose file box. The user drops files on the dashed box or clicks it; the chosen files are listed under the box with an optional thumbnail, the size in KB and a remove button. It wraps a real `<input type="file">`, so the files are posted by the browser as an ordinary multipart upload.

It extends the abstract `Devlib\Components\Field\Field` (label row, error line, hint line, 14 inherited props) and adds three own props: `accept`, `multiple`, `preview`. The behaviour is in the JS class `Upload` (`resources/assets/js/components/upload.js`, selector `[data-dl-upload]`).

### What it is not

- Not an uploader: nothing is sent until the form is submitted. DevLib does no storing, no virus scan, no resizing and no progress bar.
- Not a validator: `accept` is only a hint to the file dialog. There is no client-side size, type or count limit. The server decides.
- Not a gallery or file manager: the box shows the files of this field only, plus (optionally) one already-saved file.

## Props

Own props (Blade names are kebab-case; use `:prop="expr"` for anything that is not a plain string):

| Prop | Type, default | Meaning |
|---|---|---|
| `accept` | `?string`, `null` | Written to the `accept` attribute of the file input (`image/*`, `application/pdf`, `.xlsx,.csv`). It filters the file dialog only. Files dropped on the box are not checked against it (`take()` never reads it). |
| `multiple` | `bool`, `false` | Adds `multiple` to the input and makes the posted name an array: `name="foto[]"`. Chosen files are appended to the list (a second selection adds to the first); remove with the button on each row. Single mode keeps only the first file of each selection and replaces the previous one. |
| `preview` | `bool`, `true` | Adds `data-preview` to the root. New image files (`type` starts with `image/`) show a 36px thumbnail made with `URL.createObjectURL`; other files show a document icon. For an already-saved file (`value`) it renders `<img src=url>` as the thumbnail whatever the type: use `:preview="false"` when the saved file is not an image. |

### Inherited props

All 14 props come from `Field` (full reference: `x-dl::field.text`). Where this control differs:

| Prop | Effect on `upload` |
|---|---|
| `name` | Name of the file input (`name[]` when `multiple`). Without a name nothing is posted. Also `data-dl-field` and the id part. |
| `label` | Label above the box (the label's `for` is the file input). `''` removes the label row, the `*` and the tooltip. Translated. |
| `tooltip` | (i) bubble beside the label, only with a non-empty `label`. |
| `hint` | Help line under the box, rendered whenever not `null`. Good place to state the limits (type, size) that the server enforces. |
| `error` | Server error under the box (`:error="$errors->first('foto')"`); non-empty adds `is-invalid`. Not translated. |
| `value` | An already-saved file: `['url' => ..., 'name' => ...]` (see Behavior). Anything that is not an array with a non-empty `url` is ignored (a plain string shows nothing). |
| `placeholder` | Unused: the text inside the box is fixed ("Drag a file / Choose File"). |
| `required` | Red `*`, and `x-dl::form` Save/Confirm marks the field "Required" when no new file is chosen. See pitfalls for the saved-file case. |
| `disabled` | Disables the file input; a disabled input is not submitted, and click and drop do nothing. |
| `readonly` | Same as `disabled` for this control (a file input has no read-only state): the input is disabled, so nothing is submitted. The root also gets `is-readonly`. |
| `icon` | Unused. |
| `size` | `sm` (default) or `lg`: only the CSS class `dl-field--sm` / `dl-field--lg`. |
| `i18n` | `data-i18n` on the label (auth screens only). |
| `source` | Catalog text for the data origin; no visual effect. |

Attributes accepted on every DevLib object: `can="module.action"` (no access removes the field) and any HTML attribute (`id`, `class`, `style`, `data-*`), merged onto the root `div.dl-field`, not onto the input.

## Behavior

### Markup

```html
<div class="dl-field dl-field--upload dl-field--sm" data-dl-field="foto">
  <div class="dl-field__labelrow"><label class="dl-field__label" for="dlf-foto-...">Foto</label></div>
  <div class="dl-upload" data-dl-upload data-preview>
    <input id="dlf-foto-..." type="file" name="foto[]" class="dl-upload__input" accept="image/*" multiple>
    <div class="dl-upload__placeholder" data-dl-upload-empty>...Drag a file / Choose File (more than one allowed)</div>
    <div class="dl-upload__files" data-dl-upload-list>...rows...</div>
  </div>
  <div class="dl-field__error" data-dl-field-error></div>
</div>
```

The file input itself is `display:none`; the whole `.dl-upload` box is the click and drop target.

### Choosing, dropping, removing

- Click anywhere on the box (except on a row or a button) opens the file dialog. Clicks on a row, on the saved-file link or on a remove button do not.
- Drag over adds `is-drag` to the box; drop calls the same handler as the dialog. Dragging over a disabled box does nothing.
- Every change goes through one function: the JS keeps its own `files` array, redraws the list, then writes the array back into the input (`input.files = DataTransfer.files`). So the files in the input are always exactly the files in the list, and a removed file is not posted.
- Each row shows the thumbnail or icon, the name (escaped), the size in KB (rounded, at least 1) and a remove button (title "Remove").
- With no files, `.has-file` is absent and the "Drag a file" text shows. With files the text stays visible (dimmed) above the list.

### Saved file (`value`)

For edit screens, pass the file that is already stored:

```blade
<x-dl::field.upload name="po_file" label="PO attachment" accept="application/pdf" :preview="false"
    :value="$po->file ? ['url' => Storage::url($po->file), 'name' => basename($po->file)] : null" />
```

It renders one row marked `data-dl-upload-saved` with a link (`target="_blank" rel="noopener"`), the name (`name`, default `basename(url)`) and the word "saved". The box gets `.has-file` from the start. As soon as the user chooses or drops a file, the list is redrawn and the saved row disappears (the new file replaces the old one in the user's eyes; the server must actually replace the stored file). There is no button to delete a saved file.

### Locked states

The JS reads `input.disabled` live on every click and drop. So the box is locked by `disabled` / `readonly` and also when a surrounding `fieldset` is disabled (a locked `x-dl::form`, `mode="view"`): the box turns dim with a solid border and ignores click and drop.

### Accessibility

As implemented: the label's `for` is the file input, but the input is `display:none` and the box has no `tabindex`, no `role` and no key handler, so the picker cannot be reached with the keyboard alone. The remove button has only a `title` ("Remove") and the glyph. If keyboard use matters, offer another way to attach the file.

## Data and events

### What the server receives

The browser submits the files as `multipart/form-data` (`$_FILES`). In Laravel: `$request->file('po_file')` (single) or `$request->file('foto')` as an array (`multiple`). When no file is chosen the browser sends an empty file part and Laravel returns `null` for `$request->file()`. When the control is `disabled` / `readonly`, or the form is locked, nothing is sent for it at all.

### Form wiring: enctype

DevLib does not set the encoding for you. `x-dl::form` renders a plain `<form method="POST">` and merges extra HTML attributes into it, and no code anywhere adds `enctype`. Without `enctype="multipart/form-data"` the browser sends only the file names as text. Put it on the form object (it also works on `x-dl::action-form`):

```blade
<x-dl::form :action="route('po.save', $po)" method="POST" enctype="multipart/form-data" ...>
```

Method must be POST (a GET form cannot carry files). Form buttons that submit with `form.submit()` (Delete, Cancel, ...) keep the encoding, so they also send the chosen files.

### Interaction with the form object

- Required check (`x-dl::form`, Save/Confirm): uses the value of the named input. A file input has a value only when a file is in `input.files` (the JS assigns it), so a new file satisfies `required`.
- `Form.values()` (Undo snapshot), the QR extra button and `dl:form-submit` skip `File` entries. So a form without `action` (preview) will not report files.
- Dirty guard: the guard listens to `input`/`change` events on named controls. A file picked through the dialog fires `change` and marks the form dirty; a drop and a removal set the files from script and fire no `change`, so they do not (see the oddity note).

### Markup hooks

| Hook | Where |
|---|---|
| `data-dl-upload` | root `.dl-upload`; JS class `Upload` |
| `data-preview` | present when `preview` is true |
| `data-dl-upload-empty` | the "Drag a file" text |
| `data-dl-upload-list` | the list container |
| `data-dl-upload-saved` | the row of the saved file |
| classes | `has-file`, `is-drag` on `.dl-upload`; `dl-upload__file`, `dl-upload__thumb`, `dl-upload__name` for rows |

### Events

| Event | `detail` | When |
|---|---|---|
| `dl:upload` | `{files: File[]}` (a copy of the current list) | After every redraw: choose, drop, remove. Bubbles from `.dl-upload`; not cancelable. Fired by script, so no native `change` accompanies drop and remove. |

The native `change` of the input also fires (and bubbles) after a dialog selection.

### JavaScript

`el.dlComponent` on the `.dl-upload` element is the `Upload` instance: `files` (array of `File`), `file` (the input), `list`, `preview`, `take(fileListOrArray)` (add as if chosen) and `draw()`. Clearing in code: `box.dlComponent.files = []; box.dlComponent.draw();`.

## Rules and pitfalls

- Always put `enctype="multipart/form-data"` on the form. This is the most common reason a file "does not arrive".
- Validate on the server: type (`mimes` / `mimetypes`), size (`max`), count. `accept` is not a security control, and dropped files bypass it.
- Server limits apply before Laravel sees the file: PHP `upload_max_filesize`, `post_max_size`, `max_file_uploads`, and the web server body limit. When the body is over `post_max_size` PHP drops all input (including `_token`), so Laravel usually answers 419; state the limit in `hint`.
- Store the file outside the web root or on a private disk, generate your own file name, never trust the client name or the client MIME type.
- Show errors with `:error="$errors->first('foto')"` (for `multiple`, validate `foto.*` and show `$errors->first('foto.0')` or the array rule message). After a validation error the browser cannot refill a file input: the user must choose the file again. Do not rely on `old()` for files.
- A required upload on an edit screen that already has a saved file: the saved file is not in any input, so `required` still flags the field as empty. Leave `required` off in that case and enforce "a file must exist" on the server.
- The saved row only informs. If the user picks a file and then removes it, the box looks empty but the saved file is still stored (nothing is posted). To offer deletion, add your own checkbox or button.
- `preview` with a saved non-image: use `:preview="false"` or the thumbnail is a broken image.
- Single mode: a new selection replaces the list; multiple mode: selections accumulate, with no duplicate check.
- A disabled / read-only box posts nothing, unlike other read-only fields which post their value.
- A locked form posts no files either (fieldset disabled); a server action on a locked document must work from the stored record.
- Code oddity (not fixed): (1) `resources/assets/js/components/upload.js` `draw()` (line 34-36) writes `input.files` and dispatches only `dl:upload`; the dirty guard (`core.js` `markDirty`) listens to `input`/`change`, so dropping or removing a file does not mark the form dirty (no leave warning, Undo stays disabled), while the dialog path does. (2) `Devlib.fill()` / Undo (`fillForm`, `core.js` `setField`) never redraw the list: a file chosen after the form opened stays in the box after Undo, and `fill(form, data, true)` empties `input.files` while the list still shows the rows. (3) `required` ignores the saved file (see above). (4) `accept` is not applied to dropped files.

## Example

Single document (shape of the demo), inside a multipart form:

```blade
<x-dl::form :action="route('doc.save')" method="POST" enctype="multipart/form-data" title="Document">
    <x-dl::field.upload name="dokumen" label="Upload Document" hint="PDF, max 2 MB." :error="$errors->first('dokumen')" />
</x-dl::form>
```

Several images with thumbnails:

```blade
<x-dl::field.upload name="foto" label="Product Photos (many + preview)" accept="image/*" multiple />
```

Edit screen with the saved file, listening for changes:

```blade
<x-dl::field.upload name="po_file" label="PO attachment" accept="application/pdf" :preview="false"
    :value="$po->file ? ['url' => Storage::url($po->file), 'name' => basename($po->file)] : null" />
<script>
document.addEventListener('dl:upload', e => console.log(e.detail.files.map(f => f.name)));
</script>
```

Controller side:

```php
$request->validate(['dokumen' => ['nullable', 'file', 'mimes:pdf', 'max:2048']]);
$path = $request->file('dokumen')?->store('docs');
```

## Related

- `x-dl::field.text`: full reference of the 14 inherited props.
- `x-dl::form`: the shell; pass `enctype="multipart/form-data"` to it. Its dirty guard, Undo and required check are described in `x-dl::form`.
- `x-dl::action-form`: small form wrapper that also forwards `enctype`.
- `x-dl::field.signature`: draw a signature (posted as a PNG data URL instead of a file).
- `x-dl::field.display`: read-only text, for showing a file name without upload.
- `skills/devlib/form.md`: project rules for forms.
