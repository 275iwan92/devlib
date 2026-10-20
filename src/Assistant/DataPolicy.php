<?php

namespace Devlib\Assistant;

// Daftar terlarang (gaji / payroll), opt-in lewat Assistant::$policy. Hanya mencocokkan TEKS yang dinyatakan program
// (kunci Tool/layar/topik, modul, judul, frasa, path); DevLib tidak memahami arti data: program wajib memberi label yang benar.
// Absensi / kehadiran bukan payroll dan tidak terkena daftar ini.
final class DataPolicy
{
    public const WORDS = ['payroll', 'payslip', 'salary', 'gaji'];   // "slip gaji" / "penggajian" sudah memuat "gaji"

    /** @var list<string> */
    private array $words;

    /** @param list<string> $extra kata terlarang tambahan milik program (huruf kecil) */
    public function __construct(array $extra = [])
    {
        $this->words = [...self::WORDS, ...array_map('mb_strtolower', $extra)];
    }

    public function forbids(?string ...$texts): bool
    {
        foreach ($texts as $text) {
            $text = mb_strtolower((string) $text);
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
            throw new AssistantException("Tool [{$tool->key()}] touches a forbidden source (salary / payroll): the assistant never serves it.");
        }
    }
}
