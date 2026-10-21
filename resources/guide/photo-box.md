## Overview

`<x-dl::photo-box>` is a clickable passport-style photo box (default ratio 3 : 4). A click opens a dialog (`x-dl::modal`) where the user chooses an image file, drags it inside a fixed frame, zooms it with a slider, and presses Use Photo. The result is cropped in the browser to a JPEG (600 x 800 px by default) and put into a hidden `<input type="file">` named `name`, which is posted with the surrounding form when the form is saved. The object reads no database and stores nothing: the screen shows the current photo through `photo` and the server saves the posted file.

Use it for one portrait photo of a person (employee, member, contact) inside a form. Do NOT use it for a general file attachment or several files (`x-dl::field.upload`), for a read-only picture or avatar with initials (`x-dl::avatar`), or when no cropping is needed.

### Markup

```html
<div class="dl-photobox dl-photobox--md [has-photo]" data-dl-photobox data-crop-id="dl-photobox-xxxxxx" data-out-w="600" data-out-h="800" data-photo="URL">
  <button type="button" class="dl-photobox__pic" style="aspect-ratio: 600 / 800" data-dl-open="dl-photobox-xxxxxx" data-pb-pic title="Profile photo">
    <img alt="" data-pb-img src="URL">
    <span class="dl-photobox__ini">BS</span>
    <span class="dl-photobox__cap dl-photobox__cap--add">Click to add photo</span>
    <span class="dl-photobox__cap dl-photobox__cap--change">Change photo</span>
  </button>
  <input type="file" name="photo" accept="image/*" hidden data-pb-result>
  <!-- x-dl::modal with the canvas, zoom slider, hint, message and action bar -->
</div>
```

The modal is rendered inside the root (so inside the form), but its controls have no `name`, so only `data-pb-result` is posted. Extra HTML attributes and classes go on the root `div`; `can="module.action"` hides the object without the ability.

## Props

