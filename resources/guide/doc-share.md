## Overview

`<x-dl::doc-share>` is the **Print and Email control** for any form or document: one tag next to the document gives a `Print` menu (PDF, JPEG, HTML online with link + QR, Printer) and an `Email` button that opens a mail dialog (To / CC / BCC / Subject / Message, contact finder, what to include). The screen only places the tag and points `target` at the area that is the document; the object turns that area into files in the browser.

Output depends on what sits in `target`:

- A `x-dl::print-doc` (its sheets carry `data-pd-sheet`): each sheet is rasterised at its exact paper size (A5 landscape or A4 portrait, one PDF page per sheet), and the QR of the online copy is stamped into each sheet's QR box.
- Any other area (a card, a form, a `x-dl::print-sheet`, a table): the area is rendered as one image with a small header (title, number, print time, QR) and written to an A4 PDF (portrait or landscape) by slicing the image into pages.

With an **endpoint** (an application route that stores snapshots) the object also makes the HTML online copy (public link + QR, opened without login) and sends e-mail. Without an endpoint (the DevLib catalog site) PDF, JPEG and Printer still work; online and Email are not shown.

### Use it, or use another object

| Need | Object |
|---|---|
| Print / PDF / JPEG / link / Email for a document or form | `x-dl::doc-share` |
| The paper layout of a transactional document | `x-dl::print-doc` (share it with this object) |
| Nota or report with its own preview bar and Print button | `x-dl::print-out` (does not need this object) |
| Only a browser print dialog for an ordinary page | not covered; use the browser |
| A plain "Print" link on a form to a separate page | the Print button of `x-dl::form` (`print-url`) |

### Where it sits in the flow

`x-dl::form` has Print (new tab) and Email buttons driven by `print-url`; Email appends `ds_do=email`. The page behind that URL holds `x-dl::print-doc` and one `x-dl::doc-share`; when the URL has `ds_do=email` (and no `print_lang`) the mail dialog opens immediately (this needs an endpoint, because the dialog only exists with one).

### Markup

```html
<span class="dl-docshare" data-dl="x-dl::doc-share" ... data-dl-docshare data-target="#print-doc" data-type="delivery-note" data-id="12" data-no="10262874"
      data-title="Delivery Note" data-endpoint="/app/doc-share" data-csrf="..." data-orientation="portrait" data-print-themes='{"dark-code":"light-code"}'
      data-mail="docshare-ab12cd34-mail" data-online="docshare-ab12cd34-online">
  <span class="dl-docshare__menu">
    <button class="dl-btn" data-ds-toggle>Print <i class="fa-caret-down"></i></button>
    <span class="dl-docshare__list" role="menu">
      <span class="dl-docshare__lang" data-ds-langs> Print language: <button data-ds-lang="id">ID</button> <button data-ds-lang="en">EN</button> </span>   <!-- only when more than 1 language -->
      <button role="menuitem" data-ds-do="pdf">PDF</button> <button role="menuitem" data-ds-do="jpeg">JPEG</button>
      <button role="menuitem" data-ds-do="online">HTML online</button>    <!-- only with endpoint -->
      <button role="menuitem" data-ds-do="printer">Printer</button>
    </span>
  </span>
  <button class="dl-btn" data-ds-do="email">Email</button>               <!-- only with email and endpoint -->
</span>
```

With an endpoint two `x-dl::modal` dialogs (`<uid>-online`, and `<uid>-mail` when `email` is true) are rendered inside the tag and **moved to `<body>`** when the script starts, so the tag can sit inside a `<form>` without the dialog fields being posted. Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `span`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\DocShare`: **14 props**. Use `:prop="expr"` for anything that is not a plain string (`:doc-id="$id"`, `:email="false"`, `:contacts="[...]"`). The class also has a public `uid` (`docshare-` + 8 hex digits from type, doc-id, target and the object id); it is not a prop and gives the ids of the two dialogs.

### What to share

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `target` | `string`, `''` | CSS selector of the document area (usually the `id` of the `x-dl::print-doc`, for example `#print-doc`). Looked up when a menu item is used, not at render. Empty = the nearest `form`, `.dl-card` or `main` that contains the tag. A selector that matches nothing shows the error toast "Document area not found: <selector>". When the area is frozen (snapshot and generic image) these are left out: `script`, `template`, `button` / `.dl-btn`, the share control itself, modal layers (`.dl-mov`), file inputs, `.dl-field__trigger` buttons, `.dl-tip` tooltips and anything marked `data-dl-noprint`. Print-doc sheets are rasterised as they are. |
| `type` | `string`, `'doc'` | Document kind key. Sent to the endpoint as `doc_type`, stored in `data-type`, and used as the key of the remembered print language (`localStorage dl-print-lang:<type>`) and to find the re-rendered page in another print language. Use one distinct value per document kind and per tag on a page. Independent from `Document::$type` of the print-doc, though the same word is a good choice. |
| `doc-id` | `int` or `string` or `null`, `null` | Record id, sent as `doc_id` when not empty. Lets the application link the online copy to the record. |
| `number` | `?string`, `null` | Document number: sent as `doc_no`, part of the file name (`<title>_<number>`), of the JPEG header and of the default e-mail subject. |
| `title` | `string`, `'Document'` | Document title (translated through the dictionary): file name, JPEG header, mail subject, title of the online copy. |

