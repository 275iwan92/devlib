## Overview

`<x-dl::code-chain>` is a set of linked dropdowns (one per level) that BUILDS A CODE while the user picks. Every option carries a code piece; the finished code is the pieces joined from level 1 down to the deepest level picked, and it is shown live under the dropdowns (for example `Paper > Art carton > 210gr > 65 X 100 CM > GOLDEN` gives `PCC02002`). It was made for Material Category (category 1 to 5 gives the item code) and the same pattern fits Product. The object is generic: the whole tree is handed over as a plain array (`nodes`), there is no table, model or HTTP source behind it, and all choices are serialised into the page (`data-nodes`) and filtered in the browser.

Use it when:

- the choices form a tree with a fixed depth (the `levels` labels) and each choice must contribute a code piece;
- the user must see the code grow while choosing, and the form must post both the deepest id and the finished code.

Do NOT use it when:

- the dropdowns are not parent-child (use independent `x-dl::field.select` fields);
- you only need one long list with search: use `x-dl::field.select` or `x-dl::field.lookup`;
- the tree is edited here: the object only chooses. Adding is delegated to another screen through `add-url` (the Add button is a plain link).

### Markup produced

```html
<div class="dl-chain" data-dl="x-dl::code-chain name=category" data-dl-class="CodeChain ← DevlibComponent" data-dl-src="rantai pilihan"
     data-dl-chain data-name="category" data-levels="5" data-nodes="[[id,parent,level,label,piece],...]"
     data-navigate="" data-add="" data-t-choose="..." data-t-complete="..." data-t-level="..." data-t-first="...">
  <input type="hidden" name="category" value="9" data-cc-value>            <!-- deepest id -->
  <input type="hidden" name="category_code" value="PCC02" data-cc-codeval> <!-- finished code -->
  <div class="dl-chain__cols" style="--dl-chain-n:5">
    <div class="dl-chain__col" data-cc-level="1">
      <div class="dl-field ..."> ...x-dl::field.select name="category_1" label="Category 1"... </div>
      <div class="dl-chain__seg" data-cc-seg>P</div>                       <!-- code piece of this level -->
      <a class="dl-btn dl-btn--secondary dl-btn--sm dl-chain__add [is-disabled]" href="..." data-cc-add><span class="dl-btn__icon"><i class="fa-solid fa-plus"></i></span>Add</a> <!-- only with add-url -->
    </div>
    ... one dl-chain__col per level ...
  </div>
  <div class="dl-chain__code">
    <span class="dl-chain__codelabel">Item Code</span>
    <b data-cc-code>PCC02</b>
    <small data-cc-hint>Level 4 of 5</small>
  </div>
</div>
```

Each level is a normal searchable `x-dl::field.select` (hidden input `data-dl-value`, `.dl-dd` panel), so it inherits keyboard behaviour and look from that field. The root also carries the usual `data-dl`, `data-dl-class`, `data-dl-src` (developer catalog / Manifest overlay). The columns are a CSS grid with `--dl-chain-n` columns (the number of levels); below 900px wide they wrap (`repeat(auto-fit, minmax(150px, 1fr))`).

## Props

