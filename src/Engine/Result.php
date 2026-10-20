<?php

namespace Devlib\Engine;

// Hasil baku sebuah engine. draft = usulan, TIDAK ditulis ke database sebelum officer Confirm; applied = sudah berlaku;
// rejected = ditolak (alasan di reasons). Tiap keputusan di data wajib punya alasan di reasons (tampil ke officer).
final class Result
{
    public const DRAFT = 'draft';

    public const APPLIED = 'applied';

    public const REJECTED = 'rejected';

    /** @param list<string> $reasons alasan tiap keputusan  @param list<string> $warnings temuan yang tidak memblokir */
    private function __construct(
        public readonly string $status,
        public readonly array $data = [],
        public readonly array $reasons = [],
        public readonly array $warnings = [],
    ) {}

    public static function draft(array $data = [], array $reasons = [], array $warnings = []): self
    {
        return new self(self::DRAFT, $data, $reasons, $warnings);
    }

    public static function applied(array $data = [], array $reasons = [], array $warnings = []): self
    {
        return new self(self::APPLIED, $data, $reasons, $warnings);
    }

    public static function rejected(string $reason, array $warnings = []): self
    {
        return new self(self::REJECTED, [], [$reason], $warnings);
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isApplied(): bool
    {
        return $this->status === self::APPLIED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::REJECTED;
    }
}
