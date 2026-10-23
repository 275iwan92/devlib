## Overview

`<x-dl::greeting-card>` is the "hero" block at the top of an opening screen. From one array (`board`) it draws, on a dark gradient card: the profile eyebrow, the time-of-day salutation with the user's first name (`x-dl::greeting`), the position / department / superior line, a "today" brief strip, the Job Description and SOP buttons (each opens a read-only modal), and on the right side the running clock (`x-dl::clock`), the check-in badge with the shift, and the current weather (`x-dl::weather`). It can be placed alone, and `x-dl::dashboard` uses it as its first block.

It does not read a database or call a server. Everything comes from `board`; the live parts (time, salutation, weather) are the three child objects, so their behaviour (time zone, language, internet call, intervals) is described in their own entries: `x-dl::greeting`, `x-dl::clock`, `x-dl::weather`.

### Greeting versus greeting card: which one to use

| | `x-dl::greeting` | `x-dl::greeting-card` |
|---|---|---|
| Output | One line of text (`Good morning, Yuna`) | A whole hero section with buttons, modals, clock, check-in and weather |
| Input | `name`, `timezone` | `board` (profile slice of the dashboard data) |
| Network | None | Only through the weather child (browser call to Open-Meteo) |
| Server data needed | None | Names, position, brief, documents (HTML), attendance, location |
| Time zone | Prop `timezone` | Not configurable here: the children use `config('devlib.timezone')` |
| Typical place | Header, login frame, side panel | First row of the Beranda / opening screen |

Rule of thumb: if you only want to say hello, use `x-dl::greeting`. If the screen is the user's home and should also show who they are, their documents, attendance and weather, use `x-dl::greeting-card` (or `x-dl::dashboard`, which already includes it). Do not put a greeting card and a dashboard of the same profile on one page, see the pitfalls.

### Generated markup

```html
<div data-dl="x-dl::greeting-card profile=general" ...>              <!-- root: no class of its own -->
  <section class="dl-dash__hero">
    <div class="dl-dash__hello">
      <div class="dl-dash__eyebrow">Dashboard</div>
      <div class="dl-greeting" data-dl-greeting data-name="Yuna" data-tz="Asia/Jakarta">...</div>
      <div class="dl-dash__pos"><span>Position</span><span>Department</span><span>Reports to: Name</span></div>
      <div class="dl-dash__brief"><span>Label <b>Value</b></span> ...</div>             <!-- only with brief -->
      <div class="dl-dash__docs">
        <button class="dl-btn ..." data-dl-open="dash-general-jd">Job Description</button>   <!-- only with jobdesc -->
        <button class="dl-btn ..." data-dl-open="dash-general-sop">SOP <span class="dl-dash__count">2</span></button>  <!-- only with sops -->
      </div>
    </div>
    <div class="dl-dash__side">
      <div class="dl-clock dl-clock--clock" data-dl-clock ...>...</div>
      
      <div class="dl-dash__att"><x-dl::badge/> <small>status · Shift ... (start-end)</small></div>   <!-- only with attendance -->
      <div class="dl-weather" data-dl-weather data-lat data-lon>...</div>                 <!-- only with weather -->
    </div>
  </section>
  <!-- x-dl::modal id="dash-general-jd" and id="dash-general-sop" (only when the data exists) -->
</div>
```

The root accepts extra HTML attributes (`id`, `class`, `style`, `data-*`) and `can="module.action"` (without the ability nothing is rendered). On a narrow screen (<= 760px) the side column goes under the text (CSS `.dl-dash__side` media rule).

## Props

