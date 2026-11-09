## Overview

`<x-dl::calculator>` is a round icon button that opens a pop up window (a modal, closed with the x, `Esc` or a click outside; the page behind does not change) with a calculator in the style of the Windows 11 Calculator. It has three tabs:

- **Calculator** - plain arithmetic with big coloured keys. Operations run at once from left to right, like Windows Standard: `2 + 3 × 4 =` gives `20`. `%` = first number × second number / 100 (`200 + 10 %` = `220`). `CE` clears the entry, `C` clears everything, `⌫` deletes a digit, `±` changes the sign. Dividing by zero shows a message and resets.
- **Converter** - converts a number between two units of one quantity (Length, Weight, Area, Volume, Temperature and every other quantity the project passes) or between two currencies. Swap exchanges the two sides.
- **History** (clock icon) - the last 30 results of this device; a click puts the result back into the calculator.

Pick another object when you need a calculation inside a form field (the `+ − × ÷ %` mini calculator of `x-dl::field.decimal`) or a spreadsheet (`x-dl::sheet`).

`x-dl::greeting-card` places the button in its button row when `board['calculator']` is given. Row order, left to right: Calendar (`board['calendar'] = ['url' => ?string]`; no url = disabled button with tooltip "Calendar · Coming soon"), Calculator, Play (music player). All three are round glass buttons on the dark hero (`.dl-wcalc-btn`, same look as the Play button); outside the hero the button uses the accent colour.

### Generated markup

```html
<span class="dl-wcalc-wrap" data-dl="x-dl::calculator id=dl-calculator">
  <button type="button" class="dl-wcalc-btn" data-dl-open="dl-calculator" aria-label="Calculator"><i class="fa-solid fa-calculator"></i></button>
  <div class="dl-mov" data-dl-modal-id="dl-calculator"> ... <div class="dl-wcalc" data-dl-wcalc data-quantities='[...]' data-currencies='[...]'> tabs, keys, converter, history </div> </div>
</span>
```

The script moves the modal to `<body>` on start, so it is not clipped or covered by the box that holds the button.

## Props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `quantities` | `array`, `[]` | `[['key' => 'LENGTH', 'label' => 'Length', 'units' => [['c' => 'mm', 'n' => 'millimetre', 'f' => 0.001, 'o' => 0]]]]`. A unit is converted as `base = value × f + o`, result `= (base − o2) / f2` (`o` is only for temperature: Celsius `f` 1, `o` 273.15). |
| `currencies` | `array`, `[]` | `[['c' => 'USD', 'n' => 'US Dollar', 'r' => 15800]]`. `r` = value of 1 unit of that currency in `base`; `null` = no rate yet. |
| `featured` | `array`, `[]` | Quantity keys shown as chips next to Currency; the others are in the "All quantities" list. |
| `base` | `string`, `'IDR'` | Currency whose rate is always 1 (added when missing). |
| `rate-url` | `?string`, `null` | Link shown when a currency has no rate ("Set the rate in Exchange Rate"). `null` = no link. |
| `id` | `string`, `'dl-calculator'` | Modal id (open it from any button with `data-dl-open="<id>"`). |
| `storage-key` | `string`, `'dl-calculator'` | `localStorage` key of the history. Per browser. |

## Behavior

- Numbers follow the display language (`1,250.5` in English, `1.250,5` in Indonesian); keys accept the keyboard: digits, `.` or `,`, `+ - * /`, `x`, `%`, `Enter` (=), `Backspace`, `Delete` (CE).
- A currency without a rate shows `–` and a note naming it; no number is invented.
- The currency list shows currencies with a rate first; `Rate: 1 USD = 15,800 IDR` is shown under the result.
- Calculations use ordinary decimal numbers and show at most 12 significant digits (`0.1 + 0.2` = `0.3`).

## Data and events

Reads no database: the project passes the quantities and currencies. Writes only `localStorage[storage-key]` (history). No events are emitted.

## Rules and pitfalls

- The data sits in the page as JSON: a few hundred units are fine; do not pass thousands.
- Currencies and rates must come from the project's master data, never typed into the page.
- One calculator per page (the modal id must be unique).

## Example

```blade
<x-dl::calculator :quantities="$quantities" :currencies="$currencies" :featured="['LENGTH', 'MASS']" base="IDR" rate-url="/v2/md-exchange-rate" />
```

## Related

`x-dl::greeting-card` (places the button), `x-dl::modal` (the window), `x-dl::music-player` (sits beside it), `x-dl::field.decimal` (mini calculator inside a field), `x-dl::sheet`.