Constructor of `Devlib\Components\CodeChain` (10 props). Blade names are kebab-case; use `:prop="expr"` for arrays, numbers and booleans.

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `nodes` | `array`, `[]` | The whole tree, one row per choice: `[id, parent, level, name, code piece, active]`. `id` int (unique, not `0`); `parent` int (`0` = top level); `level` int, 1-based and equal to the parent level + 1; `name` string shown in the dropdown; `code piece` string appended to the code; `active` `1` or `0` (optional, default `1`). Every value is cast (`int` / `string`) in the constructor. Duplicate ids overwrite each other. A node whose `parent` does not exist or whose `level` does not match its depth never appears. |
| `levels` | `array`, `[]` | Labels of the levels, in order, for example `['Category 1', ..., 'Category 5']`. The count IS the chain depth (number of dropdowns, `data-levels`, `--dl-chain-n`). Labels become the `label` of each dropdown (translated through the dictionary) and feed the hint `Pick <level 1> first` and the placeholder `Pick <previous level> first`. An empty array renders no dropdown (a lone code line). |
| `value` | `mixed`, `null` | Id of the DEEPEST chosen node. The chosen node of every level above is derived by walking up the `parent` links (at most `count(levels)` steps), so only one id is needed to restore a saved record. `null`, `''` or an unknown id means nothing chosen. Written as-is into the hidden input `<name>`, even when the id is not in `nodes` (then the code stays empty while the hidden value is not). |
| `name` | `string`, `'chain'` | Base name of every posted input (see Data and events) and the id part of the tag in the developer catalog (`x-dl::code-chain name=<name>`). Use a distinct name per chain on one page. |
| `code-label` | `string`, `'Code'` | Caption in front of the finished code (`dl-chain__codelabel`). Translated with `Lang::text` in the view; write it in English (`Item Code`). |
| `navigate` | `?string`, `null` | Master-screen mode. A URL with the placeholder `{id}`: every time the user picks (or clears) a dropdown the browser goes to that URL instead of staying on the page (`location.href`), with `{id}` replaced by the URL-encoded chosen id; when a level is cleared, `{id}` is the id of the level above (empty when level 1 is cleared). The target screen is expected to render the chain again with `value` set. In this mode nothing is refilled in the browser: no segment update, no `dl:chain-change`. Only the first `{id}` in the string is replaced. |
| `add-url` | `?string`, `null` | Shows an `Add` button (`dl-chain__add`) under every level. A URL with the placeholders `{level}` (the level number) and `{parent}` (the id chosen at the level above, empty for level 1). The button is a plain link (`<a href>`): it navigates, it opens no modal and posts nothing. A level whose upper level is not chosen yet gets `is-disabled` (no `href`, dimmed, not clickable); the href is recomputed in the browser after each pick. `null` renders no button at all. Ignored when `disabled` is on (all buttons are disabled). |
| `inactive` | `bool`, `false` | `true`: inactive nodes (`active` = `0`) also appear in the dropdowns, marked ` — inactive` (translated). `false`: only active nodes appear, plus the node that is currently chosen (so an old record can still show its inactive choice; once the user picks something else, it is gone from the list). |
| `disabled` | `bool`, `false` | Locks the whole chain: every dropdown is disabled (no options are even rendered), every Add button is disabled. The chosen values, the code and the hidden inputs are still rendered, so a locked form still posts them unless the form itself blocks posting. Use for a document that is Confirmed. |
| `source` | `?string`, `null` | Free text naming where the data comes from, written in `data-dl-src` for the developer catalog / Manifest (default `rantai pilihan`). No visual or runtime effect. |

Common attribute: `can="module.action"`; without the ability the whole chain is not rendered. Any extra HTML attribute (`id`, `class`, `style`, `data-*`) is merged onto the root `div.dl-chain`.

Interactions between props:

- `value` is read against `nodes` and `levels`; a node deeper than `count(levels)` is cut off.
- `inactive` changes both what PHP renders and what the browser may offer later (the browser list is built from the same filtered `data-nodes`).
- `navigate` wins over the in-page behaviour: with it set, `add-url` still works but the code line is only computed by the server on the next page.
- `disabled` wins over `add-url` and `value`: nothing can be changed.

## Behavior

### Choosing

1. At first only level 1 is open. Levels below are disabled with the placeholder `Pick <previous level> first`.
2. Picking a node at level `n` fills level `n+1` with the children of that node (the first option is `Choose...`) and enables it. Levels `n+2` and below are emptied and disabled. A level with no children stays disabled: a branch can end before the last level, in which case the hint stays at `Level n of N` and `complete` is `false`.
3. Clearing a level (choosing the empty option) empties every level below it.
4. After every pick the browser recomputes: the code piece shown under each chosen level (`dl-chain__seg`), the finished code (`<b data-cc-code>`, a dash `—` when empty), the hint, the Add links, the two hidden inputs and fires `dl:chain-change`.

### Code and hint

- Finished code = pieces of the chosen nodes of levels 1 to k joined with no separator (k = the first empty level minus 1).
- Hint texts (translated): none chosen `Pick <level 1> first`; all levels chosen `Complete`; otherwise `Level n of N`.
- A sibling group with the same name (case-insensitive, trimmed) gets its code piece appended to the label, for example `GOLDEN (002)` and `GOLDEN (003)`, so identical names are distinguishable.

