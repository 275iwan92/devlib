<?php

namespace Devlib\Assistant;

// Daftar terlarang (gaji, payroll, formula HPP), opt-in lewat Assistant::$policy. Hanya mencocokkan TEKS yang dinyatakan program
// (kunci Tool/layar/topik, modul, judul, frasa, path); DevLib tidak memahami arti data: program wajib memberi label yang benar.
// Program tanpa teks yang cocok tidak terhalang sama sekali. Absensi / kehadiran bukan payroll; NILAI HASIL HPP (rupiah) bukan
// formulanya: itu tingkat nilai (Sensitivity::Value), hanya untuk ValueAsker.
final class DataPolicy
{
    // "slip gaji" / "penggajian" sudah memuat "gaji". Tanda baca dan garis bawah dianggap spasi ("hpp_formula" = "hpp formula").
    public const WORDS = ['payroll', 'payslip', 'salary', 'gaji',
        'hpp formula', 'formula hpp', 'rumus hpp', 'cara hitung hpp', 'cara menghitung hpp', 'komponen hpp', 'parameter hpp',
        'costing formula', 'formula costing', 'cost formula', 'formula cost', 'rumus costing', 'costing parameter'];

    /** @var list<string> */
    private array $words;

    /** @param list<string> $extra kata terlarang tambahan milik program */
    public function __construct(array $extra = [])
    {
        $this->words = array_map($this->normalize(...), [...self::WORDS, ...$extra]);
    }

    public function forbids(?string ...$texts): bool
    {
        foreach ($texts as $text) {
            $text = $this->normalize((string) $text);
            foreach ($this->words as $word) {
                if ($text !== '' && str_contains($text, $word)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Tool terlarang tidak boleh didaftarkan. */
    public function assertTool(Tool $tool): void
    {
        if ($this->forbids($tool->key(), $tool->module(), $tool->title(), ...$tool->phrases())) {
            throw new AssistantException("Tool [{$tool->key()}] touches a forbidden source (salary / payroll / costing formula): the assistant never serves it.");
        }
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)));
    }
}
