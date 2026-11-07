## Overview

`<x-dl::doc-sections>` shows a document that is stored as ONE plain-text string (a Job Description, a short procedure) in a readable structure: a title for each part, paragraphs, numbered lists with proper spacing, and an optional compact block on top for short facts (for example "Reports to" and "Direct reports"). The text itself is never changed; the object only reads its shape.

Pick another object when the document is already HTML or has tables (`x-dl::rich` output inside a card), when you edit the text (`x-dl::field.text` with `multiline`), or when the data is a list of records (`x-dl::table`).

### Reading rules (parse)

| Line in the text | Becomes |
|---|---|
| Only capital letters, digits, space, `& / -`, 3 to 60 characters, at least two capitals in a row (`TUJUAN JABATAN`) | A part title, shown as `Tujuan Jabatan` |
| `1. text` or `1) text` | A numbered item (the original number is kept; the lines that follow join the item until the next number or title) |
| Any other line | A paragraph of the current part (a line break is kept) |
| No title at all | The whole text as plain text, line breaks kept (nothing is lost) |

Everything is escaped: text typed by a user never becomes HTML.

### Generated markup

```html
<div class="dl-docsec" data-dl="x-dl::doc-sections sections=5" ...>
  <dl class="dl-docsec__meta"><div><dt>Atasan Langsung</dt><dd>Presiden Direktur.</dd></div> ...</dl>   <!-- parts named in inline -->
  <section class="dl-docsec__sec"><h4>Tujuan Jabatan</h4><p>...</p></section>
  <section class="dl-docsec__sec"><h4>Tanggung Jawab Utama</h4><ol class="dl-docsec__list"><li value="1">...</li></ol></section>
</div>
```

## Props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `text` | `?string`, `null` | The plain text to read (see the rules). |
| `sections` | `array`, `[]` | Already structured parts `['title' => ?string, 'text' => string, 'items' => [['no' => 1, 'text' => '...']]]`; when given, `text` is not parsed. |
| `inline` | `array`, `[]` | Part titles (compared in capitals, any case in the prop) that are shown as compact rows on top instead of sections, for short facts. |

## Behavior

- Static markup, no JavaScript. Titles and items are not translated (they are data).
- Title text is turned into capitalised words (`DI LUAR TANGGUNG JAWAB` becomes `Di Luar Tanggung Jawab`).
- The static method `Devlib\Components\DocSections::parse($text)` returns the parts for other uses.

## Data and events

Reads no database; emits no events.

## Rules and pitfalls

- A heading must be on its own line and fully in capitals; a line such as `OK` (two letters) or a sentence in capitals with a full stop is not a title.
- A part with a title but no content is dropped.
- Put long free text in paragraphs, short facts in `inline` parts.

## Example

```blade
<x-dl::doc-sections :text="$position->job_description" :inline="['ATASAN LANGSUNG', 'BAWAHAN LANGSUNG']" />
```

## Related

`x-dl::greeting-card` (uses it for the Job Description window with `board['jobdesc']['text']`), `x-dl::card`, `x-dl::field.display`.
