<?php

use App\Support\AgentHistory;
use App\Support\BoardDemo;
use App\Support\Catalog;
use App\Support\FlowDemo;
use App\Support\GanttDemo;
use App\Support\Generator;
use App\Support\Qc;
use App\Support\Summary;
use Devlib\Device;
use Devlib\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Situs DevLib: sesi file; akun dari database devlib (sec_user) + akun contoh di config/devlib.php.

Route::get('/login', fn () => view('login', [
    'variant' => config('devlib.login_variant'),
    'error' => session('login_error'),
    'username' => old('login', ''),
]))->name('login');

// Layar login PWA (pwa/, React): token CSRF + bahasa tamu; login yang sama dengan form Blade, balasannya JSON.
Route::get('/pwa', fn () => response()->file(public_path('pwa/index.html')))->name('pwa'); // folder tidak disajikan sebagai index oleh peladen; berkasnya hasil `npm run build` di pwa/
Route::get('/pwa/session', fn () => response()->json(['csrf' => csrf_token(), 'lang' => Locale::lang()]))->name('pwa.session');

Route::post('/login', function (Request $r) {
    $login = trim((string) $r->input('login'));
    $password = (string) $r->input('password');
    $fail = fn (string $code) => $r->expectsJson()
        ? response()->json(['error' => $code], 422)
        : back()->withInput(['login' => $login])->with('login_error', $code);

    if ($login === '' || $password === '') {
        return $fail('empty');
    }

    // Akun sungguhan = tabel sec_user di database devlib (terpisah dari mockup.test, user 2026-10-05).
    // Akun contoh di config/devlib.php tetap ada untuk memperagakan dashboard per jabatan.
    $user = null;
    try {
        $row = DB::table('sec_user')->where('login', $login)->where('activate', true)->first();
        if ($row && Hash::check($password, $row->pswd)) {
            $user = ['login' => $row->login, 'password' => '', 'name' => $row->name, 'email' => $row->email, 'role' => $row->role,
                'position' => $row->role, 'developer' => (bool) $row->developer, 'lang_code' => $row->lang_code];
        }
    } catch (Throwable) {
        // database devlib belum ada / mati: akun contoh masih bisa masuk
    }
    $user ??= collect(config('devlib.users'))->first(fn ($u) => strcasecmp($u['login'], $login) === 0 && hash_equals($u['password'], $password));

    if (! $user) {
        return $fail('wrong');
    }

    $r->session()->regenerate();
    $r->session()->put('devlib_user', collect($user)->except('password')->all());
    $to = redirect()->intended(route('home'));

    return $r->expectsJson() ? response()->json(['redirect' => $to->getTargetUrl()]) : $to;
})->name('login.submit');

// Pilihan tampilan (phone / desktop) -> cookie `ui` terenkripsi yang dibaca Device::view (skills/devlib/phone.md). Tujuan tombol "Tampilan desktop" login PWA.
Route::post('/ui', fn (Request $r) => redirect()->route('home')->withCookie(cookie(Device::COOKIE, $r->validate(['ui' => ['required', Rule::in(Device::CHOICES)]])['ui'], 60 * 24 * 365)))->name('ui.set');

