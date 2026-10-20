# Chat antar-user (model untuk proyek) di DevLib

Acuan baku untuk SEMUA proyek yang punya chat antar user. Arah warisan: **DevLib membuat, proyek mewarisi** (`inheritance.md`). Opt-in: tidak ada yang terdaftar otomatis. Layarnya sudah ada: `x-dl::chat` (`src/Components/Chat.php`, bentuk data & alamat API ada di komentar kepalanya). Bagian server: `src/Chat/`. Tes: `site/tests/Feature/AssistantChatContractTest.php`. Contoh hidup: halaman katalog "Chat model" (`site/app/Support/ModelDemo.php`, data fiktif).

## Prinsip

1. **DevLib memegang logika, proyek memegang penyimpanan.** `ChatService` tidak tahu tabel, rute, atau user proyek; ia bicara lewat empat kontrak yang diikat proyek.
2. **Hanya dua peserta yang melihat pesan dan lampiran.** Satu pasangan = satu kanal (`Channel::key()` simetris, simpan sebagai kunci UNIQUE).
3. **Tidak ada chat ke diri sendiri atau orang di luar `Directory`; pesan kosong dan kepanjangan ditolak** dengan `ChatException` bernama (bukan `false`/`null`).
4. **Online = sedang login + denyut terakhir masih dalam batas waktu.** Tiap `poll` = denyut; logout memanggil `signOut()` supaya langsung offline.
5. **Dibaca = penanda per peserta**, hanya maju. Centang: terkirim, diterima (denyut lawan lebih baru), dibaca.
6. **Lampiran berpotong** (`Uploads::receive` lalu `complete`) supaya tidak bergantung `upload_max_filesize`; file di disk privat, bukan database; batas ukuran `maxMb`.
7. **Permintaan latar (`poll`, `upload`) tidak boleh menulis sesi** (supaya flash/old input layar lain dan kedaluwarsa sesi tidak terganggu) — pastikan di rute proyek.
8. **Mati/putus tidak menghilangkan pesan:** semuanya dari penyimpanan; dorongan seketika (WebSocket) hanya percepatan opsional dan bukan bagian kontrak ini.

## Kontrak (`Devlib\Chat`)

| Kelas | Peran | Diikat proyek? |
|---|---|---|
| `Directory` (interface) | siapa yang bisa di-chat: `people()`, `find($id)` (`Person`: id, name, role, photo) | ya |
| `ChatStore` (interface) | pesan: `append`, `between`, `after`, `hasOlder`, `markRead`, `readBy`, `unread` | ya |
| `Presence` (interface) | kehadiran: `touch`, `signOut`, `isOnline`, `lastSeen` | ya |
| `Uploads` (interface) | lampiran berpotong: `receive`, `complete` -> `Attachment` | ya, opsional |
| `Message`, `Attachment`, `Person`, `Channel` | nilai data; `Message::toArray($viewer)` = bentuk MSG `x-dl::chat` | - |
| `ChatService` | `boot`, `poll`, `messages`, `upload`, `send`, `read`, `signOut` | dibuat proyek |
| `ChatException` | satu-satunya kegagalan Chat | - |
| `MemoryChat` | `ChatStore` + `Presence` + `Uploads` di memori, untuk tes dan situs acuan | - |

## Memasang di proyek

```php
$this->app->singleton(\Devlib\Chat\ChatService::class, fn ($app) => new \Devlib\Chat\ChatService(
    directory: $app->make(MyDirectory::class),     // implements Directory (user aktif proyek)
    store: $app->make(MyChatStore::class),          // implements ChatStore (tabel + indeks milik proyek)
    presence: $app->make(MyPresence::class),        // implements Presence
    uploads: $app->make(MyUploads::class),          // opsional; implements Uploads (disk privat)
));
```

Rute tipis milik proyek (gerbang `auth` proyek, sesi baca-saja untuk `poll`/`upload`), tiap rute satu baris ke service dan `ChatException` -> 422:

| Rute | Panggilan |
|---|---|
| `GET poll?after&open` | `poll($me, $after, $open)` |
| `GET messages?peer&before` | `messages($me, $peer, $before)` |
| `POST upload` (`uid`, `offset`, `chunk`) | `upload($me, $uid, $offset, $chunk)` |
| `POST send` (`to`, `text`, `upload`, `name`) | `send($me, $to, $text, $upload, $name)` |
| logout | `signOut($me)` |

Layar: `$chat = app(ChatService::class)->boot(auth()->id(), $api)` lalu `<x-dl::chat :me :people :convos :api>` (atau prop `chat` pada `x-dl::shell`). `$api` = alamat rute di atas + `csrf`, `poll_seconds`.

## Jangan

- Menaruh nama proyek / data client di `src/Chat` atau contoh `site/`; adaptor yang menyebut tabel proyek hidup di proyek.
- Menyajikan lampiran lewat url publik; selalu lewat rute proyek yang memeriksa dua peserta.
- Menambah grup/voice/hapus-ubah pesan di proyek bila bisa jadi kontrak opt-in baru di DevLib (belum ada: grup, voice, hapus/ubah pesan, pemberitahuan di luar aplikasi).