Constructor of `Devlib\Components\GreetingCard` (2 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `board` | `array`, `[]` | The profile slice described below. Bind with `:board="$data"`. Keys not listed here (for example `kpis`, `periods`, `quotes` of a full dashboard board) are ignored, so the same array that feeds `x-dl::dashboard` can be passed unchanged. An empty array still renders: eyebrow "Dashboard", an anonymous salutation and the clock. |
| `source` | `?string`, `null` | Free text naming where the data comes from; shown by the developer catalog / Manifest (`source()`). `null` = `profil dashboard per jabatan`. No visual or runtime effect. |

### Shape of `board`

| Key | Used for | Notes |
|---|---|---|
| `profile` | Modal ids (`dash-<profile>-jd`, `dash-<profile>-sop`) and the catalog tag | Default `general`. |
| `profile_label` | Eyebrow above the salutation | Default `Dashboard`. Printed as given (not translated): give the already-translated text. |
| `user` | `first` (name for the salutation), `position`, `department`, `superior` | Missing or empty items are dropped from the line. `superior` is shown as `Reports to: <superior>` ("Reports to" is translated). `user.name` and `user.code` are not used by this object. |
| `brief` | Strip under the position line | List of pairs `[label, value]`, printed as `label <b>value</b>`. Not translated: values are data and labels should be given in the display language. Omit or empty = no strip. |
| `attendance` | Check-in badge | `['in' => '07:52', 'status' => 'On time', 'tone' => 'success', 'shift' => 'Regular', 'start' => '08:00', 'end' => '17:00']`. Rendered when the array is not empty; then `in` is required (the others have fallbacks: tone `success`, status empty, shift `-`). |
| `weather` | Weather child | `['city' => ..., 'lat' => ..., 'lon' => ...]`. All three keys are required when the array is not empty. Omit/empty = no weather. |
| `jobdesc` | Job Description button and modal | `['title' => ..., 'updated' => ..., 'html' => ...]`. `html` is printed unescaped. |
| `sops` | SOP button (with the count) and modal with one accordion per SOP | List of `['code', 'title', 'revision', 'effective', 'html']`; all five keys are read. The first accordion is open. `html` is printed unescaped. |

## Behavior

### What is live and what is static

- Static (server, once): eyebrow, position line, brief, check-in text, the Job Description and SOP content.
- Live (browser): the salutation (`x-dl::greeting`, every 60 s and on language change), the clock (`x-dl::clock`, every second) and the weather (`x-dl::weather`, one fetch at page load). See their entries for time zone, locale, failure behaviour and cleanup.
- The card passes no time zone to its children: salutation and clock use `config('devlib.timezone')` (and `timezone_label` for the clock). To show another zone, build the pieces yourself from `x-dl::greeting`, `x-dl::clock` and `x-dl::weather`.

### Language and locale

- Translated by the object: "Reports to", "Last updated", "Effective from", the modal titles (the position/title part is wrapped with `raw()` so it is left alone), the clock date and the salutation words.
- English by the project rule for buttons and badges (`x-dl::button` and `x-dl::badge` do not use the dictionary unless `config('devlib.translate_fixed')` is on for a single-language application): the buttons "Job Description", "SOP", "Close" and the badge text "Check-in <time>".
- Not translated: everything that comes from `board` (eyebrow, positions, brief, attendance status/shift, document HTML) and the literal word `Shift` in the check-in line.
- The language of the server-rendered text is fixed when the page is rendered. Switching the language with the flags changes only the live parts at once (salutation, clock, weather description); the rest changes after a reload (`x-dl::lang-switch` with reload).

### Documents (Job Description, SOP)

- The buttons carry `data-dl-open="<key>-jd"` / `"<key>-sop"`; the modal library opens them; `[data-dl-close-modal]`, Esc and a click outside close them (`resources/assets/js/core.js`, modal library section).
- Both modals are `size="lg"`, read-only. The Job Description footer shows `Last updated <updated>`; every SOP accordion shows `Rev. <revision>` as badge and `Effective from <effective>`.
- `key()` returns `dash-<profile>`; it is the same id base as `x-dl::dashboard`, so a dashboard that embeds the card keeps its buttons working.

### Accessibility as implemented

Standard `button`s and modal; the sections are plain `div`/`section` without landmarks or `aria-live`. The clock and salutation are not announced when they change.

## Data and events

- Input: the `board` array only. No request is made by the card itself. The only network call is the weather child (browser to `api.open-meteo.com`, sends the latitude/longitude of the configured place).
- Output: none. No form field, no `dl:*` event from the card. Children events: none (`x-dl::greeting`, `x-dl::clock`, `x-dl::weather` emit nothing; they listen to `dl:lang`).
- Hooks: `data-dl-open="<key>-jd|-sop"` (document buttons), `data-dl-close-modal`, plus the child hooks `data-dl-greeting`, `data-dl-clock`, `data-dl-weather`.
- JS: there is no script of its own (`jsClass()` is `null`); each child has its own `el.dlComponent`.

## Rules and pitfalls

- The `html` of `jobdesc` and of every SOP is printed with `{!! !!}`. It must come from a trusted editor and be sanitised on save (for example the HR master), never from free user input.
- One card per profile per page: the modal ids come from `profile`. A standalone card plus an `x-dl::dashboard` with the same `profile` on one page produces duplicate ids and the buttons open the wrong modal. The dashboard already contains its card.
- Give the already-translated text for `profile_label`, `brief` labels and the attendance `status`/`shift`; the object does not translate them. Wrap data values in `raw()` when they are mixed into translated props.
- Missing required keys fail at render (undefined array key): `attendance.in`, `weather.city/lat/lon`, all five keys of each SOP, `jobdesc.html` (`jobdesc.title` and `jobdesc.updated` have fallbacks). Pass `null` or `[]` for a whole block you do not have.
- Attendance and location come from the caller. The card shows what it is given; it never decides lateness. Personal data in `board` is the caller's responsibility.
- Time zone and `timezone_label` come from `config('devlib.timezone')` and `config('devlib.timezone_label')`. The limits of the children apply here too: the device clock is the source of time, an invalid zone name breaks the page script, and the weather needs internet access from the visitor's browser.
- Related lint rules: none (`src/Lint` has no rule for this object).
- Code oddity (not fixed): `resources/views/components/greeting-card.blade.php:38` writes the word `Shift` as a literal inside `<small>`; a plain view literal is not passed through the dictionary, so it stays English in every language (unlike "Reports to", "Last updated" and "Effective from", which use `Lang::text`).

## Example

The catalog page (`site/resources/views/demos/greeting-card.blade.php`) feeds the card from the sample data class:

```blade
{{-- Greeting widget: salutation, position, today brief, Job Description & SOP buttons, clock, check-in, weather --}}
<x-dl::greeting-card :board="\App\Support\DashboardData::forLogin('yuna')" source="App\Support\DashboardData (contoh)" />
```

A minimal board in code (fictional data):

```blade
@php
    $board = [
        'profile' => 'general',
        'profile_label' => 'Dashboard',
        'user' => ['first' => 'Yuna', 'position' => 'Purchasing Staff', 'department' => 'Purchasing', 'superior' => 'Rina'],
        'brief' => [['Open POs', '4'], ['Waiting approval', '2']],
        'attendance' => ['in' => '07:52', 'status' => 'On time', 'tone' => 'success', 'shift' => 'Regular', 'start' => '08:00', 'end' => '17:00'],
        'weather' => ['city' => 'Surabaya', 'lat' => -7.2575, 'lon' => 112.7521],
        'jobdesc' => null,
        'sops' => [],
    ];
@endphp
<x-dl::greeting-card :board="$board" />
```

## Related

- `x-dl::greeting`, `x-dl::clock`, `x-dl::weather`: the three live children; their entries explain time zone, locale, failure and cleanup.
- `x-dl::dashboard`: the full opening screen; it renders this card first and shares the same `board`.
- `x-dl::modal`, `x-dl::accordion`, `x-dl::button`, `x-dl::badge`: used inside the card for documents, SOP list, buttons and the check-in badge.
- `x-dl::geo-clock`: the mobile GPS clock in / out, the usual way the check-in data of `attendance` is produced.
- `x-dl::lang-switch`, `x-dl::theme-boot`: language switch and the script values the children need.
- `skills/devlib/components.md`: generated prop list.
