## Overview

`<x-dl::print-doc>` is the standard printout of a transactional document (delivery note, purchase order, voucher, ...). One layout is shared by every document: company header, centered title, QR box, document number, date + status stamp, a two-column field block, one item table, notes + totals, signature boxes and a footer line. A screen does not draw any of it; it hands over one object, a subclass of `Devlib\PrintDoc\Document`, that declares only what differs (title, labels, extra columns, signers) and carries the raw data.

Paper is decided by the number of rows: up to 5 rows = **A5 landscape** (210 x 148 mm), more = **A4 portrait** (210 x 297 mm). Longer lists continue on further sheets (22 rows per A4 sheet); totals and signatures appear only on the last sheet; every sheet has its own header and footer `Sheet n/m`.

### Where it sits in the flow

`x-dl::form` (Print button, `print-url`) -> a page that contains `x-dl::doc-share` + `x-dl::print-doc` -> Print / PDF / JPEG / online link + QR / Email. The print-doc only draws the paper; producing the file, the QR and the e-mail is `x-dl::doc-share` (point its `target` at the id you give to this object). Rule of the project: a printout always carries the document status (`DRAFT`, `CONFIRMED`, `CANCELLED`, ...), see `skills/devlib/transaction-lifecycle.md`.

### Use it, or use another object

| Need | Object |
|---|---|
| One document with a single item table (delivery note, PO, voucher, receipt) | `x-dl::print-doc` |
| Nota / report with many columns, repeating table header, price toggle, group + subtotal, VAT switch, built-in Print button | `x-dl::print-out` (or `x-dl::report`) |
| Form-like printout made of numbered sections (tables, party boxes, free text) | `x-dl::print-sheet` |
| Print / PDF / JPEG / link / Email buttons for any area | `x-dl::doc-share` |

### Markup

```html
<div class="dl-pdoc" data-dl="x-dl::print-doc" ... data-dl-pdoc data-paper="a5|a4" [id, class, ...]>
  <section class="dl-pdoc__sheet is-a5|is-a4" data-pd-sheet data-pd-type="<doc type>" style="--pd-info-rows:6;--pd-rh:7.6mm;--pd-tail:18.5mm">
    <div class="dl-pdoc__wm" aria-hidden="true">DRAFT</div>        <!-- only for DRAFT / WAITING APPROVAL / HOLD / REJECTED / CANCELLED or data.watermark -->
    <header class="dl-pdoc__kop"> company | <h1>title<small>subtitle</small></h1> | "Scan for" + <span class="dl-pdoc__qr" data-pd-qr> </header>
    <div class="dl-pdoc__info"> left: number + refs; right: date + status stamp, party, address lines, side </div>
    <p class="dl-pdoc__nb">NB: ...</p>
    <table class="dl-pdoc__items"> No | name (+ sub, sub2) | Qty | Unit | variant zone | Price | Subtotal </table>
    <div class="dl-pdoc__tail"> .dl-pdoc__notes | .dl-pdoc__tot (.is-grand) </div>   <!-- last sheet only -->
    <div class="dl-pdoc__sign"> receivers, Approved By, Acknowledged By, Prepared By </div>   <!-- last sheet only -->
    <footer class="dl-pdoc__foot"><span>Printed on dd-Mmm-yyyy HH:mm by NAME</span><span>Sheet 1/2</span></footer>
  </section>
  ... more sheets ..., then one A4 appendix section per Document::appendices() entry (class dl-pdoc__appx)
</div>
```

Header fields carry `data-pd-k="<key>"` (`number`, `date`, `party`, and the `key` of each ref / side); item cells carry `data-pd-c="<column key>"`. Extra HTML attributes (`id`, `class`, `data-*`) and `can="module.action"` go on the root `div`; without the ability nothing is rendered.

## Props

Constructor of `Devlib\Components\PrintDoc`: **1 prop**.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `doc` | `Devlib\PrintDoc\Document`, required | The document object. Must be an instance of a subclass of the abstract `Document` (a different type is a PHP `TypeError`). Everything printed comes from it: `$doc->data` (raw values), the declared fields/columns, the paper (`paper()`), the sheets (`sheets()`), totals, notes, signatures, watermark and appendices. Use `:doc="..."` (never a plain string). The catalog source line reads `dokumen cetak: <type> (<class>)`. |

