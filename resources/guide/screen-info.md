## Overview

`<x-dl::screen-info>` is a screen diagnostic page body. Opened on the real device, it shows what the browser really gives the page: CSS width x height, pixel ratio (DPR), orientation, the heights `svh` / `lvh` / `dvh`, `visualViewport`, touch or mouse, the safe area, the DevLib screen rule and the view in use, the OS, the User-Agent, and the time. A big button "Copy result" puts ONE plain text block on the clipboard (first line = the key numbers) to paste into a chat. It measures a phone before a breakpoint is decided, and it checks a layout after.

It is pure client side: nothing is sent, nothing is stored. The server prints only three values of the request itself (the view it rendered, where that choice came from, the OS from the User-Agent).

Use it for a measuring page of a project (for example a public `GET /ukur`) and for the catalog page `p7-screen`. Do NOT use it as a part of a working screen.

### Generated markup

```html
<section class="dl-sinfo" data-dl="x-dl::screen-info" data-dl-screen-info data-view="phone" data-view-source="cookie" data-os="android" data-t="{...}">
  <h2 class="dl-sinfo__t">Screen info</h2>
  <p class="dl-sinfo__note">Open this page on the device you want to measure ...</p>
  <div class="dl-sinfo__sum" role="status" aria-live="polite" data-sinfo-sum>360 x 791 - DPR 3 - portrait - phone</div>
  <button type="button" class="dl-sinfo__copy" data-sinfo-copy>Copy result</button>
  <p class="dl-sinfo__st" role="status" data-sinfo-status></p>
  <div class="dl-sinfo__groups"> <section class="dl-sinfo__g"><h3>Screen</h3><dl><div class="dl-sinfo__r"><dt>CSS width x height</dt><dd data-sinfo="css">360 x 791</dd></div> ... </dl></section> ... </div>
  <details class="dl-sinfo__txt" data-sinfo-details><summary>Result text ...</summary><textarea readonly data-sinfo-text></textarea></details>
</section>
```

Extra HTML attributes (`id`, `class`, `style`, `data-*`) go on the `section`. `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\ScreenInfo` (2 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `title` | `string`, `'Screen info'` | Heading. English text, translated through the dictionary. |
| `note` | `string`, `''` | Sentence under the heading. Empty = the default ("Open this page on the device you want to measure, then tap Copy result and paste it in the chat. Nothing is sent or stored on the server."). |

## Behavior

### Rows

| Group | Rows (key) |
|---|---|
| Screen | `css` (innerWidth x innerHeight), `dpr`, `orient`, `phys` (CSS x DPR = approximate physical size), `inner` (innerWidth / clientWidth), `screen` (`screen.width x height` and available) |
| Height units | `svh`, `lvh`, `dvh` (measured with a hidden probe; "not supported" on old browsers), `vv` (`visualViewport` width x height, scale; shows pinch zoom) |
| Input and safe area | `pointer`, `anypointer`, `hover` (media features), `safe` (top / right / bottom / left of `env(safe-area-inset-*)`, read from a probe; needs `viewport-fit=cover`, which `Devlib\Device::viewport()` adds in the phone view) |
| DevLib | `rule` (the screen rule of `skills/devlib/views.md` section 1b with the reason: phone when width < 600 px, or phone sideways), `view` (the view the server rendered and where the choice came from: cookie ui, client hint, User-Agent, preview override), `os` (server / browser), `bp` (active `max-width` breakpoints) |
| Browser | `ua`, `uad` (`navigator.userAgentData` brands, mobile, platform, and model when the browser gives it; marked optional), `secure` (`isSecureContext`), `time` (ISO time of the measurement) |

### Summary box and the copied text

The box on top says `<width> x <height> - DPR <n> - <orientation> - <phone|desktop>`. "Copy result" copies one plain text block: the first line is the same numbers (`360 x 791 - DPR 3 - portrait - phone`), then every group as `[Group]` followed by `label: value` lines.

### Copy, with fallbacks

1. `navigator.clipboard.writeText` when the page is a secure context (https or localhost).
2. Otherwise (plain `http://192.168.x.x` has no `navigator.clipboard`): the text of the textarea is selected and `document.execCommand('copy')` is used.
3. If both fail, the result text box opens with the whole text selected and a message tells the user to copy it by hand (long press, then Copy). The text is always visible there.

### Live update

The numbers are measured again on `resize`, `orientationchange`, `pageshow`, `visibilitychange`, `visualViewport` `resize` and `scroll` (on-screen keyboard, pinch zoom), and when the pointer / hover / orientation / screen-rule media queries change. Measuring is batched in one animation frame.

### Text and language

Labels and messages are English in the code and translated through the dictionary. The script receives the translated messages from the server (`data-t`), so it carries no dictionary. The measured values (numbers, `phone` / `desktop`, the User-Agent) are not translated.

### Accessibility as implemented

The summary and the status line are `role="status"` live regions. The copy button is a real `<button>` (60 px high, 18 px text, visible focus ring). All controls are at least 16 px, the textarea is labelled, and colors come from theme tokens (light and dark). One column on a phone, two columns from 720 px.

## Data and events

- No data in or out, no network call, no storage, no `dl:*` event. `el.dlComponent` is the instance (`measure()`, `copy()`).
- Hooks: `data-dl-screen-info` (root, script selector), `data-sinfo="<key>"` (value cells), `data-sinfo-sum`, `data-sinfo-copy`, `data-sinfo-status`, `data-sinfo-text`, `data-sinfo-details`.

## Rules and pitfalls

- The page that holds it should have `<meta name="viewport" content="{{ \Devlib\Device::viewport(request()) }}">`. Without a viewport meta a phone browser renders at 980 px and every number is wrong.
- Android "Display size" and "Request desktop site" change the numbers; write them down when measuring.
- The model name needs a secure context and a browser that allows it; on plain http it is simply missing (the row is optional).
- The result text contains the User-Agent (and the model when available): it describes the device, not the person, but paste it only where the user wants.
- Related lint rules: none (the object has no own rule).

## Example

```blade
@extends('layout-minimal')
@section('content')
    <x-dl::screen-info />
@endsection
```

## Related

- `Devlib\Device`: `view()`, `viewSource()`, `os()`, `screenIsPhone()` and `PHONE_MEDIA` (the screen rule).
- `x-dl::device-frame`, `x-dl::view-as`: preview a page at device sizes.
- `skills/devlib/views.md`: the multi view rules and the screen rule (section 1b).