Constructor of `Devlib\Components\PhotoBox` (7 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `name` | `string`, `'photo'` | `name` of the hidden file input that carries the cropped image. Use a distinct name per box (two boxes with the same name collide). Also seeds the modal id (`dl-photobox-<6 hex>` from the name and the time, so it differs on every render). |
| `photo` | `?string`, `null` | URL of the existing photo. When set: the root gets `has-photo`, `data-photo`, and the `<img src>`, and the box shows the picture with the caption "Change photo". When the user opens the dialog for the first time the same URL is loaded into the crop canvas. Empty or `null` = no picture (initials are shown). Escaped; not translated. |
| `label` | `string`, `''` | Name of the person. Used ONLY to build the initials shown when there is no photo: first letter of the first word plus first letter of the second word, upper-cased (`Budi Santoso` becomes `BS`, one word gives one letter); an empty label shows `＋`. It is a standard text prop, so it goes through the dictionary: wrap a real name in `raw()`. |
| `caption` | `?string`, `null` | Text of the caption bar shown when there is NO photo (default `Click to add photo`). It does not change the `Change photo` caption shown when a photo exists. Translated. |
| `out-w` | `int`, `600` | Width in pixels of the cropped JPEG. Together with `out-h` it sets the frame ratio: the box CSS `aspect-ratio`, the crop canvas height (`round(240 * out-h / out-w)`, canvas width is fixed at 240), and `data-out-w`. Must be greater than 0 (0 causes a division by zero in PHP). |
| `out-h` | `int`, `800` | Height in pixels of the cropped JPEG (see `out-w`). The 3 : 4 ratio is the default only; any ratio can be set, but the hint text in the dialog is always the 3 : 4 sentence (see Rules and pitfalls). |
| `size` | `string`, `'md'` | Display size of the box: `sm` (width 90px), `md` (120px) or `lg` (150px); the height follows the ratio. Written as the class `dl-photobox--<size>`; any other string is not validated and has no style, which falls back to the 120px default. |

## Behavior

### States of the box

- No photo: tinted box with the initials and the bottom caption bar `Click to add photo` (or `caption`).
- Photo present (from `photo` or after Use Photo): the picture fills the box (`object-fit: cover`), the initials are hidden, the bar says `Change photo`.
- The whole box is one `<button>`; a click, Enter or Space opens the dialog (`data-dl-open`).

### Dialog

1. A click on the box opens the modal titled `Profile photo` (camera icon). If an existing `photo` URL is known and nothing has been loaded yet, that photo is loaded into the canvas. Otherwise the frame shows `Choose a photo to start.`.
2. `Choose File` opens the browser file picker (a second hidden input without a name, `accept="image/*"`). On devices with a camera the browser may offer the camera; DevLib does not call `getUserMedia` itself.
3. The chosen file is read locally with `URL.createObjectURL` and drawn in a 240px-wide canvas. The image is scaled to cover the frame (`cover` scale); the user drags it with the mouse or a finger (pointer events with capture, `touch-action: none`) and zooms with the slider from 1x to 3x (step 0.01). Zoom keeps the point at the centre of the frame fixed. The photo can never leave an empty edge: the position is clamped so the image always covers the whole frame. A 3x3 guide grid is drawn over the frame.
4. `Use Photo` renders the visible area at `out-w` x `out-h` from the original image (not from the 240px preview, so no quality is lost), encodes JPEG with quality 0.9 as `profile.jpg`, puts the file into the hidden input (`DataTransfer`), shows it in the box, adds `has-photo`, dispatches a bubbling `change` on the hidden input, and closes the dialog. Without a chosen image the message `Choose a photo first.` appears in the dialog.
5. `Cancel`, the close button, Esc and a click outside the dialog close the modal and keep the previous state.

Nothing is uploaded when the dialog closes: the file is posted only when the form is submitted.

### Dirty state, locking and Undo

- The bubbling `change` event makes the surrounding `x-dl::form` mark itself unsaved (dirty guard: the named hidden input is the target), like any other field.
- Inside a locked form (view mode, locked status) the form disables its fieldsets, so a box placed in a fieldset (default slot, tabs, `strip`) has a disabled button and the dialog cannot be opened.
- The object has no reset hook. The `Undo` of `x-dl::form` (`Devlib.fill`) does not restore the picture in the box.

### Text, language, accessibility

- Translated at render (dictionary): `Profile photo`, `Click to add photo` (or `caption`), `Change photo`, `Choose a photo to start.`, `Zoom`, the hint sentence, the `label`, and the modal/button texts. The built-in dictionary only contains `Change photo` and `Choose File`; the other texts are English unless the application dictionary has them.
- The message `Choose a photo first.` is written in the script and is not translated.
- The box is a real `<button>` with `title="Profile photo"`; the `<img>` has `alt=""` (decorative) so the name is not exposed to screen readers other than through the title. The modal has `role="dialog"`, `aria-modal="true"` and an `aria-label` from its title.
- Dragging the image needs a pointer; there is no keyboard way to move the image inside the frame (the zoom slider is keyboard operable). A user without a pointer can still press Choose File and Use Photo (the image is then centred at the cover scale).

## Data and events

### What the server receives

| Case | Posted |
|---|---|
| Photo chosen and Use Photo pressed | multipart file field `name`, a JPEG named `profile.jpg` of the requested size. |
| Nothing changed | the file field with no file (PHP/Laravel: `hasFile` is false). Keep the stored photo. |
| `can` denies | the whole object is not rendered, nothing is posted. |

The form must be sent as multipart: add `enctype="multipart/form-data"` to `x-dl::form` (it is merged into the `<form>`); `x-dl::form` does not add it. Validate on the server (`image`, size limit, dimensions) and save the file; then pass the saved URL back through `photo`.

### Events and JS

- `change` (bubbles) on the hidden input `[data-pb-result]` after Use Photo. No `dl:*` event.
- `el.dlComponent` on the root is the `PhotoBox` instance (`out`, `result`, `img`, `layer`, and the methods `load(src)`, `draw()`, `use()`). The modal layer is looked up by `data-crop-id`; if it is not found the script stops in `init()` and the box does nothing.

| Hook | Where |
|---|---|
| `data-dl-photobox`, `data-crop-id`, `data-out-w`, `data-out-h`, `data-photo` | root. |
| `data-pb-pic`, `data-pb-img` | the box button and its image. |
| `data-pb-result` | the hidden file input that is posted. |
| `data-pb-canvas`, `data-pb-zoom`, `data-pb-empty`, `data-pb-msg` | canvas, zoom slider, empty text and message line in the dialog. |
| `data-pb-pick`, `data-pb-choose`, `data-pb-use` | file picker input and the two dialog buttons. |

## Rules and pitfalls

- Always wrap the `form` with `enctype="multipart/form-data"`; without it the browser posts only the file name.
- The file is created in the browser; never trust it. The server must validate the type, size and dimensions and enforce permissions before saving it.
- Use a distinct `name` per box. Put the box in a `x-dl::form` (or any element with `data-dl-guard`) so the dirty guard works; outside a form nothing is posted.
- Wrap real names in `raw()` for `label` (`label` is a translated prop): `<x-dl::photo-box :label="raw($employee->name)" />`.
- A `photo` URL from another origin needs CORS headers: the script loads it without `crossOrigin`, so the browser treats the canvas as tainted and `Use Photo` would fail. Serve the photo from the same origin as the page (a signed route is fine).
- A transparent PNG becomes black behind the image in the JPEG; the image always covers the frame so this only shows with transparent source pixels.
- A file that is not a decodable image (a renamed text file, a broken file) gives no message: the script has no `onerror`, so the dialog just stays empty. `accept="image/*"` is the only filter; there is no size limit.
- Lint: no own rule. L001 applies to the Blade of the screen (do not hand-write `<input>`, `<button>`, `<form>`), L002 to inline `<script>`, L003 to hex colours.
- Code oddity (not fixed): the hint in the dialog is the fixed sentence "The result is 600 x 800 px, ratio 3 : 4 (about 3 x 4 cm when printed)" in `src/Components/PhotoBox.php`, whatever `out-w` and `out-h` are.
- Code oddity (not fixed): `Choose a photo first.` in `resources/assets/js/components/photo-box.js` is a hard-coded English string, outside the dictionary.
- Code oddity (not fixed): the demo `site/resources/views/demos/photo-box.blade.php` sets `caption="Change photo"` on a box without a photo, so the empty box says "Change photo" instead of an add text; `caption` only replaces the add caption.
- Code oddity (not fixed): the file header of `src/Components/PhotoBox.php` (REMARK, `source()`) names a project screen and a project table column; the neutrality rule asks for no project names in the library.

## Example

Copied in shape from the catalog demo (fictional names), and a use inside a form with multipart posting.

```blade
<div class="dl-row">
    <x-dl::photo-box name="demo_photo" label="Budi Santoso" />
    <x-dl::photo-box name="demo_photo_large" label="Siti Rahma" size="lg" caption="Add photo" />
</div>
```

```blade
<x-dl::form title="Employee" :actions="['Save' => 'fa-floppy-disk']" enctype="multipart/form-data" style="height:auto">
    <x-dl::photo-box name="photo" :label="raw($employee->name)" :photo="$employee->photo_url" />
    <x-dl::field.text name="name" label="Full Name" :value="old('name', $employee->name)" required />
</x-dl::form>
```

## Related

- `x-dl::profile-banner`: shows a photo box in its `photo` slot next to the name, chips and KPI tiles.
- `x-dl::modal`: the dialog the photo box is built on; `x-dl::button` and `x-dl::action-bar` hold its buttons.
- `x-dl::avatar`: read-only picture or initials without cropping.
- `x-dl::field.upload`: general file upload (any type, several files).
- `x-dl::form`: the form that receives the file and tracks the unsaved state.
- `skills/devlib/components.md`: generated prop list.
