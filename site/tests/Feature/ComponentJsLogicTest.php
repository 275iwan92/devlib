<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

/* REMARK
 * Tujuan       : Menjaga logika JavaScript komponen yang tidak tersentuh tes render PHP: Kanban (aturan transisi, dl:kanban-beforemove, urutan, WIP, simpan + rollback, Urungkan, saring) dan Grid (match, saring, colMatch, urut, kolom tampil, pilihan formatter). Tiap skrip di tests/js menjalankan berkas JS aslinya dengan Devlib tiruan (node + vm, tanpa dependensi).
 * Dipakai oleh : php artisan test. Tanpa node tes dilewati.
 * Status       : Aktif.
 * Batas        : Seret pointer, auto-scroll, modal kartu, menggambar tabel, pager, filter pop-up dan ekspor terikat DOM: belum diuji (butuh ekstraksi logika ke fungsi murni, perubahan src yang harus disetujui pemilik).
 */
class ComponentJsLogicTest extends TestCase
{
    /** Jalankan skrip tests/js/<skrip> terhadap resources/assets/js/components/<berkas>; kembalikan [kode keluar, keluaran]. */
    private function node(string $skrip, string $berkas): array
    {
        $node = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where node 2>NUL' : 'command -v node'));
        if ($node === '') {
            $this->markTestSkipped('node tidak ada');
        }
        $p = new Process([strtok($node, "\r\n"), base_path('tests/js/'.$skrip), base_path('../resources/assets/js/components/'.$berkas)]);
        $p->run();

        return [$p->getExitCode(), $p->getOutput().$p->getErrorOutput()];
    }

    public function test_kanban_move_rules_run_in_a_browserless_script(): void
    {
        [$kode, $out] = $this->node('kanban-logic.cjs', 'kanban.js');

        $this->assertSame(0, $kode, $out);
        $this->assertMatchesRegularExpression('/^OK \d+$/m', $out);
    }

    public function test_grid_data_logic_runs_in_a_browserless_script(): void
    {
        [$kode, $out] = $this->node('grid-logic.cjs', 'grid.js');

        $this->assertSame(0, $kode, $out);
        $this->assertMatchesRegularExpression('/^OK \d+$/m', $out);
    }
}
