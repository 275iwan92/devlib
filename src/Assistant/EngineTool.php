<?php

namespace Devlib\Assistant;

use Closure;
use Devlib\Engine\EngineRegistry;
use Devlib\Engine\Mode;
use Devlib\Engine\Result;
use Devlib\Lang;

// Jembatan Engine -> Tool: asisten memakai engine DevLib yang sudah ada, tidak menulis hitungan sendiri.
// Aturan: hanya engine Calc (hitungan murni) dan Suggest (draf) yang boleh dipanggil asisten; Auto / Flow menulis data
// dan hanya jalan lewat layar + officer. Draf tampil sebagai draf (tidak pernah berlaku sendiri), alasan engine = langkah jawaban.
final class EngineTool implements Tool
{
    /**
     * @param  list<string>  $phrases  frasa pemicu huruf kecil
     * @param  Closure(Asker, string): array<string, mixed>  $input  menyusun input engine dari penanya + kalimatnya
     */
    public function __construct(
        private EngineRegistry $registry,
        private string $engineKey,
        private array $phrases,
        private Closure $input,
        private ?string $module = null,
        private int $minLevel = 0,
    ) {
        $mode = $registry->find($engineKey)->mode();
        if (! in_array($mode, [Mode::Calc, Mode::Suggest], true)) {
            throw new AssistantException("Engine [{$engineKey}] is {$mode->value}: only Calc and Suggest engines can be tools.");
        }
    }

    public function key(): string
    {
        return $this->engineKey;
    }

    public function title(): string
    {
        return $this->registry->find($this->engineKey)->name();
    }

    public function phrases(): array
    {
        return $this->phrases;
    }

    public function module(): ?string
    {
        return $this->module;
    }

    public function minLevel(): int
    {
        return $this->minLevel;
    }

    public function answer(Asker $asker, string $question): Reply
    {
        $result = $this->registry->run($this->engineKey, ($this->input)($asker, $question));
        if ($result->isRejected()) {
            return new Reply(Reply::UNKNOWN, $this->title(), $result->reasons[0] ?? null, note: implode(' ', $result->warnings) ?: null, key: $this->engineKey);
        }
        $rows = [];
        foreach ($result->data as $label => $value) {
            if (is_scalar($value)) {
                $rows[] = [(string) $label, is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value];
            }
        }

        return new Reply(Reply::NUMBER, $this->title(), $result->isDraft() ? Lang::text('Draft: the officer confirms in the screen before anything is saved.') : null,
            rows: $rows, steps: $result->reasons, note: implode(' ', $result->warnings) ?: null, key: $this->engineKey);
    }
}
