<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Executive Summary > All Projects: tahap tiap fitur tmerp (mockup -> tested -> compat -> applied -> uat). stage NULL = belum dicatat.
// Diisi lewat `php artisan stage:set` (App\Support\Projects::STAGES = tahap yang sah).
// Daftar awal = modul menu tmerp (dibaca dari migrasi/seeder sys_app_menu tmerp), SEMUA tanpa tahap: tahap tidak ditebak.
return new class extends Migration
{
    private const TMERP_MODULES = [
        'Sales' => 'SALES', 'Inquiry & Quotation' => 'PREORDER', 'Procurement' => 'PROC', 'Inventory' => 'INV', 'Warehouse' => 'WH',
        'Production' => 'PROD', 'Master Data' => 'MDATA', 'Finance & Accounting' => 'FINACC', 'Human Resources' => 'HRM',
        'Management Report' => 'MGMT', 'Setup & Security' => 'SETUP',
    ];

    public function up(): void
    {
        Schema::create('project_stage', function (Blueprint $table) {
            $table->id();
            $table->string('project', 40);
            $table->string('feature', 120);
            $table->string('stage', 20)->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();
            $table->unique(['project', 'feature']);
        });

        $now = now();
        DB::table('project_stage')->insert(array_map(fn ($name, $code) => [
            'project' => 'tmerp', 'feature' => $name, 'stage' => null, 'note' => 'Menu module '.$code, 'created_at' => $now, 'updated_at' => $now,
        ], array_keys(self::TMERP_MODULES), self::TMERP_MODULES));
    }

    public function down(): void
    {
        Schema::dropIfExists('project_stage');
    }
};