Blade usage: `<x-dl::print-doc :doc="$doc" id="print-doc" />`. The `id` is what `x-dl::doc-share target="#print-doc"` points at.

### The `Document` object (what `doc` carries)

`Devlib\PrintDoc\Document` is abstract. A document kind = one subclass that sets the public properties below (all optional; defaults shown) and is created with `new Kind(array $data)`.

| Property | Default | Meaning |
|---|---|---|
| `type` | `'document'` | Document kind key (`data-pd-type`; also the key for the signer resolver). Independent from the `type` of `x-dl::doc-share`. |
| `title`, `subtitle` | `''`, `null` | Centered heading and the small line under it. English text, translated at print time. |
| `titleCompact` | `false` | `true` = smaller heading font for a long title. |
| `numberLabel` | `'No.'` | Label of the big document number (value = `data['number']`). |
| `refs` | `[]` | Left field slots under the number, max `infoRows - 1` (5). Item: `['key', 'label', 'type'?, 'hand'?]`. |
| `partyLabel` | `''` | Label of the party line (right block): `data['party']['name']` plus `code`, then address lines. |
| `side` | `null` | One extra field at the bottom of the right block, same item shape as `refs`. |
| `nb`, `nbPlain`, `nbRaw` | `''`, `false`, `false` | The sentence under the info block. By default it is prefixed `NB:` and translated; `nbPlain` removes the prefix (an opening sentence); `nbRaw` prints it as is (it already holds document data such as a bank account, not translated). |
| `nameLabel` | `'Item Name'` | Header of the name column. |
| `qtyCols` | `true` | `false` = no Qty / Unit columns (their 36 mm are added to the variant zone). |
| `extra` | `[]` | Variant columns between Unit and Price. Item: `['key', 'label', 'width' (mm), 'type'?, 'hand'?, 'wrap'?]`. |
| `priced` | `false` | `true` = Price + Subtotal columns, the totals box and the amount in words. |
| `receivers` | `[]` | Outside parties, leftmost signature boxes: `[[role, note?], ...]`. |
| `signLabels`, `signNotes` | `approve: Approved By`, `know: Acknowledged By`, `make: Prepared By`; notes empty | Titles and sub-lines of the three fixed signature slots. A slot whose label is `''` is not printed. |
| `qrHint` | `'online document'` | Text under `Scan for` next to the QR box. |
| `capacity` | `['a5' => 5, 'a4' => 22]` | Rows per sheet. |
| `rowHeight`, `infoRows`, `tailHeight` | `7.6`, `6`, `18.5` | Row height (mm), number of rows of the field block, height of the notes + totals zone (mm). |
| `currency` | `'IDR'` | Currency code in the `Total (IDR)` line and for the amount in words. |

Overridable methods: `prepare(array $rows)` (rework rows before printing), `paper()`, `priceColumns()` (default Price 21 mm + Subtotal 25 mm; a journal can return Debit + Credit with the same total width), `appendices()`, `totals()`, `watermark()`, `statusLabel()`.

Static hooks, registered once by the application (service provider):

| Hook | Purpose |
|---|---|
| `Document::companyUsing(fn (bool $ppn) => ['name', 'initials', 'lines' => [...], 'npwp' => ?string, 'logo' => ?url])` | Company header. `$ppn` is true when `data['ppn'] > 0` (the legal-entity header with tax id). `logo` set = the image replaces the initials box and the name. |
| `Document::signersUsing(fn (string $type) => ['approve' => ?string, 'know' => ?string])` | Fixed signer names per document type (used when `data['approvedBy']` / `knownBy` are absent). |
| `Document::labels([English => Indonesian])` | Extra print terms for the application (merged with `Devlib\PrintDoc\Labels::ID`). |

### `data` keys

