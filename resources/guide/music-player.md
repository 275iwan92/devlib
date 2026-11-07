## Overview

`<x-dl::music-player>` is a small pill with background music controls: a Play / Pause button, a Mute button, a volume slider and the credit (title, author, license) of the track that is playing. It is silent by default: nothing plays until the user presses Play, on every page load (the browser blocks sound before the user acts anyway). Volume, mute, track and position are remembered on this device; the on / off state is not, so a saved earlier choice never starts the music by itself.

It plays a list of tracks one after another (a single track loops), fades in and out over one second, pauses while the browser tab is hidden and continues from the saved position. The audio element belongs to the component, so music keeps playing while the user opens other tabs of the same shell page.

Pick another object when you need a sound effect on an action (use the browser Audio API in the project), a video (`<video>`), or a list of files to download (`x-dl::file-list`).

`x-dl::greeting-card` places this player in its button row when `board['music']` is given.

### Generated markup

```html
<div class="dl-music" data-dl="x-dl::music-player tracks=1" data-dl-music data-key="dl-music" data-base="/audio/" data-volume="25" data-tracks='[{"file":"a.mp3","title":"Title","author":"Author","license":"CC BY 4.0"}]' data-l-play="Play music" ...>
  <button type="button" class="dl-music__btn" data-dl-music-play aria-pressed="false" aria-label="Play music"><i class="fa-solid fa-play"></i></button>
  <button type="button" class="dl-music__btn" data-dl-music-mute aria-pressed="false" aria-label="Mute music" hidden>...</button>   <!-- shown after Play -->
  <input type="range" class="dl-music__vol" min="0" max="100" data-dl-music-vol aria-label="Music volume" hidden>              <!-- shown after Play -->
  <span class="dl-music__credit" data-dl-music-credit hidden></span>                                                           <!-- shown after Play -->
</div>
```

With no tracks nothing is rendered.

## Props

| Prop | Type, default | Meaning and effect |
|---|---|---|
| `tracks` | `array`, `[]` | List of `['file' => 'a.mp3', 'title' => 'Name', 'author' => 'Who', 'url' => ?link of the author, 'license' => ?'CC BY 4.0', 'license_url' => ?link]`. Empty = nothing is rendered. |
| `base` | `string`, `'/audio'` | Folder URL of the audio files; the file name is appended. |
| `storage-key` | `string`, `'dl-music'` | `localStorage` key of the saved choice (`mute`, `vol`, `i`, `pos`). Per browser, not per user account. |
| `volume` | `int`, `25` | Starting volume 0-100 for a browser that has no saved choice. |

## Behavior

- Play turns the music on; Pause turns it off. Every page load starts silent; only Play (by the user) starts sound.
- Volume and Mute apply at once. The credit shows `♪ title — author · license` while on; every track shows its credit (license duty of CC BY).
- A track that ends starts the next one; after the last one the first returns.
- Hidden browser tab = paused; visible again = resumes.
- Button texts are translated through `Lang::text` (`Play music`, `Pause music`, `Mute music`, `Unmute music`, `Music volume`) and the buttons carry `aria-label` and `aria-pressed`.

## Data and events

Reads no database. Writes only `localStorage[storage-key]`. No events are emitted.

## Rules and pitfalls

- Use only music you may play, and keep the author and license in the track data; the credit must stay visible while the track plays.
- Audio files are large: put one light file (a few MB) in the project, not an album.
- The saved choice is per browser. For a per-account preference the project must save it itself.
- Two players with the same `storage-key` on one page share the saved choice but play separately; use one player per page.

## Example

```blade
<x-dl::music-player :tracks="[['file' => 'calm.mp3', 'title' => 'Calm', 'author' => 'Sample Author', 'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0/']]" base="/audio" />
```

## Related

`x-dl::greeting-card` (places the player), `x-dl::clock`, `x-dl::weather`.
