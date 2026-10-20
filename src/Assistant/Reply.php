<?php

namespace Devlib\Assistant;

// Satu jawaban asisten sebagai data; bentuk toArray() = bentuk yang dibaca laci "AI Assistant" di x-dl::shell (prop assistant).
//   SCREEN layar ditemukan    HELP kutipan bantuan    NUMBER hasil Tool    DENIED di luar hak penanya    UNKNOWN tidak ada jawabannya
//   OUT_OF_SCOPE pertanyaan di luar program (kalimat tetap dari aplikasi; opt-in lewat Assistant::$outOfScope, bukan bahan menambah Help)
// actions: ['label','url','code','icon'] = buka sebagai tab | ['label','topic'] = tanya bantuan topik itu.
final class Reply
{
    public const SCREEN = 'SCREEN';

    public const HELP = 'HELP';

    public const NUMBER = 'NUMBER';

    public const DENIED = 'DENIED';

    public const UNKNOWN = 'UNKNOWN';

    public const OUT_OF_SCOPE = 'OUT_OF_SCOPE';

    /**
     * @param  list<array{0: string, 1: string}>  $rows  rincian [label, nilai]
     * @param  list<string>  $steps  langkah / alasan berurutan
     * @param  list<array<string, ?string>>  $actions
     */
    public function __construct(
        public string $kind,
        public string $title,
        public ?string $text = null,
        public ?string $big = null,
        public array $rows = [],
        public array $steps = [],
        public ?string $note = null,
        public array $actions = [],
        public ?string $key = null,
        public ?int $id = null,
    ) {}

    public function open(string $label, string $url, string $code, ?string $icon = null): self
    {
        $this->actions[] = ['label' => $label, 'url' => $url, 'code' => $code, 'icon' => $icon];

        return $this;
    }

    public function topic(string $label, string $topicKey): self
    {
        $this->actions[] = ['label' => $label, 'topic' => $topicKey];

        return $this;
    }

    public function answered(): bool
    {
        return ! in_array($this->kind, [self::UNKNOWN, self::OUT_OF_SCOPE], true);
    }

    public function toArray(): array
    {
        return array_filter(['kind' => $this->kind, 'title' => $this->title, 'text' => $this->text, 'big' => $this->big, 'rows' => $this->rows,
            'steps' => $this->steps, 'note' => $this->note, 'actions' => $this->actions], fn ($v) => $v !== null && $v !== []);
    }
}