| Key | Meaning |
|---|---|
| `number`, `date` (ISO), `status` | Number, date (shown `dd-Mmm-yyyy`), status text. `status` is shown in a boxed stamp next to the date and decides the watermark. |
| `party` | `['name', 'code'?, 'lines' => [address lines, max 3]]`. |
| `rows` | List of rows, see below. |
| `notes` | `[[label, text], ...]` printed in the notes zone, after the amount in words. |
| `ppn`, `pph`, `disc` | Percentages used by the default totals (`priced` documents). |
| `sums` | `['lines' => [[English label, amount, 'minus'?, 'suffix'?]], 'grand' => amount]`: totals already computed by the system; the printout does not recompute. |
| `approvedBy`, `knownBy`, `printedBy` | Names under the fixed signature slots; `printedBy` is also written in the footer line. |
| `watermark` | Forces another watermark text. |
| value of every `key` in `refs` / `side` | The field values. |

Row: `name`, `sub`, `sub2` (second and third small lines under the name), `qty`, `unit`, `cls` (class on the `<tr>`; `is-split` marks a split row), `price`, `subtotal`, and one value per `extra` column key. Column `type`: `text` (default), `date`, `money` (2 decimals), `int` (0 decimals), `html` (printed unescaped). `hand: true` = when empty, a dotted line to write on (header fields) or dotted side borders (columns). `wrap: true` = the cell may wrap onto two lines.

## Behavior

### Paper and sheets

- `paper()` is `a5` when the document has 5 rows or fewer, otherwise `a4`. The sheet is padded with empty numbered rows up to `capacity`, so the form always looks full.
- Sheet geometry is fixed in CSS (`.is-a5` 148 mm high, `.is-a4` 297 mm high, both 210 mm wide). Total + signatures only on the last sheet; every sheet repeats header and footer.
- A document with appendices must use A4: override `paper()` to return `'a4'`.

### Variant column zone

The zone between Unit and Price is exactly **84 mm** wide, whatever the document (`priced` documents: `extra` 38 mm + Price 21 + Subtotal 25). With `qtyCols = false` the `extra` columns must be 36 mm wider. The constructor throws `InvalidArgumentException` ("variant columns are X mm wide, must be Y mm") when the sum is wrong. This keeps column positions identical across documents.

### Status, watermark and stamp

The status text is normalised (upper case, `_` -> space, `SUBMITTED` / `APPROVAL` -> `WAITING APPROVAL`, `ON HOLD` -> `HOLD`) and printed in the stamp. A large slanted watermark is added to every sheet for `DRAFT`, `WAITING APPROVAL`, `HOLD`, `REJECTED` and `CANCELLED` (not for `CONFIRMED` / `APPROVED`), or any text in `data['watermark']`. Watermark text longer than 9 characters uses a smaller font.

### Totals, amount in words, notes

Only for `priced` documents. Default calculation from `rows[].subtotal`: Subtotal, Discount (`disc` %, in brackets), VAT (`ppn` %), Income Tax 23 (`pph` %, in brackets, only when not zero), `Total (<currency>)` (bold, with a rule above). `data['sums']` replaces the calculation. The notes zone starts with `In Words` (amount in words written `# ... #`; for IDR also the cents as `... sen`) and then the lines of `data['notes']`. Documents without money (`priced` false) have no totals and no amount in words.

### Signatures

From left: each `receivers` entry (outside party), `Approved By`, `Acknowledged By`, `Prepared By`. Names under the line come from `data` or the signer resolver; an empty name leaves a dotted line to sign. Boxes whose label is `''` are not printed.

### Fit-to-size script

`print-doc.js` (JS class `PrintDoc`, selector `[data-dl-pdoc]`) shrinks text that is too long step by step (0.25 pt) down to **6.5 pt**: item names and sub-lines, wrapped cells, notes, other cells, header fields, the NB line and signer names. It runs on load and again when web fonts are ready. Below 6.5 pt table cells and boxes are clipped (overflow hidden); header field values end with an ellipsis.

### Translation and print language

Labels are written in English in the `Document` class and translated by `Document::t()`: in Indonesian from the print dictionary `Devlib\PrintDoc\Labels::ID` (+ `Document::labels()`), otherwise from the application dictionary (`Lang::text`). Numbers, dates, month names and the amount in words follow the **print language**, which `x-dl::doc-share` can set independently from the screen language by re-rendering the page with `?print_lang=xx`. Data values (names, numbers, notes of the document) are never translated.

