<?php

namespace App\Engines;

use Devlib\Engine\Described;
use Devlib\Engine\Engine;
use Devlib\Engine\Mode;
use Devlib\Engine\Result;

// Contoh engine mode Flow: tombol yang berlaku per mode data (aturan pemilik 2026-10-20, skills/devlib/SKILL.md).
class StatusFlowEngine implements Described, Engine
{
    private const TRANSACTIONAL = [
        'draft' => ['save', 'delete', 'new', 'print', 'confirm'],
        'confirmed' => ['cancel', 'new', 'print'],
        'cancelled' => ['new', 'print'],
    ];

    private const MASTER = ['activated' => ['edit', 'deactivate'], 'deactivated' => ['edit', 'activate']];

    public function key(): string
    {
        return 'status-flow';
    }

    public function name(): string
    {
        return 'Document Status Flow';
    }

    public function group(): string
    {
        return 'Documents';
    }

    public function mode(): Mode
    {
        return Mode::Flow;
    }

    public function purpose(): string
    {
        return 'Tells which actions a record may take in its current mode (Draft, Confirmed, Cancelled or Activated, Deactivated).';
    }

    public function run(array $input): Result
    {
        $status = strtolower((string) ($input['status'] ?? ''));
        $table = ($input['kind'] ?? 'transactional') === 'master' ? self::MASTER : self::TRANSACTIONAL;

        return isset($table[$status])
            ? Result::applied(['status' => $status, 'actions' => $table[$status]], ['Mode '.strtoupper($status).' allows: '.implode(', ', $table[$status]).'.'])
            : Result::rejected('Unknown status "'.$status.'" for this kind of record.');
    }

    public function details(): array
    {
        return [
            'inputs' => [['kind', 'transactional (default) or master'], ['status', 'draft, confirmed, cancelled / activated, deactivated']],
            'outputs' => [['actions', 'Buttons the record may use now']],
            'steps' => ['Transactional: Draft -> Confirm; Confirmed is read only and can only Cancel; Cancelled stays stored.', 'Master has no Draft or Confirm, only Activated or Deactivated.'],
            'docs' => ['skills/devlib/SKILL.md: Data transaksional'],
        ];
    }
}
