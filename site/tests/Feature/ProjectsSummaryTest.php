<?php

namespace Tests\Feature;

use App\Support\Projects;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Executive Summary > All Projects: halaman membaca projects.json (fixture fiktif di tes), tahap fitur tmerp, projects:snapshot.
class ProjectsSummaryTest extends TestCase
{
    private string $json;

    protected function setUp(): void
    {
        parent::setUp();
        $this->json = sys_get_temp_dir().DIRECTORY_SEPARATOR.'projects-test-'.uniqid().'.json';
        config(['devlib.projects_json' => $this->json]);
    }

    protected function tearDown(): void
    {
        @unlink($this->json);
        parent::tearDown();
    }

    private function table(): void
    {
        Schema::create('project_stage', function ($t) {
            $t->id();
            $t->string('project', 40);
            $t->string('feature', 120);
            $t->string('stage', 20)->nullable();
            $t->string('note', 300)->nullable();
            $t->timestamps();
        });
    }

    private function fixture(): void
    {
        file_put_contents($this->json, json_encode(['generated' => '2026-10-21', 'projects' => [
            'alfa' => ['label' => 'Alfa', 'tone' => 'accent', 'position' => 'Pos alfa', 'commits' => 1234, 'claude_commits' => 0, 'active_days' => 11, 'first' => '2026-09-01', 'last' => '2026-10-20', 'months' => ['2026-09' => 1000, '2026-10' => 234], 'sessions' => 7, 'sessions_first' => '2026-10-01'],
            'beta' => ['label' => 'Beta', 'tone' => 'warn', 'position' => 'Pos beta', 'commits' => 55, 'claude_commits' => 3, 'active_days' => 2, 'first' => '2026-08-06', 'last' => '2026-08-07', 'months' => ['2026-08' => 55], 'sessions' => 2, 'sessions_first' => null],
        ]]));
    }

    public function test_page_shows_numbers_from_json(): void
    {
        $this->fixture();
        $this->table();

        $this->get(route('summary', ['page' => 'projects']))->assertOk()
            ->assertSee('Commits per month')->assertSee('Alfa')->assertSee('Pos beta')
            ->assertSee('1.289', false)->assertSee('Mockup lane')->assertSee('Applied lane')->assertSee('Not recorded yet');
    }

    public function test_page_without_json_shows_empty_state(): void
    {
        $this->get(route('summary', ['page' => 'projects']))->assertOk()->assertSee('php artisan projects:snapshot');
    }

    public function test_menu_lists_projects_first(): void
    {
        $this->assertSame('projects', array_key_first(\App\Support\Summary::PAGES));
        $this->assertSame('S-projects', \App\Support\Summary::menu()['children'][0]['code']);
    }

    public function test_stage_set_and_list(): void
    {
        $this->table();
        $this->artisan('stage:set', ['project' => 'tmerp', 'feature' => 'Inventory', 'stage' => 'tested', '--note' => 'ok'])->assertSuccessful();
        $this->artisan('stage:set', ['project' => 'tmerp', 'feature' => 'Sales'])->assertSuccessful();
        $this->artisan('stage:list')->expectsOutputToContain('Tested OK')->assertSuccessful();
        $this->artisan('stage:set', ['project' => 'tmerp', 'feature' => 'Inventory', 'stage' => 'uat'])->assertSuccessful();

        $lanes = Projects::lanes();
        $this->assertSame(1, $lanes['unrecorded']);
        $this->assertSame([['stage' => 'Compatibility made', 'count' => 0], ['stage' => 'Applied to tmerp', 'count' => 0], ['stage' => 'UAT', 'count' => 1]], $lanes['applied']);
    }

    public function test_stage_set_rejects_unknown_stage(): void
    {
        $this->table();
        $this->artisan('stage:set', ['project' => 'tmerp', 'feature' => 'Inventory', 'stage' => 'done'])->expectsOutputToContain('Tahap tidak sah')->assertFailed();
        $this->assertSame([], Projects::features());
    }

    public function test_snapshot_skips_missing_path(): void
    {
        config(['devlib.projects' => ['x' => ['label' => 'Ghost', 'path' => sys_get_temp_dir().'/tidak-ada-'.uniqid(), 'sessions' => 'zz', 'tone' => 'accent']]]);

        $this->artisan('projects:snapshot')->expectsOutputToContain('Ghost: jalur tidak ada')->assertFailed();
        $this->assertFileDoesNotExist($this->json);
    }

    public function test_snapshot_counts_git_and_sessions(): void
    {
        $repo = sys_get_temp_dir().DIRECTORY_SEPARATOR.'repo-'.uniqid();
        $ses = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ses-'.uniqid();
        mkdir($repo);
        mkdir($ses);
        mkdir($ses.DIRECTORY_SEPARATOR.'pfx');
        mkdir($ses.DIRECTORY_SEPARATOR.'pfx--claude-worktrees-w1');
        mkdir($ses.DIRECTORY_SEPARATOR.'pfxother');
        file_put_contents($ses.'/pfx/a.jsonl', json_encode(['type' => 'x'])."\n".json_encode(['timestamp' => '2026-10-02T01:00:00Z'])."\n");
        file_put_contents($ses.'/pfx--claude-worktrees-w1/b.jsonl', json_encode(['timestamp' => '2026-10-05T01:00:00Z'])."\n");
        file_put_contents($ses.'/pfxother/c.jsonl', '{}');
        foreach ([['init'], ['-c', 'user.name=Claude Test', 'commit', '--allow-empty', '-m', 'a'], ['-c', 'user.name=Orang Fiktif', 'commit', '--allow-empty', '-m', 'b']] as $i => $args) {
            $cmd = $i === 0 ? $args : array_merge(['-c', 'user.email=t@example.test'], $args);
            exec('git -C '.escapeshellarg($repo).' '.implode(' ', array_map('escapeshellarg', $cmd)).' 2>&1');
        }
        config(['devlib.projects' => ['x' => ['label' => 'Fiktif', 'path' => $repo, 'sessions' => 'pfx', 'tone' => 'teal']], 'devlib.claude_sessions_path' => $ses]);

        $this->artisan('projects:snapshot')->assertSuccessful();
        $p = json_decode(file_get_contents($this->json), true)['projects']['x'];

        $this->assertSame([2, 1, 1], [$p['commits'], $p['claude_commits'], $p['active_days']]);
        $this->assertSame([2, '2026-10-02'], [$p['sessions'], $p['sessions_first']]);
    }
}