### Appendices

`appendices()` returns A4 sheets printed after the document sheets, each `['title' => ..., 'blocks' => [...]]` and/or `'view'` + `'data'` (an application Blade view included in the body). Block `kind`:

| Kind | Fields |
|---|---|
| `facts` | `rows` => `[[label, value], ...]` |
| `table` | `head` (list), `rows` (list of lists), `num` (column indexes right-aligned) |
| `list` | `items` (numbered list) |
| `text` | `text` (line breaks kept) |
| `sign` | `intro`, `checks` (checkbox lines), `slots` => `[[role, note?, name?], ...]` |

Any block may have `title` (heading) and `note` (caption). Footer shows `Appendix n/m`.

### Accessibility

Print output is visual. The watermark is `aria-hidden`. Each sheet has its own `<h1>` (the title). There are no landmarks, ARIA states or keyboard behaviour; the object has no interactive element (buttons belong to `x-dl::doc-share`).

## Data and events

### What is printed

The printout shows what the server renders. For a form, the printout page must load the **stored** record (not the unsaved screen). `x-dl::doc-share` also re-renders the same page from stored data when the print language differs from the screen language, and then warns "Unsaved changes are not printed" if a form on that page is dirty.

### Events and JS

- No custom event is emitted. `el.dlComponent` on the root is the `PrintDoc` instance; `fit()` can be called again after the content changes.
- `x-dl::doc-share` finds the sheets by `[data-pd-sheet]` and puts the QR image into each `[data-pd-qr]` (class `is-on`) before it renders PDF / JPEG / print.

### Lint

No rule is specific to this object. General screen rules apply to the Blade file that holds it: `L001` (no raw `table` / `button` / `input` / `form`), `L002` (no inline `<script>`), `L003` (no hex colours). Form side: `F103` asks for `print-url` on a document form with `action`.

## Rules and pitfalls

- The layout is **locked** in `Document` (project decision 2026-10-01); catalog pages `p5-print` and `p5-print-doc` are APPROVED: do not change the look without the owner's approval. A new document kind only declares fields; it never restyles.
- Always give `data['status']`. An empty status prints no stamp and no watermark, which breaks the rule that a printout carries its status.
- Write labels in English; Indonesian wording for the paper belongs in `Document::labels()`, not in the class. Label properties and `nb` go through the translation (`Document::t()` falls back to the application dictionary, which also records unknown text), so keep data values in `data`; use `nbRaw` for a sentence that already contains data.
- `type: 'html'` values are printed **unescaped**; pass only trusted or sanitised markup. Every other type is escaped.
- `refs` beyond 5 entries and `party.lines` beyond 3 lines are silently dropped (the block has `infoRows` fixed rows). Do not exceed them; change `infoRows` only with the matching design.
- Variant columns must total the zone width (84 mm incl. Price / Subtotal): wrong widths throw at construction time.
- The company header comes only from `Document::companyUsing()`; register it before the first render, otherwise the header box is empty. `data['ppn'] > 0` selects the second (legal-entity) header.
- The QR box stays an empty dashed square unless `x-dl::doc-share` has an endpoint; do not remove the box, the layout reserves it.
- A5 documents with appendices: the appendix sheet is A4 but the browser "Printer" route of `x-dl::doc-share` uses the first sheet's size for all pages; make such documents A4 (`paper()`).
- Do not print this object with the browser's own Ctrl+P: use `x-dl::doc-share` (PDF / JPEG / Printer), which rasterises each sheet at its exact paper size. See the stylesheet oddity below.
- Code oddity (not fixed): `resources/assets/devlib.css:1729-1735` (print-out) contains an `@media print` rule `body *{visibility:hidden}` that is NOT scoped (the label sheet and login card scope theirs with `body:has(...)`), so Ctrl+P on any page that loads the stylesheet hides every element except a `.dl-print__pages` block (an empty page for this object). The `@media print` rules written for `.dl-pdoc` (`devlib.css:2907`) cannot take effect for direct browser printing.
- Code oddity (not fixed): `Document::fileTitle()` (`src/PrintDoc/Document.php:342`) is public but nothing calls it; the downloaded file name comes from the `title` and `number` props of `x-dl::doc-share`.