### Statuses

There is no status machine. The only states are: open / disabled level (by parent chosen), active / inactive node (by `active` and `inactive`), complete / incomplete chain (hint), and locked (`disabled`).

### Screen text, language and rights

- Translated at render time: each level `label` (through `x-dl::field.select`), `code-label`, the hint strings, the placeholders, the `inactive` mark and `Choose...`. The hint and placeholder strings for the browser are rendered into `data-t-*` attributes, so they follow the page language at load time (not when the language is switched without reload).
- Node names and code pieces are DATA and are never translated.
- Access: `can="module.action"` as on every DevLib object.

### Accessibility as implemented

- The labels are real field labels (`x-dl::field.select`); the dropdown button and panel come from that field. The code line and the hint are plain text without `aria-live`, so a screen reader does not announce a changed code; the segment boxes carry no label.
- The Add button is an `<a>`; when disabled it has no `href` (so it leaves the Tab order) and `pointer-events:none`.

## Data and events

### What the server receives

For `name="category"` and 5 levels:

| Field | Value |
|---|---|
| `category` | Id of the deepest chosen node (empty when nothing chosen). The one to store. |
| `category_code` | The finished code (empty when nothing chosen). Treat it as display and cross-check; always recompute it on the server from the stored tree. |
| `category_1` ... `category_5` | The chosen id of each level (empty for levels not chosen). |

Nothing is posted twice by the browser; all inputs are plain named hidden inputs inside whatever `<form>` encloses the chain. The server must verify that the posted id exists, is active when the rule says so, and that the parents match.

### Event

`dl:chain-change` is dispatched on the root `div.dl-chain` by the browser after every in-page pick; it bubbles and is not cancelable.

```js
document.addEventListener('dl:chain-change', e => {
  // e.detail = { ids: ['1','4','7','9'], code: 'PCC02', complete: false }
});
```

`ids` are strings (values of the hidden inputs), `code` is the finished code, `complete` is `true` only when every level is chosen. It is NOT fired on first load and NOT fired in `navigate` mode.

### Markup hooks and JS

| Hook | Where |
|---|---|
| `data-dl-chain` | root (the class `CodeChain` in `resources/assets/js/components/code-chain.js` mounts on it). |
| `data-name`, `data-levels`, `data-nodes`, `data-navigate`, `data-add` | configuration read by the script (`data-nodes` rows are `[id, parent, level, label, piece]`). |
| `data-t-choose`, `data-t-complete`, `data-t-level`, `data-t-first` | texts for the browser. |
| `data-cc-level="n"` | one column. `data-cc-seg`, `data-cc-add` inside it. |
| `data-cc-value`, `data-cc-codeval`, `data-cc-code`, `data-cc-hint` | the two hidden inputs, the code text and the hint text. |

`el.dlComponent` is the `CodeChain` instance (methods `pick(level, id)`, `paint()`, `children(parent, level)`). The script listens for `change` on the dropdowns inside the root (a hidden input named `<name>_<n>` with `n` in 1..levels). `Devlib.h.setOptions` is used to refill the dropdowns. Override with `data-dl-js` + `Devlib.register(class MyChain extends Devlib.classes.CodeChain {...})` if a project needs different behaviour (the PHP class returns no `jsClass()`).

## Rules and pitfalls

