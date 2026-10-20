<?php

namespace Devlib\Engine;

// Kontrak baku engine (mesin hitung / aturan bisnis) untuk SEMUA proyek. Opt-in: tidak ada yang terdaftar otomatis.
// Aturan baku (rinci: skills/devlib/engine.md):
//   - mode Suggest hanya boleh mengembalikan Result::draft (EngineRegistry::run menolak selain itu);
//   - draf tidak menulis ke database; hasil keputusan officer (OVERRIDE / MANUAL) tidak pernah ditimpa engine;
//   - kegagalan = EngineException bernama, bukan false / null.
interface Engine
{
    public function key(): string;

    public function name(): string;

    public function group(): string;

    public function mode(): Mode;

    /** Kegunaan satu kalimat (tampil di x-dl::engine-catalog). */
    public function purpose(): string;

    public function run(array $input): Result;
}
