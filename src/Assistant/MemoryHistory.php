<?php

namespace Devlib\Assistant;

// Riwayat di memori proses: untuk tes dan situs acuan. Aplikasi nyata mengikat HistoryStore berbasis database.
final class MemoryHistory implements HistoryStore
{
    /** @var array<int, array{user: string, q: string, reply: array<string, mixed>, feedback: ?int}> */
    private array $rows = [];

    public function store(Asker $asker, string $question, Reply $reply): int
    {
        $this->rows[] = ['user' => $asker->id(), 'q' => mb_substr($question, 0, 500), 'reply' => $reply->toArray(), 'feedback' => null];

        return array_key_last($this->rows) + 1;
    }

    public function recent(Asker $asker, int $limit = 30): array
    {
        $mine = [];
        foreach ($this->rows as $i => $row) {
            if ($row['user'] === $asker->id()) {
                $mine[] = ['id' => $i + 1, 'q' => $row['q'], 'reply' => $row['reply'], 'feedback' => $row['feedback']];
            }
        }

        return array_slice($mine, -$limit);
    }

    public function feedback(Asker $asker, int $id, int $value): bool
    {
        if (! isset($this->rows[$id - 1]) || $this->rows[$id - 1]['user'] !== $asker->id()) {
            return false;
        }
        $this->rows[$id - 1]['feedback'] = $value ?: null;

        return true;
    }
}