Route::post('/logout', function (Request $r) {
    $r->session()->invalidate();
    $r->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware('devlib.auth')->group(function () {
    // Gantt demo (Production Scheduling Board): impact check, then "Move into Draft".
    Route::post('/demo/gantt/impact', fn (Request $r) => response()->json(GanttDemo::impact($r->all())));
    Route::post('/demo/gantt/move', fn (Request $r) => response()->json(GanttDemo::impact($r->all()) + ['draft' => 'DRAFT '.$r->session()->increment('gantt_demo_draft').' change']));

    // Select · Add New (halaman p2-select-add): balasan palsu, tidak menyimpan apa pun. 422 bila nama kosong atau 'dup', 500 bila 'fail'.
    Route::post('/demo/select-add/supplier', function (Request $r) {
        abort_if($r->input('name') === 'fail', 500);
        $d = $r->validate(['name' => ['required', 'string', 'max:60', 'not_in:dup'], 'phone' => ['nullable', 'string', 'max:20'], 'contact' => ['nullable', 'string', 'max:60']], ['name.not_in' => 'This name already exists.']);

        return response()->json(['id' => 'NEW'.random_int(100, 999), 'name' => $d['name']], 201);
    });
    Route::post('/demo/select-add/document', fn (Request $r) => response('Demo: yang terkirim hanya '.implode(', ', array_keys($r->except('_token'))).' (tidak disimpan). Isian popup tidak ikut.'));

    Route::get('/', fn () => view('home', ['modules' => Catalog::modules(), 'user' => session('devlib_user')]))->name('home');

    // Layar contoh halaman katalog p7-phone (Phone shell): data fiktif.
    require __DIR__.'/phone-ref.php';

    // Data bersama semua demo halaman katalog.
    $libData = fn () => [
        'action' => 'javascript:void(0)',
        'error' => null,
        'username' => '',
        'modules' => Catalog::modules(),
        'user' => session('devlib_user'),
        'locations' => config('devlib.demo.locations'),
        'languages' => config('devlib.demo.languages'),
    ];

    // Halaman komponen: komponen hidup + ikon </> berisi kode pemakaiannya.
    Route::get('/lib/{slug}', function (Request $r, string $slug) use ($libData) {
        $page = Catalog::page($slug) ?? abort(404);
        // Halaman Aturan = tampilan langsung skills/devlib/*.md (RulesDocs), bukan demo komponen.
        if (isset($page['doc'])) {
            return view('lib-doc', ['page' => $page]);
        }
        $data = $libData();

        // Halaman kategori (Part 2): beberapa komponen + breadcrumb; kode = gabungan demo.
        if (isset($page['members'])) {
            $code = collect($page['members'])->map(fn ($m, $s) => '{{-- '.$m[0].' --}}'."\n".file_get_contents(resource_path("views/demos/{$s}.blade.php")))->implode("\n");

            return view('lib-cat', ['page' => $page, 'code' => $code, 'data' => $data]);
        }

        return view('lib', [
            'page' => $page,
            'demo' => $slug,
            'code' => file_get_contents(resource_path("views/demos/{$slug}.blade.php")),
            'data' => $data,
        ]);
    })->name('lib');

    // "Lihat sebagai" (skills/devlib/views.md): halaman katalog yang sama di x-dl::device-frame berukuran perangkat; ?as= android | ios | tablet | laptop | desktop.
    Route::get('/lib/{slug}/as', function (Request $r, string $slug) {
        $page = Catalog::page($slug) ?? abort(404);

        return view('view-as', ['page' => $page, 'slug' => $slug, 'preset' => (string) $r->query('as', 'android')]);
    })->name('lib.as');

    // Alur Grid -> Form -> kembali (skills/devlib/grid-form-flow.md): form = halaman sendiri, dibuka dari grid alur, Back/Simpan kembali ke grid asal (?from=).
    Route::get('/lib/{slug}/form/{id}', function (Request $r, string $slug, string $id) use ($libData) {
        abort_unless(FlowDemo::has($slug), 404);
        $doc = FlowDemo::doc($slug, $r->query('doc'));
        $back = FlowDemo::back($slug, $doc, $r->query('from'));
        $row = $id === 'new' ? null : FlowDemo::row($slug, $doc, $id);
        if ($id !== 'new' && ! $row) {
            return redirect($back)->with('flow_flash', FlowDemo::flash('danger', 'Data tidak ditemukan.'));
        }
        $page = Catalog::page($slug);
        $demo = FlowDemo::demo($slug, $doc);
        $name = $row ? ($row['name'] ?? $row['co_no'] ?? $row['po_no'] ?? $row['cv_no']) : 'New';
        $label = ['co' => 'Customer Order', 'po' => 'Purchase Order'][$doc] ?? FlowDemo::title($slug);
        $page = [
            'label' => $label.' · '.$name,
            'members' => [$demo => [$label.' form (opened from the grid, Back returns to it)', $page['classes']]],
            'crumbs' => [$page['part'] => null, $page['label'] => $back, $label.' · '.$name => url()->current()],
        ] + $page;

        return view('lib-cat', [
            'page' => $page,
            'code' => file_get_contents(resource_path("views/demos/{$demo}.blade.php")),
            // row = isi yang ditampilkan: baris yang dibuka, atau sumber salinan (?copy=) pada form baru hasil Duplicate.
            'data' => $libData() + ['flow' => ['slug' => $slug, 'id' => $id, 'doc' => $doc, 'back' => $back, 'status' => FlowDemo::status($slug, $doc, $row),
                'approval' => $r->query('approval', $row['approval'] ?? null), 'as' => $r->query('as'),
                'row' => $row ?? ($r->filled('copy') ? FlowDemo::row($slug, $doc, (string) $r->query('copy')) : null)]],
        ]);
    })->where('id', '[A-Za-z0-9_-]+')->name('lib.flow.form');

    // Cetakan form alur: status dokumen (dan approval) dibawa dari form lewat query.
    Route::get('/lib/{slug}/print/{id}', function (Request $r, string $slug, string $id) {
        abort_unless(FlowDemo::has($slug) && ! FlowDemo::isMaster($slug), 404);
        $doc = FlowDemo::doc($slug, $r->query('doc'));
        $row = FlowDemo::row($slug, $doc, $id) ?? FlowDemo::rows($slug, $doc)[0];
        $status = (string) $r->query('status', FlowDemo::status($slug, $doc, $row));
        $approval = $r->query('approval', $row['approval'] ?? null);

        return view('flow-print', ['doc' => \App\Support\FlowPrintDoc::make($slug, $doc, $row, $status, $approval)]);
    })->where('id', '[A-Za-z0-9_-]+')->name('lib.flow.print');

    // Nested Grid: satu tingkat = satu halaman (customer, order, product | supplier, po, item); jejak tingkat sebelumnya di query ?t=. Grid ke grid saja, tanpa form.
    Route::get('/lib/p5-grid-nested/{type}/{key}', function (Request $r, string $type, string $key) use ($libData) {
        $level = \App\Support\NestedDemo::level($type, $key, $r->query('t')) ?? abort(404);
        if ($r->query->has('t') && $r->query('t') !== \App\Support\NestedDemo::chain($level['trail'])) {   // jejak rusak, berulang, atau berlebih: alamat dibakukan
            return redirect($level['self']);
        }
        $base = Catalog::page('p5-grid-nested');
        $demo = 'grid-nested-level';
        $page = [
            'label' => $level['title'],
            'members' => [$demo => ['Nested Grid level: '.$level['kind'].' (links open the next grid; Back goes up one level)', $base['classes']]],
            'crumbs' => array_fill_keys($base['trail'], null) + [$base['label'] => \App\Support\NestedDemo::root()] + $level['crumbs'] + [$level['title'] => $level['self']],
        ] + $base;

        return view('lib-cat', ['page' => $page, 'code' => file_get_contents(resource_path("views/demos/{$demo}.blade.php")), 'data' => $libData() + ['nested' => $level]]);
    })->where('type', 'customer|order|product|supplier|po|item')->where('key', '[A-Za-z0-9_.-]+')->name('lib.nested');

    Route::post('/lib/{slug}/form/{id}', function (Request $r, string $slug, string $id) {
        abort_unless(FlowDemo::has($slug), 404);

        return FlowDemo::handle($r, $slug, $id);
    })->where('id', '[A-Za-z0-9_-]+')->name('lib.flow.save');

    // Aktif/Nonaktif grid master alur (stateless: membalik nilai baris, tanpa menyimpan).
    Route::post('/lib/{slug}/toggle/{id}', function (string $slug, string $id) {
        abort_unless(FlowDemo::has($slug), 404);
        $row = FlowDemo::row($slug, FlowDemo::doc($slug, null), $id) ?? abort(404);

        return ['active' => ! $row['active']];
    })->where('id', '[A-Za-z0-9_-]+')->name('lib.flow.toggle');

    // QC cepat (user 2026-10-07): Approved / Need improvement + catatan; hanya akun developer. Tiga lapis: QC, UAT officer, UAT supervisor.
    Route::post('/qc/review', function (Request $r) {
        $user = session('devlib_user');
        abort_unless(($user['developer'] ?? false) && Qc::store()->ready(), 403);
        $r->validate(['key' => 'required|string|max:190', 'name' => 'nullable|string|max:190', 'stage' => 'required|string', 'status' => 'required|string', 'note' => 'nullable|string|max:500']);
        $out = Qc::store()->record($r->input('key'), (string) $r->input('name'), $r->input('stage'), $r->input('status'), $r->input('note'), $user['login']);

        return $out + ['meta' => $user['login'].' · '.Locale::date(now())];
    })->name('qc.review');

    // Executive Summary (user 2026-10-05): sembilan halaman + simpan rating (hanya akun developer).
    Route::get('/summary/{page}', function (string $page) {
        abort_unless(isset(Summary::PAGES[$page]), 404);

        return view('summary', Summary::page($page, session('devlib_user')));
    })->name('summary');

    Route::post('/summary/rate', function (Request $r) {
        $user = session('devlib_user');
        abort_unless($user['developer'] ?? false, 403);
        $out = Summary::rate((int) $r->input('item'), (int) $r->input('rating'), $r->input('note'), $user['login']);

        return $out + ['meta' => $user['login'].' · '.Locale::date(now()).($out['ratings'] > 1 ? ' · '.$out['ratings'].'x' : '')];
    })->name('summary.rate');

    // Agent Log (user 2026-10-07): log pekerjaan agen Iwan / Jo dari git, dengan saringan.
    Route::get('/agent-log', fn () => view('agent-log'))->name('agent-log');

    // Agent History (user 2026-10-07): catatan pengingat user; aturan = App\Support\AgentHistory. Hanya akun developer yang boleh menulis.
    Route::get('/history', function (Request $r) {
        $ready = AgentHistory::ready();
        $note = $ready && $r->integer('id') ? (AgentHistory::find($r->integer('id')) ?? abort(404)) : null;

        return view('history', ['ready' => $ready, 'note' => $note, 'form' => $r->boolean('new'), 'feed' => $ready ? AgentHistory::feed($r->query('author')) : [], 'author' => $r->query('author')]);
    })->name('history');

    Route::post('/history', function (Request $r) {
        $user = session('devlib_user');
        abort_unless($user['developer'] ?? false, 403);
        try {
            $id = AgentHistory::add((string) $r->input('author', 'user'), (string) $r->input('kind', 'NOTE'), (string) $r->input('title'), $r->input('body'), $r->input('url'), $user['login']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('hist_error', $e->getMessage());
        }

        return redirect()->route('history', ['id' => $id])->with('hist_flash', 'Note saved.');
    })->name('history.save');

    Route::post('/history/{id}/{what}', function (int $id, string $what) {
        abort_unless(session('devlib_user')['developer'] ?? false, 403);
        AgentHistory::toggle($id, $what);

        return back();
    })->whereIn('what', ['pin', 'read'])->name('history.toggle');

    // Generator (user 2026-10-05): form permintaan sederhana + pratinjau; aturan = App\Support\Generator. Hanya akun developer
    // yang boleh menyimpan / menyetujui.
    Route::get('/generator', function (Request $r) {
        $ready = Generator::ready();
        Generator::defineTableLookup();
        $req = $ready && $r->integer('id') ? (Generator::find($r->integer('id')) ?? abort(404)) : null;

        return view('generator', ['ready' => $ready, 'req' => $req, 'form' => $req || $r->boolean('new'), 'rows' => $ready ? Generator::rows() : [],
            'objects' => $req ? Generator::objects($req->id_request) : []]);
    })->name('generator');

    Route::post('/generator', function (Request $r) {
        $user = session('devlib_user');
        abort_unless($user['developer'] ?? false, 403);
        [$id, $error] = Generator::save($r, $user['login'], $r->integer('id') ?: null);
        if ($error) {
            return back()->withInput()->with('gen_error', $error);
        }

        return redirect()->route('generator', ['id' => $id])->with('gen_flash', 'Saved. The agent reads the request in its next session.');
    })->name('generator.save');

    Route::post('/generator/{id}/formula', function (Request $r, int $id) {
        abort_unless(session('devlib_user')['developer'] ?? false, 403);
        Generator::saveFormula($id, $r);

        return redirect()->route('generator', ['id' => $id])->with('gen_flash', 'Preview saved.');
    })->name('generator.formula');

    Route::post('/generator/object/{object}', function (Request $r, int $object) {
        $user = session('devlib_user');
        abort_unless($user['developer'] ?? false, 403);
        Generator::decideObject($object, $r->input('decision') === 'approve', $user['login']);

        return back()->with('gen_flash', 'Decision saved.');
    })->name('generator.object');

    Route::post('/generator/{id}/approve', function (int $id) {
        $user = session('devlib_user');
        abort_unless($user['developer'] ?? false, 403);
        $error = Generator::approve($id, $user['login']);

        return redirect()->route('generator', ['id' => $id])->with($error ? 'gen_error' : 'gen_flash', $error ?: 'Approved. The agent builds it in its next session.');
    })->name('generator.approve');

    Route::post('/generator/{id}/generate', function (int $id) {
        abort_unless(session('devlib_user')['developer'] ?? false, 403);
        [$ok, $message] = Generator::generate($id);

        return redirect()->route('generator', ['id' => $id])->with($ok ? 'gen_flash' : 'gen_error', $message);
    })->name('generator.generate');

    Route::post('/generator/{id}/cancel', function (int $id) {
        abort_unless(session('devlib_user')['developer'] ?? false, 403);
        Generator::cancel($id);

        return redirect()->route('generator')->with('gen_flash', 'Request cancelled.');
    })->name('generator.cancel');

    // Contoh papan widget: aksi Customize (POST) / muat ulang (GET) -> HTML papan terbaru; susunan di sesi (BoardDemo).
    Route::match(['get', 'post'], '/board-demo', function (Request $r) {
        if ($r->isMethod('post')) {
            BoardDemo::apply($r->input('action'), $r->input('key'));
        }

        return ['html' => view('demos.widget-board')->render()];
    })->name('board.demo');

    // Mockup asli (untuk yang komponennya belum dibangun, dan tautan dari ikon </>).
    Route::get('/mockup/{file}', function (string $file) {
        $mockup = Catalog::find($file) ?? abort(404);

        return view('mockup', ['mockup' => $mockup]);
    })->where('file', '.*')->name('mockup');

    Route::get('/berkas/{file}', function (string $file) {
        $path = Catalog::path($file);
        abort_unless($path, 404);

        return response()->file($path);
    })->where('file', '.*')->name('mockup.file');
});
