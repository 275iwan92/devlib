## Overview

`<x-dl::action-form>` is a thin `<form>` wrapper with no title, no panel and no buttons of its own. It exists so that small actions (a Post / Cancel / Create button, fields inside a pop-up, a GET filter) are still written with DevLib objects and not with a raw `<form>` tag (lint rule `L001` reports raw `form`). It sets the HTTP method, the target, and adds the CSRF token for every method except GET. Everything else is passed straight to the `<form>`.

Use it for:

- one or a few action buttons that post to the server (`<x-dl::button type="submit">`);
- fields inside a modal, drawer or card that are not a full document;
- a GET filter or search form.

Do not use it for a full document or master record: use `x-dl::form` (status-aware footer, required check, dirty guard, `Undo`, numbers posted canonical in the form scripts). `x-dl::action-form` has none of that.

### Generated markup

```html
<form data-dl="x-dl::action-form" data-dl-class="ActionForm" data-dl-src="—" method="post" action="/route">
  <input type="hidden" name="_token" value="...">   <!-- only when not GET -->
  ...slot...
</form>
```

Every other attribute you write on the tag (`id`, `class`, `enctype`, `data-dl-guard`, `data-dl-autosubmit`, `hidden`, `style`, `onsubmit` ...) is written as-is on the `<form>`. `can="module.action"` hides the whole form when the ability is missing.

## Props

Constructor of `Devlib\Components\ActionForm` (2 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `method` | `string`, `'post'` | Compared case-insensitively. `get` renders `method="get"` and no CSRF token. Any other value (including `put`, `patch`, `delete`) renders `method="post"` and the `@csrf` hidden input. HTML forms cannot send other verbs: for those, put `@method('PUT')` inside the slot. |
| `action` | `?string`, `null` | The `action` URL. `null` writes no `action` attribute, so the form posts to the current page URL. Use `{{ route('name') }}` or a string. For a GET filter the query string of the URL is replaced by the form fields (browser behaviour). |

## Behavior

- The form carries no DevLib class and no styling: layout comes from the objects inside (`x-dl::field.*`, `x-dl::action-bar`, `x-dl::button`, `x-dl::card`).
- No `novalidate` is written, so native browser validation applies to fields that have HTML attributes (`maxlength`, `type="email"`). DevLib fields do not write HTML `required`, so nothing blocks submit by default; validate on the server.
- No client script is attached to the form. These global behaviours apply to any form, including this one:
  - **Submit lock**: after a (not prevented) submit, the pressed submit button is disabled and shows "Processing" (`core.js`). Add `data-dl-no-busy` on a button to opt out.
  - **Canonical numbers**: on `formdata`, inputs with `data-dl-num` (the number/decimal/money fields) are posted as `1234.5`, not in the display format.
  - **Auto submit**: the attribute `data-dl-autosubmit` on the form submits it on every `change` (for example a pattern picker).
  - **Dirty guard**: with `data-dl-guard` on the form, a change to a named input marks the form dirty, and a click on a link, on a Back button or on a `data-dl-leave` element asks "Save / Don't Save / Cancel" first. "Save" clicks the `[data-dl-save]` or `[data-dl-preset=save]` button (or requests a submit). The mark is cleared when the form is submitted.
- Placing the form: it is a block-level `<form>`. A form wrapped around only a button inside a row (as in a header row of buttons) needs the row's own layout rules; `x-dl::action-bar` can be inside the form or the form inside a bar item.
- Forms cannot be nested in HTML. Do not put an `x-dl::action-form` inside another form or inside `x-dl::form`; the browser drops the inner tag. Place several action forms side by side instead.
- Language and text: the form has no text. It belongs to the `form` dictionary group.
- Accessibility: a plain `<form>`; no landmark role unless you add `aria-label` as an attribute.

## Data and events

- What is posted: every named, non-disabled control inside the form, plus `_token` for non-GET. Nothing is added by DevLib besides the token.
- A GET form posts nothing but the query string (no token).
- Events: native `submit` (bubbles to `document`). DevLib adds no `dl:*` event and no `dlComponent`.
- Method spoofing: not supported by the object (see pitfalls).
- Hooks and attributes the global scripts understand on the tag: `data-dl-guard`, `data-dl-autosubmit`; inside: `data-dl-save`, `data-dl-no-busy`, `data-dl-num`.

## Rules and pitfalls

- Never write a raw `<form>` in a screen view (lint `L001`); use this object for small forms and `x-dl::form` for documents and master records.
- `method` only chooses between GET and POST. `method="delete"` does NOT send a DELETE: the form is posted as `post` with no `_method` field. Add `@method('DELETE')` in the slot and a matching route.
- The CSRF token is automatic for non-GET. Do not add `@csrf` yourself (it would duplicate the token input); a GET form must not contain a token.
- Without `action` the form posts to the current URL, which is rarely right. Always pass the route.
- Do not nest forms (an `x-dl::action-form` inside `x-dl::form`, or inside another action form).
- The form does not check required fields and does not show errors. Pass server errors to the fields with `:error="$errors->first('x')"`.
- `data-dl-guard` only counts inputs that have a `name`; the search box of a dropdown or grid does not make the form dirty.
- A submit button in the form turns into "Processing" and stays locked. If the response does not replace the page (a file download), set `data-dl-no-busy` on that button.
- A GET filter form usually has no submit button of DevLib's preset kind: use `<x-dl::button type="submit" label="Search" />` or `data-dl-autosubmit`.

## Example

Action buttons that post (shape of the history screen):

```blade
<x-dl::action-bar>
    <x-dl::action-form action="{{ route('history.toggle', [$note->id_note, 'pin']) }}">
        <x-dl::button variant="secondary" type="submit" :label="$note->pinned ? 'Unpin' : 'Pin'" />
    </x-dl::action-form>
    <x-dl::action-form action="{{ route('history.toggle', [$note->id_note, 'read']) }}">
        <x-dl::button variant="secondary" type="submit" :label="$note->read_at ? 'Mark Unread' : 'Mark Read'" />
    </x-dl::action-form>
</x-dl::action-bar>
```

A small data-entry form with the dirty guard:

```blade
<x-dl::action-form action="{{ route('history.save') }}" data-dl-guard>
    <x-dl::card title="New Note" icon="fa-clock-rotate-left">
        <x-dl::field.text name="title" label="Title" required :value="old('title', '')" />
        <x-dl::field.text name="body" label="Note" multiline :value="old('body', '')" />
        <x-dl::action-bar>
            <x-dl::button variant="secondary" icon="back" label="Back" :href="$home" />
            <x-dl::button preset="save" type="submit" />
        </x-dl::action-bar>
    </x-dl::card>
</x-dl::action-form>
```

GET filter:

```blade
<x-dl::action-form method="get" action="{{ $home }}">
    <x-dl::action-bar align="start">
        <x-dl::button type="submit" icon="search" label="Search" />
    </x-dl::action-bar>
</x-dl::action-form>
```

## Related

- `x-dl::form`: the full document / master form (use it instead for records with status).
- `x-dl::button`, `x-dl::action-bar`: the submit button and the row that holds it.
- `x-dl::card`, `x-dl::field.text` and the other `x-dl::field.*` objects: the usual content.
- `x-dl::modal`, `x-dl::drawer`: pop-ups that often hold an action form.
- `skills/devlib/form.md`: when to use `x-dl::form` and not a small form.
