<?php

namespace Devlib\Assistant;

// Adapter model bahasa (Ollama, Laravel AI SDK, API apa pun) yang diikat aplikasi. Tugasnya HANYA memahami kalimat:
// memilih SATU dari pilihan yang disodorkan Assistant. Ia tidak menghitung, tidak menulis SQL, tidak menyusun jawaban.
// Tanpa pengikatan, Assistant memakai NullModel dan tetap jalan dengan pencocokan kata kunci.
interface LanguageModel
{
    public function available(): bool;

    /**
     * @param  array<string, string>  $options  kunci pilihan => uraian singkat
     * @param  list<array{q: string, a: string}>  $history  tanya-jawab terakhir (konteks, boleh diabaikan)
     * @return ?string kunci pilihan, atau null bila tidak ada yang cocok / model tidak menjawab
     */
    public function choose(string $question, array $options, array $history = []): ?string;
}
