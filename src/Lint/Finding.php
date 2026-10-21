<?php

namespace Devlib\Lint;

// Satu temuan penegak aturan baku: kode stabil, pesan, tingkat (error = melanggar aturan baku, warn = menyimpang/perlu dicek, info = saran).
// Dipakai oleh: GridRules, FormRules, ViewLinter, dan perintah devlib:lint.
final class Finding
{
    public const ERROR = 'error';

    public const WARN = 'warn';

    public const INFO = 'info';

    public function __construct(
        public readonly string $code,
        public readonly string $message,
        public readonly string $level = self::WARN,
        public readonly ?string $where = null,
    ) {}

    public function at(string $where): self
    {
        return new self($this->code, $this->message, $this->level, $where);
    }

    public function toArray(): array
    {
        return ['code' => $this->code, 'level' => $this->level, 'message' => $this->message, 'where' => $this->where];
    }

    public function __toString(): string
    {
        return ($this->where ? $this->where.' ' : '').'['.$this->code.'] '.$this->message;
    }
}