### E-mail dialog

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `email` | `bool`, `true` | Show the `Email` button and render the mail dialog. Needs an endpoint: without one nothing e-mail related is rendered, whatever this prop says. `:email="false"` keeps Print and HTML online only. |
| `email-to` | `?string`, `null` | Initial content of the `To` box (comma separated addresses), usually the customer's address. |
| `subject` | `?string`, `null` | Initial subject. Default `title` + a space + `number`. |
| `message` | `?string`, `null` | Initial message text (translated like a standard text prop). |
| `contacts` | `array`, `[]` | Static address book for the "Find contact" box: `[['name' => ..., 'email' => ..., 'group' => ...], ...]` (`group` optional). Filtered locally as the user types. |
| `contacts-url` | `?string`, `null` | URL for server search. Default `config('devlib.mail_contacts_url')`. The script calls `GET <url>?q=<text>` (after at least 2 letters, 250 ms debounce, same-origin cookies) and expects JSON `{"rows": [{"name", "email", "group"}]}`. The Find-contact box exists when `contacts` is not empty or a `contacts-url` is set; at most 20 results are shown, static and server results merged without duplicate addresses. |

### Storage and output

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `endpoint` | `?string`, `null` | Application URL that stores snapshots and sends mail (contract below). Default `config('devlib.doc_share_url')`. Not set: only PDF, JPEG and Printer are offered. Note that an explicit empty string is also "no endpoint" (the default is applied only to `null`). |
| `orientation` | `string`, `'portrait'` | `'landscape'` makes the A4 PDF and the printer page of a generic area landscape (any other value is portrait). Print-doc sheets ignore it and use their own paper. Use it for wide areas such as a landscape `x-dl::print-sheet`. |
| `size` | `string`, `'md'` | Size of the Print and Email buttons (the `x-dl::button` sizes `sm`, `md` and `lg`). Use `sm` inside dense toolbars. |

## Behavior

### Menu and flow

- `Print` is a split-style menu: PDF, JPEG, HTML online (endpoint only), Printer. The caret button toggles `is-open`; a click anywhere outside closes it. Choosing an item closes it and runs `run(what)`.
- While a job runs the control gets `is-busy` (dimmed) and its buttons are disabled; every error becomes a toast ("Print failed" / "Email failed" titles, message from the server or the exception).
- `run(what)` for pdf, jpeg, online, printer: (1) resolve the document in the chosen **print language** (see below); (2) with an endpoint, send the snapshot and receive `url` + `token`; (3) `online` opens the link dialog (link box, QR, Copy link, Open); otherwise render and deliver the file. For `email` the dialog simply opens; the snapshot is made when Send is pressed.

### Output formats

| Item | Print-doc sheets | Other area |
|---|---|---|
| JPEG | One download per sheet (`<name>-1.jpg`, `<name>-2.jpg`, ...), rendered at 3x pixel ratio. | One image (2x), light theme, with a header (title, number, print time, QR when an endpoint answered). |
| PDF | One page per sheet: A5 landscape for A5 sheets, A4 portrait for A4 sheets. | A4 with 8 mm margins, portrait or landscape by `orientation`; a tall image is shifted up page by page and the margins are covered with white. |
| Printer | A hidden frame with the sheet images and `@page` size of the first sheet, then the browser print dialog. | A hidden frame with the image, `@page A4` portrait or landscape, 8 mm margin, then the print dialog. |

The file name is `<title>_<number>` with every character except letters, digits, `.`, `_` and `-` replaced by `_` (the endpoint can override it with a `file` field). For the generic image route, dark and black themes are switched to the light theme of the same colour while the image is made, so the paper is always light (print-doc sheets use fixed light colours).

### Print language

When the application has more than one language, a `Print language` row (one button per language) appears at the top of the Print menu and in the mail dialog. The choice is remembered per `type` in `localStorage`, default = the screen language. If it differs from the screen language, the same page is loaded again in a hidden frame with `?print_lang=<code>` (the server then renders labels, numbers, dates and month names in that language), the matching `x-dl::doc-share` (same `type`) in that frame does the job, and the data are the **stored** ones: the screen shows the warning "Unsaved changes are not printed" if a dirty form contains the target (or lies inside it). Loading the frame has a 20 s limit (error "Print page (xx) did not load").

