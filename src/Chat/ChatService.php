<?php

namespace Devlib\Chat;

// Logika chat satu lawan satu yang tidak bergantung pada tabel / rute aplikasi (kontrak baku: skills/devlib/chat.md).
// Aplikasi mengikat Directory, ChatStore, Presence, Uploads, lalu membuat rute tipis yang memanggil method di sini; hasilnya
// berbentuk persis yang dibaca x-dl::chat. Opt-in: tidak terdaftar otomatis.
// Aturan: hanya dua peserta yang melihat pesan; tidak bisa chat ke diri sendiri / orang di luar Directory; pesan kosong ditolak.
class ChatService
{
    public function __construct(
        private Directory $directory,
        private ChatStore $store,
        private Presence $presence,
        private ?Uploads $uploads = null,
        private int $maxChars = 4000,
        private int $maxMb = 25,
        private int $pageSize = 50,
    ) {}

    /** Data awal layar: <x-dl::chat :me :people :convos :api>. $api = alamat rute aplikasi (poll, messages, upload, send, csrf, ...). */
    public function boot(string|int $me, array $api = []): array
    {
        $this->presence->touch($me);
        $self = $this->person($me);
        $convos = [];
        $people = [];
        foreach ($this->directory->people() as $p) {
            if ((string) $p->id === (string) $me) {
                continue;
            }
            $people[] = $this->personRow($p);
            $msgs = $this->store->between($me, $p->id, null, $this->pageSize);
            if ($msgs) {
                $convos[$p->id] = ['unread' => $this->store->unread($me, $p->id), 'read' => $this->store->readBy($p->id, $me),
                    'msgs' => array_map(fn (Message $m) => $m->toArray($me), $msgs)];
            }
        }

        return ['me' => ['id' => $self->id, 'name' => $self->name, 'photo' => $self->photo], 'people' => $people, 'convos' => $convos,
            'api' => $api + ['max_mb' => $this->maxMb, 'last_id' => $this->lastId($me)]];
    }

    /** Denyut + pesan baru. $open = percakapan yang sedang dilihat (langsung dibaca). */
    public function poll(string|int $me, int $after = 0, string|int|null $open = null): array
    {
        $this->presence->touch($me);
        $msgs = [];
        foreach ($this->store->after($me, $after) as $m) {
            $peer = (string) $m->from === (string) $me ? $m->to : $m->from;
            $msgs[] = $m->toArray($me) + ['peer' => $peer];
        }
        if ($open !== null && $this->directory->find($open)) {
            $this->read($me, $open);
        }
        $read = [];
        $presence = [];
        foreach ($this->directory->people() as $p) {
            if ((string) $p->id !== (string) $me) {
                $read[$p->id] = $this->store->readBy($p->id, $me);
                $presence[$p->id] = [$this->presence->isOnline($p->id), $this->presence->lastSeen($p->id)];
            }
        }

        return ['msgs' => $msgs, 'last_id' => max($after, $this->lastId($me)), 'read' => $read, 'presence' => $presence];
    }

    /** Satu halaman pesan lama dengan $peer (terlama dulu). */
    public function messages(string|int $me, string|int $peer, ?int $before = null): array
    {
        $this->peer($me, $peer);
        $msgs = $this->store->between($me, $peer, $before, $this->pageSize);

        return ['msgs' => array_map(fn (Message $m) => $m->toArray($me), $msgs), 'more' => $msgs && $this->store->hasOlder($me, $peer, $msgs[0]->id),
            'read' => $this->store->readBy($peer, $me)];
    }

    public function upload(string|int $me, string $uid, int $offset, string $chunk): array
    {
        $received = $this->uploads()->receive($uid, $offset, $chunk);
        if ($received > $this->maxMb * 1048576) {
            throw new ChatException("Attachment is larger than {$this->maxMb} MB.");
        }

        return ['received' => $received];
    }

    /** Kirim teks dan / atau lampiran yang sudah di-upload ($upload = uid, $name = nama file asli). */
    public function send(string|int $me, string|int $to, string $text, ?string $upload = null, ?string $name = null): array
    {
        $this->peer($me, $to);
        $text = trim($text);
        if (mb_strlen($text) > $this->maxChars) {
            throw new ChatException("Message is longer than {$this->maxChars} characters.");
        }
        $file = $upload === null ? null : $this->uploads()->complete($upload, (string) $name, $me);
        if ($text === '' && ! $file) {
            throw new ChatException('Message is empty.');
        }

        return ['msg' => $this->store->append($me, $to, $text, $file)->toArray($me)];
    }

    /** $reader membuka percakapan dengan $peer: semua pesan yang masuk ditandai dibaca. */
    public function read(string|int $reader, string|int $peer): void
    {
        $last = 0;
        foreach ($this->store->between($peer, $reader, null, $this->pageSize) as $m) {
            if ((string) $m->from === (string) $peer) {
                $last = max($last, $m->id);
            }
        }
        if ($last > 0) {
            $this->store->markRead($reader, $peer, $last);
        }
    }

    public function signOut(string|int $me): void
    {
        $this->presence->signOut($me);
    }

    private function uploads(): Uploads
    {
        return $this->uploads ?? throw new ChatException('Attachments are not enabled in this application.');
    }

    private function person(string|int $id): Person
    {
        return $this->directory->find($id) ?? throw new ChatException("Unknown user [{$id}].");
    }

    private function peer(string|int $me, string|int $peer): Person
    {
        if ((string) $me === (string) $peer) {
            throw new ChatException('You cannot chat with yourself.');
        }

        return $this->person($peer);
    }

    private function personRow(Person $p): array
    {
        return ['id' => $p->id, 'name' => $p->name, 'role' => $p->role, 'photo' => $p->photo,
            'online' => $this->presence->isOnline($p->id), 'seen' => $this->presence->lastSeen($p->id)];
    }

    private function lastId(string|int $me): int
    {
        $all = $this->store->after($me, 0);

        return $all ? end($all)->id : 0;
    }
}
