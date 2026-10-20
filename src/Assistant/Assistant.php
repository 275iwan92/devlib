<?php

namespace Devlib\Assistant;

use Devlib\Engine\EngineException;
use Devlib\Lang;

// Mesin jawab asisten aplikasi (kontrak baku: skills/devlib/assistant.md). Opt-in: tidak terdaftar otomatis.
// Urutan keputusan:
//   1. frasa Tool cocok -> gerbang hak (modul + level) -> Tool menjawab        (angka hanya dari Tool, tidak dari model)
//   2. kata "cara / how" + topik bantuan cocok -> langkah dari Knowledge
//   3. nama layar cocok -> layar (Open + How to use)
//   4. topik bantuan cocok -> kegunaan topik
//   5. belum yakin dan model aktif -> model memilih SATU dari calon terdekat
//   6. tidak tahu (UNKNOWN), tercatat di riwayat
// Teks bantuan hanya kutipan; model bahasa tidak pernah menyusun isi jawaban.
final class Assistant
{
    private const SURE = 0.7;

    private const SURE_HOW = 0.45;

    private const MAYBE = 0.34;

    private const STOP = ['the', 'a', 'an', 'to', 'of', 'in', 'on', 'for', 'is', 'my', 'me', 'please', 'open', 'show', 'how', 'do', 'i', 'can',
        'cara', 'buka', 'tampilkan', 'dan', 'di', 'ke', 'yang', 'untuk', 'saya', 'tolong', 'bagaimana', 'apa'];

    /** @param list<Tool> $tools */
    public function __construct(
        private Navigator $navigator,
        private Knowledge $knowledge,
        private array $tools = [],
        private LanguageModel $model = new NullModel,
        private ?HistoryStore $history = null,
    ) {}

    public function ask(Asker $asker, string $question): Reply
    {
        $question = trim(mb_substr($question, 0, 500));
        $reply = $this->answer($asker, $question);
        $reply->id = $this->history?->store($asker, $question, $reply);

        return $reply;
    }

    /** Bantuan satu topik (tombol "How to use" di jawaban layar). */
    public function topic(Asker $asker, string $key): Reply
    {
        foreach ($this->knowledge->topics($asker) as $t) {
            if ($t['key'] === $key) {
                return $this->howTo($t);
            }
        }

        return $this->unknown();
    }

    /** @return list<array{id: int, q: string, reply: array<string, mixed>, feedback: ?int}> */
    public function history(Asker $asker, int $limit = 30): array
    {
        return $this->history?->recent($asker, $limit) ?? [];
    }

    public function feedback(Asker $asker, int $id, int $value): bool
    {
        return $this->history?->feedback($asker, $id, $value) ?? false;
    }

    /** Contoh pertanyaan untuk laci asisten: frasa pertama tiap Tool. */
    public function suggestions(): array
    {
        return array_values(array_filter(array_map(fn (Tool $t) => $t->phrases()[0] ?? null, $this->tools)));
    }

    private function answer(Asker $asker, string $question): Reply
    {
        if ($tool = $this->tool($question)) {
            return $this->run($asker, $tool, $question);
        }
        $query = $this->tokens($question);
        if (! $query) {
            return $this->unknown();
        }
        $how = (bool) preg_match('/\b(how|cara|bagaimana)\b/i', $question);
        $screens = $this->navigator->screens($asker);
        $topics = $this->knowledge->topics($asker);
        $screen = $this->best($query, $screens, fn ($s) => $s['label'].' '.($s['path'] ?? '').' '.($s['words'] ?? ''));
        $topic = $this->best($query, $topics, fn ($t) => $t['title'].' '.($t['words'] ?? ''));

        if ($how && $topic['score'] >= self::SURE_HOW) {
            return $this->howTo($topic['item']);
        }
        if ($screen['score'] >= self::SURE && $screen['score'] >= $topic['score']) {
            return $this->screen($screen['item']);
        }
        if ($topic['score'] >= self::SURE) {
            return $this->about($topic['item']);
        }

        $near = $this->near($query, $screens, $topics);
        if ($near && $this->model->available()) {
            $pick = $this->model->choose($question, array_map(fn ($c) => $c['say'], $near), $this->history ? $this->recentPairs($asker) : []);
            if ($pick !== null && isset($near[$pick])) {
                return $near[$pick]['kind'] === 'screen' ? $this->screen($near[$pick]['item']) : $this->howTo($near[$pick]['item']);
            }
        }

        return $this->unknown();
    }

