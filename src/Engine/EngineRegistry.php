<?php

namespace Devlib\Engine;

use Throwable;

// Daftar engine sebuah aplikasi: satu pintu untuk menjalankan engine dan satu sumber untuk x-dl::engine-catalog
// (tanda tangan diambil EngineInspector dari kode, jadi katalog tidak pernah berbeda dari kode).
final class EngineRegistry
{
    /** @var array<string, Engine> */
    private array $engines = [];

    public function register(Engine ...$engines): static
    {
        foreach ($engines as $engine) {
            if (isset($this->engines[$engine->key()])) {
                throw new EngineException($engine->key(), 'Engine key is already registered.');
            }
            $this->engines[$engine->key()] = $engine;
        }

        return $this;
    }

    /** @return array<string, Engine> */
    public function all(): array
    {
        return $this->engines;
    }

    public function find(string $key): Engine
    {
        return $this->engines[$key] ?? throw new EngineException($key, 'Unknown engine.');
    }

    public function run(string $key, array $input = []): Result
    {
        $engine = $this->find($key);
        try {
            $result = $engine->run($input);
        } catch (EngineException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new EngineException($key, $e->getMessage(), $e);
        }
        if ($engine->mode() === Mode::Suggest && $result->isApplied()) {
            throw new EngineException($key, 'A suggest engine must return a draft; the officer confirms before anything is applied.');
        }

        return $result;
    }

    /** Baris untuk x-dl::engine-catalog (:engines=...). */
    public function catalog(?string $basePath = null): array
    {
        return array_values(array_map(fn (Engine $e) => [
            'key' => $e->key(), 'name' => $e->name(), 'group' => $e->group(), 'mode' => $e->mode()->value, 'purpose' => $e->purpose(),
        ] + EngineInspector::describe($e::class, ['run'], $basePath), $this->engines));
    }
}
