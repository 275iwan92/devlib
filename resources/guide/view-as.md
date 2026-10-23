## Overview

`<x-dl::view-as>` is a small floating button (bottom right, next to the `</>` code button) with a menu of five devices: Android phone 360x790, iPhone 393x852, Tablet 820x1180, Laptop 1366x768, Desktop 1920x1080. Choosing one opens the preview page of the project, which shows the same page inside `x-dl::device-frame` (source mode) at that size, rendered as the `phone` or the `desktop` view. It is a pure link list: no JavaScript, no data, no state.

Use it on a catalog or QA site where a screen must be checked in both views (`skills/devlib/views.md`: every new object is tested in two views). Do NOT put it on a working screen of an end-user app.

### Generated markup

```html
<details class="dl-viewas" data-dl="x-dl::view-as">
  <summary class="dl-viewas__btn" title="View as" aria-label="View as"><i class="fa-solid fa-mobile-screen-button"></i></summary>
  <nav class="dl-viewas__menu">
    <span class="dl-viewas__t">View as</span>
    <a href="/lib/x/as?as=android"><i class="fa-brands fa-android"></i> Android phone <small>360&times;790</small></a>
    <!-- iphone, tablet, laptop, desktop: same shape -->
  </nav>
</details>
```

Extra attributes (`class`, `id`, `data-*`) go on the `details`. `can="module.action"` works as for every component.

## Props

Constructor of `Devlib\Components\ViewAs` (2 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `href` | `string`, required | Address of the preview page. Each menu link is `href` + `?as=` and one of `android`, `ios`, `tablet`, `laptop`, `desktop` (`&as=` if `href` already has a query). |
| `label` | `string`, `'View as'` | Title of the menu and tooltip of the button. Translated through the dictionary. |

## Behavior

- The menu list comes from `Devlib\Components\DeviceFrame::PRESETS`, so the sizes are written once. The `<details>` element opens and closes the menu natively (keyboard: Enter / Space on the summary).
- The component only links. The project provides the route behind `href`; it reads `?as=` and renders `<x-dl::device-frame :src="page address" :preset="as" :back="page address" />`. The catalog route is `lib.as` (`/lib/{slug}/as`).
- For the preview to render the right view, the project adds `Devlib\ViewOverride` to its `web` middleware group (`$middleware->appendToGroup('web', \Devlib\ViewOverride::class)`). Without it the iframe shows the page in the view the browser would get anyway.
- Hide it inside the preview iframe (the catalog does: `@unless (request()->has('dl-view'))`), otherwise the iframe shows its own button.

## Data and events

- No data in or out, no `dl:*` event, no JS class (`el.dlComponent` does not exist).
- Hooks: `.dl-viewas`, `.dl-viewas__btn`, `.dl-viewas__menu`.

## Rules and pitfalls

- Fixed position (`right: 60px; bottom: 14px`) assumes the `x-dl::code-button` at `right: 14px`; on a page without it the button simply sits 60 px from the edge.
- Links lose their parameters inside the iframe: preview one page at a time.
- Related lint rules: none.

## Example

```blade
@unless (request()->has('dl-view'))
    <x-dl::view-as :href="route('lib.as', $slug)" />
@endunless
```

## Related

- `x-dl::device-frame`: source mode draws the preview.
- `Devlib\Device`, `Devlib\ViewOverride`: view model and the per-request override.
- `skills/devlib/views.md`: the multi view rules.