## Example

Document kind (a delivery note without money, 2 rows, therefore A5 landscape). Shape copied from the catalog demo; data is fictional.

```php
use Devlib\PrintDoc\Document;

final class DeliveryNote extends Document
{
    public string $type = 'delivery-note';
    public string $title = 'Delivery Note';
    public string $numberLabel = 'Delivery Note No.';
    public array $refs = [
        ['key' => 'customer_po_no', 'label' => 'Customer PO No.', 'hand' => true],
        ['key' => 'co_no', 'label' => 'CO Ref.'],
        ['key' => 'vehicle', 'label' => 'Vehicle', 'hand' => true],
        ['key' => 'driver', 'label' => 'Driver', 'hand' => true],
        ['key' => 'departed_at', 'label' => 'Departed', 'hand' => true],
    ];
    public string $partyLabel = 'Ship To';
    public ?array $side = ['key' => 'contact', 'label' => 'Phone / Attn', 'hand' => true];
    public string $nb = 'Complaints no later than 7 days after the goods are received';
    public array $extra = [   // 21 + 13 + 50 = 84 mm
        ['key' => 'weight_kg', 'label' => 'Weight (kg)', 'width' => 21, 'type' => 'money'],
        ['key' => 'bundles', 'label' => 'Bundles', 'width' => 13, 'type' => 'int'],
        ['key' => 'remark', 'label' => 'Remarks', 'width' => 50],
    ];
    public array $receivers = [['Receiver', 'Customer'], ['Driver', '']];
}
```

Printout page: the document, plus the share button pointed at it. For sheets of this object `x-dl::doc-share` takes the paper from the sheet itself (`orientation` is only used for areas without print-doc sheets; the demo still sets it for A5 documents).

```blade
<x-dl::action-bar>
    <x-dl::doc-share target="#print-doc" type="delivery-note" :doc-id="$dn->id" :number="$dn->no" title="Delivery Note" orientation="landscape" />
</x-dl::action-bar>

<x-dl::print-doc id="print-doc" :doc="new \App\Print\DeliveryNote([
    'number' => '10262874', 'date' => '2026-10-01', 'status' => 'CONFIRMED', 'printedBy' => 'Eka', 'co_no' => '2602282',
    'party' => ['name' => 'PT CONTOH MAJU', 'lines' => ['JL. CONTOH NO. 1', 'MALANG']],
    'rows' => [
        ['name' => 'BANANA CHIPS 250 G', 'qty' => 508, 'unit' => 'PCS', 'weight_kg' => 12.7, 'bundles' => 6, 'remark' => '100 / bundle'],
        ['name' => 'DONUT BOX 20 X 20 X 6 CM', 'qty' => 3000, 'unit' => 'PCS', 'weight_kg' => 81, 'bundles' => 60, 'remark' => '50 / bundle'],
    ],
])" />
```

A status other than CONFIRMED shows the watermark, for example `'status' => 'DRAFT'` puts a large slanted `DRAFT` on the sheet. A priced document (more than 5 rows, `public bool $priced = true;`, `extra` widths summing to 38 mm, `'ppn' => 11`) gets Price / Subtotal columns, the totals box and the amount in words.

## Related

- `x-dl::doc-share`: Print / PDF / JPEG / online link + QR / Email for the area that holds this object (`target`).
- `x-dl::form`: its Print and Email buttons open the page that holds this object (`print-url`; Email adds `ds_do=email`).
- `x-dl::print-out`: nota or report with a built-in preview bar (paper, price, VAT, group) and a Print button.
- `x-dl::print-sheet`: sectioned A4 sheet for reports and field forms.
- `x-dl::action-bar`: the usual place for the `x-dl::doc-share` button above the printout.
- `skills/devlib/transaction-lifecycle.md`, `skills/devlib/form.md`, `skills/devlib/grid-form-flow.md`: status on every printout, Print button rules.
