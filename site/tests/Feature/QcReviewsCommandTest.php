<?php

namespace Tests\Feature;

use Devlib\Qc;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Perintah qc:reviews milik paket: store bawaan, daftar catatan terbuka, lalu --resolve menutupnya.
class QcReviewsCommandTest extends TestCase
{
    public function test_lists_open_notes_and_resolves_them(): void
    {
        Schema::create('qc_review', function ($t) {
            $t->id('id_review');
            $t->string('item_key', 190);
            $t->string('item_name', 190);
            $t->string('stage', 20)->default('QC');
            $t->string('status', 20);
            $t->string('note', 500)->nullable();
            $t->string('reviewed_by', 60);
            $t->dateTime('reviewed_at');
            $t->string('resolved_by', 60)->nullable();
            $t->dateTime('resolved_at')->nullable();
        });
        (new Qc)->record('p5-form', 'Form', 'QC', 'NEEDS_IMPROVEMENT', 'kolom terlalu sempit', 'iwan');

        $this->artisan('qc:reviews')->expectsOutputToContain('kolom terlalu sempit')->assertSuccessful();
        $this->artisan('qc:reviews', ['--resolve' => 1, '--by' => 'agen'])->expectsOutputToContain('ditandai selesai')->assertSuccessful();
        $this->artisan('qc:reviews')->doesntExpectOutputToContain('kolom terlalu sempit')->assertSuccessful();
    }

    public function test_warns_when_table_missing(): void
    {
        $this->artisan('qc:reviews')->expectsOutputToContain('qc_review belum ada')->assertSuccessful();
    }
}
