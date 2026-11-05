<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Buat / perbarui akun login katalog di sec_user. Sandi diminta lewat prompt tersembunyi: tidak masuk riwayat shell, berkas, atau log; yang tersimpan hanya hash.
class MakeUser extends Command
{
    protected $signature = 'devlib:user {login} {--name=} {--role=} {--developer}';

    protected $description = 'Buat atau perbarui akun login katalog (sandi lewat prompt tersembunyi)';

    public function handle(): int
    {
        $login = (string) $this->argument('login');
        $password = (string) $this->secret("Sandi untuk {$login}");
        if ($password === '' || $password !== (string) $this->secret('Ulangi sandi')) {
            $this->error('Sandi kosong atau tidak sama.');

            return self::FAILURE;
        }
        $exists = DB::table('sec_user')->where('login', $login)->exists();
        DB::table('sec_user')->updateOrInsert(['login' => $login], [
            'pswd' => Hash::make($password), 'name' => $this->option('name') ?: $login, 'role' => $this->option('role') ?: null,
            'developer' => (bool) $this->option('developer'), 'activate' => true, 'pswd_last_updated' => now(),
            'updated_at' => now(), 'updated_by' => 'devlib:user',
        ] + ($exists ? [] : ['created_at' => now(), 'created_by' => 'devlib:user']));
        $this->info(($exists ? 'Diperbarui' : 'Dibuat').": {$login}");

        return self::SUCCESS;
    }
}
