<?php

namespace Devlib\Chat;

use Closure;

// Penyimpanan, kehadiran, dan upload di memori proses: untuk tes dan situs acuan. Aplikasi nyata mengikat ChatStore /
// Presence / Uploads berbasis database + disk privat. $clock bisa diganti tes (epoch detik).
final class MemoryChat implements ChatStore, Presence, Uploads
{
    /** @var list<Message> */
    private array $messages = [];

    /** @var array<string, int> "<reader>|<peer>" => id terakhir pesan dari peer yang sudah dibaca reader */
    private array $reads = [];

    /** @var array<string, array{online: bool, at: int}> */
    private array $seen = [];

    /** @var array<string, string> */
    private array $chunks = [];

    public function __construct(private int $onlineSeconds = 60, private ?Closure $clock = null) {}

    private function now(): int
    {
        return $this->clock ? ($this->clock)() : time();
    }

    public function append(string|int $from, string|int $to, string $text, ?Attachment $attachment = null): Message
    {
        return $this->messages[] = new Message(count($this->messages) + 1, $from, $to, $text, $this->now(), $attachment);
    }

    public function between(string|int $a, string|int $b, ?int $before, int $limit): array
    {
        $key = Channel::key($a, $b);
        $rows = array_values(array_filter($this->messages, fn (Message $m) => Channel::key($m->from, $m->to) === $key && ($before === null || $m->id < $before)));

        return array_slice($rows, -$limit);
    }

    public function after(string|int $user, int $afterId): array
    {
        return array_values(array_filter($this->messages, fn (Message $m) => $m->id > $afterId
            && ((string) $m->from === (string) $user || (string) $m->to === (string) $user)));
    }

    public function hasOlder(string|int $a, string|int $b, int $beforeId): bool
    {
        return (bool) $this->between($a, $b, $beforeId, 1);
    }

    public function markRead(string|int $reader, string|int $peer, int $upToId): void
    {
        $k = "{$reader}|{$peer}";
        $this->reads[$k] = max($this->reads[$k] ?? 0, $upToId);
    }

    public function readBy(string|int $peer, string|int $reader): int
    {
        return $this->reads["{$peer}|{$reader}"] ?? 0;
    }

    public function unread(string|int $me, string|int $peer): int
    {
        $read = $this->reads["{$me}|{$peer}"] ?? 0;

        return count(array_filter($this->messages, fn (Message $m) => (string) $m->from === (string) $peer && (string) $m->to === (string) $me && $m->id > $read));
    }

    public function touch(string|int $user): void
    {
        $this->seen[(string) $user] = ['online' => true, 'at' => $this->now()];
    }

    public function signOut(string|int $user): void
    {
        $this->seen[(string) $user] = ['online' => false, 'at' => $this->now()];
    }

    public function isOnline(string|int $user): bool
    {
        $s = $this->seen[(string) $user] ?? null;

        return $s && $s['online'] && $this->now() - $s['at'] <= $this->onlineSeconds;
    }

    public function lastSeen(string|int $user): ?int
    {
        return $this->seen[(string) $user]['at'] ?? null;
    }

    public function receive(string $uid, int $offset, string $chunk): int
    {
        $have = $this->chunks[$uid] ?? '';
        if ($offset !== strlen($have)) {
            throw new ChatException("Upload [{$uid}] chunk is out of order: expected offset ".strlen($have).", got {$offset}.");
        }

        return strlen($this->chunks[$uid] = $have.$chunk);
    }

    public function complete(string $uid, string $name, string|int $owner): Attachment
    {
        $data = $this->chunks[$uid] ?? throw new ChatException("Unknown upload [{$uid}].");
        unset($this->chunks[$uid]);

        return new Attachment($uid, $name, strlen($data), 'memory://'.$uid.'/'.rawurlencode($name), (bool) preg_match('/\.(png|jpe?g|gif|webp)$/i', $name));
    }
}
