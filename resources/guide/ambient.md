# x-dl::ambient

## Overview


Musik latar ambient yang **dibuat di peramban** (Web Audio API): pad akor lembut berganti tiap 8 detik. Tanpa berkas audio dan tanpa hak cipta.

```blade
<x-dl::ambient label="Background music" />
```

- Bawaan **diam**: suara mulai hanya setelah pengguna menekan **Play** (peramban memblokir autoplay).
- **Pause** menghentikan dengan fade; **Mute** membisukan dan pilihannya diingat per perangkat.
- Berhenti sendiri saat tab tersembunyi. Prop: `label`, `volume` (0-100, bawaan 18), `storageKey`.
- Untuk musik berkas dengan kredit pencipta pakai `x-dl::music-player`.

Use it as quiet background sound for a long guide or video screen when the project has no music file. Pick another object when you have real tracks with credits: `x-dl::music-player`.

Generated markup:

```html
<div class="dl-ambient" data-dl="x-dl::ambient" data-dl-ambient data-volume="18" data-key="dl-ambient" role="group" aria-label="Background music">
  <i class="fa-solid fa-music"></i><span class="dl-ambient__label">Background music</span>
  <button class="dl-ambient__play" data-am-play aria-pressed="false">Play</button>
  <button class="dl-ambient__mute" data-am-mute aria-pressed="false">Mute</button>
</div>
```

## Props

Constructor of `Devlib\Components\Ambient` (3 props).

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `label` | `string`, `'Background music'` | Text next to the music icon and the accessible name of the group (the visible text is translated through the dictionary). |
| `volume` | `int`, `18` | Loudness from 0 to 100, written to `data-volume`; a value of 0 or a non-number falls back to 18. |
| `storage-key` | `string`, `'dl-ambient'` | Browser storage key (`localStorage`) that remembers the Mute choice. Use another key when two ambient players on one site must remember separately. |

## Behavior

- Four chords (Am9, Fmaj7, Cmaj7, G6) change every 8 seconds with a 3 second glide; each note is two slightly detuned oscillators through a slowly moving low-pass filter.
- Play starts the sound (the audio context is created on the first press, because browsers block autoplay); starting and stopping fade over 1.2 seconds. Pause fades out and then suspends the audio context.
- Mute fades the sound to 0 while the chords keep running; pressing it again fades back to `volume`. The choice is saved on this device and is read when the page loads.
- It stops by itself when the tab becomes hidden and does not resume by itself.
- If the browser has no Web Audio API, Play does nothing.
- Only the labels Play and Mute and the `label` text are translated.

## Data and events

No request and no custom event. The only data kept is `localStorage[storage-key]` (`mute` or `on`). Reading and writing storage is wrapped in try/catch, so private windows still work (the choice is just not kept).

## Rules and pitfalls

- Never expect autoplay: the first sound always needs a click on Play.
- Several players on one page play together; place one per screen.
- The volume is a ceiling for a quiet background (default 18); do not raise it for narration or video screens.
- Mute is remembered but Play is not: every page load starts silent.

## Example

```blade
<x-dl::ambient label="Background music" :volume="15" storage-key="guide-ambient" />
```

## Related

- `x-dl::music-player`: plays real music files with credits.
- `x-dl::deck`: a presentation that this background sound suits.