### E-mail dialog

Fields: To (required), CC, BCC, Subject (required), Message, and three checkboxes: online document link with QR (default on), document shown as an image in the mail body (default on), PDF attachment (default off). The contact finder fills the box that had focus last (To by default); clicking a contact that is already used removes it from all three boxes. Send checks that `To` is not empty (toast "Enter at least one To address."), makes the snapshot, builds the image / PDF when asked and posts everything; the success message comes from the server. Subject is only marked required; the server must check it.

### Libraries

PDF, image and QR are made in the browser by html-to-image 1.11.11, jsPDF 2.5.1 and qrcodejs 1.0.0, loaded **on demand from cdnjs.cloudflare.com**. A page without internet access or with a strict Content-Security-Policy cannot run PDF / JPEG / Printer / QR.

### Accessibility as implemented

The list has `role="menu"` and the items `role="menuitem"`; the toggle is a normal button without `aria-haspopup` / `aria-expanded`; there is no Escape or arrow-key handling (only outside click closes the menu). The dialogs are `x-dl::modal`. Language buttons carry the language name in `title`. Results are announced only by the toast.

## Data and events

### Endpoint contract

The package ships **no route or controller** for the endpoint; the application implements it (the catalog site has none). Requests are `multipart/form-data` with header `X-CSRF-TOKEN` (token written into `data-csrf`, so the page needs a session), `Accept: application/json` and same-origin cookies.

| Request | Fields | Expected JSON answer |
|---|---|---|
| `POST <endpoint>` (snapshot) | `doc_type` (`type`), `doc_id` (when set), `doc_no` (when set), `title`, `html` (outer HTML of the frozen document area) | `url` (public link opened without login), `token`, optional `file` (file name base). The same content may return the same token. |
| `POST <endpoint>/<token>/email` | `to`, `cc`, `bcc`, `subject`, `message`, `with_link` (1 or 0), optional `image` (file `document.jpg`) and `pdf` (file `<name>.pdf`) | `message` (shown as a success toast titled "Email"). |
| `GET <contacts-url>?q=<text>` | none | `{"rows": [{"name", "email", "group"}]}` |

A non-2xx answer is shown with `message` or the first entry of `errors` (Laravel validation shape), else "Failed (status)". The "frozen" HTML is the area with input values written as attributes, canvases as images and buttons / controls removed; the application page that serves the link renders it inside its own layout (the stylesheet has `dl-share-page*` classes for such a page).

### Events and JS