- `value` is a single id: store only the deepest id and rebuild the chain with it. Never store `<name>_1`..`<name>_5` as the source of truth.
- Keep `level` consistent with the tree: a child must have `level` = parent level + 1 and the right `parent`. A wrong level makes the node vanish from the dropdown and breaks the restore of `value`.
- `0` is the "top" marker for `parent`, so a real node must not use id `0`.
- `levels` decides the depth: more levels in `nodes` than in `levels` are never shown.
- A branch may stop early (a node with no children): the chain then never reports `Complete` and the code is shorter. If your rule says a code must be full depth, check `complete` in the event (or the number of `level` in the posted id) and validate on the server.
- The finished code is composed in the browser and in PHP with the same rule, but the server must not trust `<name>_code`.
- The Add button only navigates; the new node has to be created on another screen, which then returns to a page that renders the chain with the right `value`.
- `inactive=false` hides inactive nodes but keeps the one currently chosen; set `inactive` on a master/admin screen where inactive nodes must be seen and fixed.
- `disabled` renders no options at all, so the closed dropdowns do NOT show the chosen names (see the oddity below); the code line, the segment boxes and the hidden values are still correct. For a locked document that must show readable names, render the names yourself (for example with `x-dl::field.display`).
- Do not hand-write the dropdowns with `x-dl::field.select` for this case; the chain keeps parent-child options, segments and the code in sync.
- No lint rule is specific to this object (the lint rules apply to the Blade of the screen that uses it, for example `L001` for raw HTML).
- Code oddity (not fixed): the `Add` label is written literally in the inline view of `src/Components/CodeChain.php` (`...</span>Add</a>`) and is not passed through `Lang::text`, so it stays English in every language, unlike the other texts of the object.
- Code oddity (not fixed): `resources/assets/js/components/code-chain.js` (`pick()`) refills the lower dropdown with `Devlib.h.setOptions`, which resets the shown text to the select `data-empty`. That is the placeholder rendered at load time, so a level that was rendered disabled keeps showing `Pick <previous level> first` after it becomes enabled, until the user picks something. Found by reading the code, not run in a browser.
- Code oddity (not fixed): the browser replaces only the FIRST `{id}` (in `navigate`) and the first `{level}` / `{parent}` (in `add-url`) with `String.replace`, while PHP (`addHref()`) replaces every occurrence with `str_replace`. A URL that repeats a placeholder is correct on load and wrong after a pick.
- Code oddity (not fixed): with `disabled`, `CodeChain::options()` returns `[]` for every level (`open()` is false), and the select view shows `$opts[$value] ?? $empty`, so each locked dropdown shows its placeholder (level 1 reads `Pick  first` with an empty level name; the others read `Pick <previous level> first`) instead of the chosen node name, although the hidden id is still posted. Found by reading the code.

## Example

From the catalog demo (`site/resources/views/demos/code-chain.blade.php`; name, nodes and codes are fictional). Nodes are `[id, parent (0 = top), level, name, code piece, active]`; `value` 9 restores `Paper > Art carton > 210gr > 65 X 100 CM`, so the code starts as `PCC02`.

```blade
<x-dl::code-chain name="category" code-label="Item Code" :value="9" add-url="#new={level}&parent={parent}"
    :levels="['Category 1', 'Category 2', 'Category 3', 'Category 4', 'Category 5']"
    :nodes="[
        [1, 0, 1, 'Paper', 'P', 1], [2, 0, 1, 'Ink', 'I', 1],
        [4, 1, 2, 'Art carton', 'C', 1], [5, 1, 2, 'Duplex', 'D', 1],
        [7, 4, 3, '210gr', 'C', 1], [8, 4, 3, '230gr', 'D', 1],
        [9, 7, 4, '65 X 100 CM', '02', 1], [10, 7, 4, '79 X 109 CM', '03', 1],
        [11, 9, 5, 'GOLDEN', '002', 1], [12, 10, 5, 'GOLDEN', '003', 1],
    ]" />
```

Master screen (each pick opens that row's form; inactive nodes are visible), shape only:

```blade
<x-dl::code-chain name="category" code-label="Item Code" :value="$category->id ?? null" :nodes="$nodes" :levels="$levels"
    navigate="/materials/categories/{id}" add-url="/materials/categories/new?level={level}&parent={parent}" inactive />
```

Read-only (document Confirmed):

```blade
<x-dl::code-chain name="category" :value="$item->category_id" :nodes="$nodes" :levels="$levels" disabled />
```

## Related

- `x-dl::field.select`: the dropdown used for every level; the chain only adds linking, segments and code.
- `x-dl::form`, `x-dl::form.row`: the form shell that posts the hidden inputs; the chain is listed in the `form` dictionary group.
- `x-dl::field.lookup`, `x-dl::lookup-grid`: pick one record from a flat list instead of a tree.
- `x-dl::field.masked`: a code typed against a fixed pattern (when the code is typed, not composed).
- `x-dl::barcode`, `x-dl::qr`, `x-dl::code-generator`: turn the finished code into a printable symbol.
- `skills/devlib/components.md`: generated prop list.
