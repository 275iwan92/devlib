# x-dl::ambient

Musik latar ambient yang **dibuat di peramban** (Web Audio API): pad akor lembut berganti tiap 8 detik. Tanpa berkas audio dan tanpa hak cipta.

```blade
<x-dl::ambient label="Background music" />
```

- Bawaan **diam**: suara mulai hanya setelah pengguna menekan **Play** (peramban memblokir autoplay).
- **Pause** menghentikan dengan fade; **Mute** membisukan dan pilihannya diingat per perangkat.
- Berhenti sendiri saat tab tersembunyi. Prop: `label`, `volume` (0-100, bawaan 18), `storageKey`.
- Untuk musik berkas dengan kredit pencipta pakai `x-dl::music-player`.