- No custom event is emitted. `el.dlComponent` (and `el.dlDocShare`) on the root is the `DocShare` instance: `run('pdf' or 'jpeg' or 'online' or 'printer' or 'email')`, `snapshot()`, `source()`, `sheets()`, `freeze(el)`, `fileName()`, plus `lang` and `share` (the last endpoint answer). Screens may call `run()` from their own button (a screen of the catalog's mockup package calls `run('email')` while the share menu is hidden).
- Markup hooks: `data-dl-docshare`, `data-ds-toggle`, `data-ds-do` (`pdf`, `jpeg`, `online`, `printer`, `email`), `data-ds-lang` / `data-ds-langs`, dialogs `data-ds-mailform`, `data-ds-send`, `data-ds-contacts`, `data-ds-contact-list`, `data-ds-contact`, `data-ds-contact-target`, `data-ds-url`, `data-ds-qr`, `data-ds-copy`, `data-ds-open`; classes `dl-docshare`, `dl-docshare__menu`, `__list`, `__lang`, `__contact`, `is-open`, `is-busy`, `is-on`.
- Mail dialog field names (they live in a dialog moved to `<body>`, never in your form): `to`, `cc`, `bcc`, `subject`, `message`, `with_link`, `with_image`, `with_pdf`, `ds_contact_q`; link dialog `ds_url`.
- Lint: no rule is specific to this object. General screen rules apply to the Blade file: `L001` (no raw `button` / `form` / `input`), `L002` (no inline `<script>`), `L003` (no hex colours). Form side: `F103` warns when a document form with `action` has no `print-url`.

## Rules and pitfalls

- Pair it with the print page: one `x-dl::doc-share` per page and `target` = the `id` of the `x-dl::print-doc`. Give every tag on a page a distinct `type`; the print-language frame finds its twin by `type` and takes the first match.
- Only **stored** data is printed. An online copy is a snapshot of the page as rendered by the server in the print language; unsaved screen changes are not part of it.
- The online link is opened **without login** (dialog text "Link (no login)"). Everything inside `target` is stored on the application's server and reachable by anyone who has the link. Put into the area only what may be shared, make the token unguessable and consider an expiry; the object does nothing of this.
- Inside `target`, mark what must not be exported with `data-dl-noprint` (action bars, helper text).
- `email` and the HTML online item need an endpoint; with no `endpoint` and no `config('devlib.doc_share_url')` they silently do not exist. Set `config('devlib.doc_share_url')` once in the application instead of repeating `endpoint` on every tag.
- A wide area exported as a generic image is scaled to the A4 width; set `orientation="landscape"` for landscape sheets. Tall areas are cut at the page height, which can split a row; use `x-dl::print-doc` for documents that need clean pages.
- `target` is resolved at click time; if the document is built later by script the share still works, but a wrong selector only shows up as the toast.
- The three CDN libraries must be reachable; allow `cdnjs.cloudflare.com` in the Content-Security-Policy.
- Multi-sheet JPEG produces one download per sheet; browsers may ask the user to allow multiple downloads.
- Code oddity (not fixed): `resources/assets/js/components/doc-share.js:282` takes `pages[0].img` for the `with_image` mail body, so a multi-sheet print-doc e-mailed with "document shown as an image" shows only the first sheet (the PDF attachment has all sheets).
- Code oddity (not fixed): `printSheets()` (`doc-share.js:208`) sets the `@page` size and the image height from the first sheet for every page; an A5 document followed by an A4 appendix prints the appendix on the wrong format through the Printer route (the PDF route handles each sheet's format). `Document::appendices()` therefore requires A4 for such documents.
- Code oddity (not fixed): the class comment (`src/Components/DocShare.php:29`) says that elements marked `data-dl-noprint` "(or buttons / action-bar)" are not printed, but the exclusion list `DROP` in `doc-share.js:8` has no action-bar class: only buttons and the listed classes are removed, so non-button content of an action bar (a status badge, for example) is exported. Mark such content with `data-dl-noprint`.
- Code oddity (not fixed): `Document::fileTitle()` (`src/PrintDoc/Document.php:342`) exists for a print file title, but the file name is built in `doc-share.js` from the `title` and `number` props; the method is never called.

## Example

Delivery note page (shape of the catalog demo `print-doc-a5`): share control above the print-doc.

```blade
<x-dl::action-bar>
    <x-dl::doc-share target="#print-doc" type="delivery-note" :doc-id="$dn->id" :number="$dn->no" title="Delivery Note"
        :email-to="$dn->customer_email" />
</x-dl::action-bar>
<x-dl::print-doc id="print-doc" :doc="$printDoc" />
```

A form or card shared with an address book (shape of the catalog demo `doc-share`; fictional contacts). The area is the card; the buttons and the share control are dropped from the export automatically, the status badge stays in it.

```blade
<x-dl::card id="demo-doc" title="Recalculation Inquiry . IQ-2609-0012">
    <x-dl::action-bar align="between">
        <x-dl::badge label="Draft" />
        <x-dl::doc-share target="#demo-doc" type="inquiry-recalc" doc-id="12" number="IQ-2609-0012" title="Recalculation Inquiry"
            email-to="purchasing@customer.test"
            :contacts="[
                ['name' => 'Rina Wulandari', 'email' => 'rina@customer.test', 'group' => 'PT Contoh Baterai'],
                ['name' => 'Sales Admin', 'email' => 'sales@contoh.test', 'group' => 'User'],
            ]" />
    </x-dl::action-bar>
    <x-dl::form.row cols="2">
        <x-dl::field.text label="Product" name="product" value="Battery N120" />
        <x-dl::field.text label="Customer" name="customer" value="PT Contoh Baterai" />
    </x-dl::form.row>
</x-dl::card>
```

Wide sheet, landscape, no e-mail, small buttons:

```blade
<x-dl::doc-share target="#line-sheet" type="line-inspection" number="FRM-QA-01" title="Daily Line Inspection" orientation="landscape" :email="false" size="sm" />
```

Application side, once, in `config/devlib.php`:

```php
'doc_share_url' => '/app/doc-share',        // snapshot + <token>/email routes (implemented by the application)
'mail_contacts_url' => '/app/mail-contacts', // optional: GET ?q= -> {rows: [...]}
```

## Related

- `x-dl::print-doc`: the paper layout this object shares best (sheets with exact paper size and a QR box).
- `x-dl::print-sheet`: sectioned A4 sheet; share it as a generic area (landscape needs `orientation="landscape"`).
- `x-dl::print-out`: nota / report with its own preview bar and Print button; it does not need this object.
- `x-dl::form`: Print and Email buttons that open the page holding this object (`print-url`, `ds_do=email`).
- `x-dl::action-bar`: the usual place for the control.
- `x-dl::button`: the Print and Email buttons are `preset="print"` and `preset="email"` buttons.
- `x-dl::modal`: the link and mail dialogs.
- `x-dl::menu-button`: another menu button that reuses the `dl-docshare` list styling.
- `x-dl::field.text`, `x-dl::field.checkbox`: the fields of the mail dialog.