    private function tool(string $question): ?Tool
    {
        $q = mb_strtolower($question);
        foreach ($this->tools as $tool) {
            foreach ($tool->phrases() as $phrase) {
                if (str_contains($q, mb_strtolower($phrase))) {
                    return $tool;
                }
            }
        }

        return null;
    }

    private function run(Asker $asker, Tool $tool, string $question): Reply
    {
        if (($tool->module() !== null && ! $asker->can($tool->module())) || $asker->level() < $tool->minLevel()) {
            return new Reply(Reply::DENIED, Lang::text('Not available for your account'),
                Lang::text('This answer follows the access rights of your account: it needs access to its module and a high enough security level.'), key: $tool->key());
        }
        try {
            $reply = $tool->answer($asker, $question);
        } catch (EngineException $e) {
            return new Reply(Reply::UNKNOWN, Lang::text('Could not work that out'), $e->getMessage(), key: $tool->key());
        }
        $reply->key ??= $tool->key();

        return $reply;
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $m);

        return array_values(array_diff(array_unique($m[0]), self::STOP));
    }

    // Skor 0..1 = bagian kata pertanyaan yang ditemukan di teks sasaran.
    private function score(array $query, string $target): float
    {
        $hay = $this->tokens($target);
        $hit = count(array_filter($query, fn ($w) => in_array($w, $hay, true)));

        return $query ? $hit / count($query) : 0.0;
    }

    /** @return array{score: float, item: ?array<string, mixed>} */
    private function best(array $query, array $items, callable $text): array
    {
        $best = ['score' => 0.0, 'item' => null];
        foreach ($items as $item) {
            $score = $this->score($query, $text($item));
            if ($score > $best['score']) {
                $best = ['score' => $score, 'item' => $item];
            }
        }

        return $best;
    }

    /** @return array<string, array{kind: string, item: array<string, mixed>, say: string}> calon terdekat (maks 5) */
    private function near(array $query, array $screens, array $topics): array
    {
        $out = [];
        foreach ($screens as $s) {
            if ($this->score($query, $s['label'].' '.($s['words'] ?? '')) >= self::MAYBE) {
                $out['screen:'.$s['key']] = ['kind' => 'screen', 'item' => $s, 'say' => $s['label'].' — '.($s['about'] ?? '')];
            }
        }
        foreach ($topics as $t) {
            if ($this->score($query, $t['title'].' '.($t['words'] ?? '')) >= self::MAYBE) {
                $out['topic:'.$t['key']] = ['kind' => 'topic', 'item' => $t, 'say' => $t['title'].' — '.($t['purpose'] ?? '')];
            }
        }

        return array_slice($out, 0, 5, true);
    }

    private function recentPairs(Asker $asker): array
    {
        return array_map(fn ($h) => ['q' => $h['q'], 'a' => (string) ($h['reply']['title'] ?? '')], $this->history->recent($asker, 4));
    }

    private function screen(array $s): Reply
    {
        $reply = (new Reply(Reply::SCREEN, Lang::text('Screen found'), $s['label'], note: $s['about'] ?? null, key: $s['key'],
            rows: isset($s['path']) ? [[Lang::text('Menu'), (string) $s['path']]] : []))
            ->open(strtr(Lang::text('Open :screen'), [':screen' => $s['label']]), $s['url'], $s['key'], $s['icon'] ?? null);

        return empty($s['help']) ? $reply : $reply->topic(Lang::text('How to use'), $s['help']);
    }

    private function howTo(array $t): Reply
    {
        return new Reply(Reply::HELP, strtr(Lang::text('How to use: :screen'), [':screen' => $t['title']]), $t['purpose'] ?? null,
            steps: $t['steps'] ?? [], note: strtr(Lang::text('Source: Help, :screen'), [':screen' => $t['title']]), key: $t['key']);
    }

    private function about(array $t): Reply
    {
        return (new Reply(Reply::HELP, $t['title'], $t['purpose'] ?? null, note: strtr(Lang::text('Source: Help, :screen'), [':screen' => $t['title']]), key: $t['key']))
            ->topic(Lang::text('How to use'), $t['key']);
    }

    private function unknown(): Reply
    {
        return new Reply(Reply::UNKNOWN, Lang::text("I don't know that yet"), Lang::text('I only answer from the screens, the Help and the calculations of this application. Try another wording.'));
    }
}
