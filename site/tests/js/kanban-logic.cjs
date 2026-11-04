// Dipanggil ComponentJsLogicTest: menjalankan kanban.js tanpa browser (Devlib tiruan) dan menguji aturan pindah kartu:
// transisi yang diizinkan, dl:kanban-beforemove (batal + lanjut), urutan kartu, batas WIP, simpan ke server + rollback, Urungkan, saring, tanggal.
// Yang TIDAK diuji di sini (terikat DOM): seret pointer, auto-scroll, modal kartu. Argumen: jalur kanban.js.
const fs = require('fs'), vm = require('vm'), assert = require('assert');

let cls;
const toasts = [];
const Devlib = {
  Component: class {},
  define: (n, c) => { cls = c; },
  h: {
    $: () => null, $$: () => [], esc: s => String(s), visible: () => true, at: () => null, emit: () => null,
    toast: (msg, tone) => toasts.push([msg, tone]),
    confirmBox: async () => true,
    T: k => k,
    Tf: (k, o) => k + ':' + JSON.stringify(o),
    fmt: {date: () => 'D', time: () => 'T'},
  },
};
let fetchImpl = async () => ({ok: true});
const win = {Devlib, DL_CSRF: 'tok'};
vm.runInNewContext(fs.readFileSync(process.argv[2], 'utf8'), {
  window: win, CustomEvent, fetch: (...a) => fetchImpl(...a), setTimeout, clearTimeout, document: {}, navigator: {},
});

// Papan baru per tes. rules: todo hanya boleh ke doing; doing boleh ke todo/done; done = akhir (tanpa tujuan); kolom lain bebas.
function board(over = {}) {
  const k = Object.create(cls.prototype);
  const o = {
    el: Object.assign(new EventTarget(), {dataset: {moveUrl: '/api/move'}}),
    cols: [{key: 'todo', label: 'To do'}, {key: 'doing', label: 'Doing', limit: 2}, {key: 'done', label: 'Done', done: true}, {key: 'free', label: 'Free'}],
    cards: [{id: 'a', col: 'todo', title: 'A'}, {id: 'b', col: 'todo', title: 'B'}, {id: 'c', col: 'doing', title: 'C'}, {id: 'd', col: 'doing', title: 'D'}, {id: 'e', col: 'done', title: 'E'}],
    rules: {todo: ['doing'], doing: ['todo', 'done'], done: []},
    me: 'Budi', q: '', prio: '', undoOn: false, readonly: false, renders: 0, bars: [], events: [], persisted: [],
    TONES: ['accent', 'success', 'warn', 'violet', 'info', 'danger'],
    render() { this.renders++; },
    undoBar(text, fn) { this.bars.push([text, fn]); },
    ...over,
  };
  Object.assign(k, o);
  k.el.addEventListener('dl:kanban', e => k.events.push(e.detail));
  const real = cls.prototype.persist;
  k.persist = (...a) => { const p = real.apply(k, a); k.persisted.push(p); return p; };
  return k;
}
const ids = (k, col) => k.cards.filter(c => c.col === col).map(c => c.id).join('');
const order = k => k.cards.map(c => c.id).join('');
const last = () => toasts[toasts.length - 1];

(async () => {
  let n = 0; const ok = (name, fn) => Promise.resolve(fn()).then(() => { n++; }, e => { e.message = `[${name}] ${e.message}`; throw e; });

  await ok('allowed: aturan transisi', () => {
    const k = board();
    assert.strictEqual(k.allowed('todo', 'doing'), true);
    assert.strictEqual(k.allowed('todo', 'done'), false);          // todo hanya boleh ke doing
    assert.strictEqual(k.allowed('done', 'todo'), false);          // done = daftar kosong = akhir
    assert.strictEqual(k.allowed('done', 'done'), true);           // diam di kolom sendiri selalu boleh
    assert.strictEqual(k.allowed('free', 'done'), true);           // kolom tanpa aturan = bebas
    assert.strictEqual(board({rules: {}}).allowed('todo', 'done'), true);
  });

  await ok('move ditolak aturan: kartu tetap, toast danger, tanpa event/server', async () => {
    toasts.length = 0; let called = 0; fetchImpl = async () => { called++; return {ok: true}; };
    const k = board(); const before = order(k);
    assert.strictEqual(k.move(k.cardById('a'), 'done'), false);
    assert.strictEqual(order(k), before);
    assert.strictEqual(k.cardById('a').col, 'todo');
    assert.strictEqual(last()[1], 'danger');
    assert.match(last()[0], /k_move_denied/);
    assert.strictEqual(k.events.length, 0);
    assert.strictEqual(k.renders, 1);                              // layar digambar ulang agar kartu kembali
    await Promise.all(k.persisted); assert.strictEqual(called, 0);
  });

  await ok('urut ulang dalam kolom: tanpa aktivitas, tanpa beforemove, tanpa server', async () => {
    let before = 0, called = 0; fetchImpl = async () => { called++; return {ok: true}; };
    const k = board(); k.el.addEventListener('dl:kanban-beforemove', () => before++);
    assert.strictEqual(k.move(k.cardById('a'), 'todo', 1), true);
    assert.strictEqual(ids(k, 'todo'), 'ba');                      // a pindah ke indeks 1 -> b lalu a
    assert.strictEqual(k.cardById('a').activity, undefined);
    assert.strictEqual(before, 0);
    assert.deepStrictEqual(k.events.map(e => [e.type, e.from, e.to]), [['move', 'todo', 'todo']]);
    await Promise.all(k.persisted); assert.strictEqual(called, 0);
  });

  await ok('pindah antar kolom: indeks, riwayat, event, peringatan WIP', async () => {
    toasts.length = 0; fetchImpl = async () => ({ok: true});
    const k = board(); const a = k.cardById('a');
    assert.strictEqual(k.move(a, 'doing', 0), true);               // sebelum c
    assert.strictEqual(ids(k, 'doing'), 'acd'); assert.strictEqual(ids(k, 'todo'), 'b');
    assert.strictEqual(a.col, 'doing');
    assert.match(a.activity[0].text, /k_act_moved/); assert.strictEqual(a.activity[0].by, 'Budi');
    assert.deepStrictEqual(k.events.map(e => [e.type, e.from, e.to]), [['move', 'todo', 'doing']]);
    assert.ok(toasts.some(t => t[1] === 'warn' && /k_wip_over/.test(t[0])), 'doing sudah penuh (limit 2): harus ada peringatan');
    // tanpa indeks / indeks melewati ujung = di akhir kolom
    assert.strictEqual(k.move(k.cardById('b'), 'doing'), true); assert.strictEqual(ids(k, 'doing').slice(-1), 'b');
    const k2 = board(); k2.move(k2.cardById('a'), 'doing', 99); assert.strictEqual(ids(k2, 'doing'), 'cda');
  });

  await ok('dl:kanban-beforemove: preventDefault menunda, resume melanjutkan dengan isian tambahan', async () => {
    const bodies = []; fetchImpl = async (url, o) => { bodies.push([url, o]); return {ok: true}; };
    const k = board(); let detail;
    k.el.addEventListener('dl:kanban-beforemove', e => { detail = e.detail; e.preventDefault(); });
    const a = k.cardById('a'), before = order(k);
    assert.strictEqual(k.move(a, 'doing', 0), false);              // ditunda aplikasi
    assert.strictEqual(order(k), before); assert.strictEqual(a.col, 'todo'); assert.strictEqual(k.events.length, 0);
    assert.deepStrictEqual([detail.from, detail.to, detail.index, detail.undo], ['todo', 'doing', 0, false]);
    assert.strictEqual(typeof detail.resume, 'function');
    detail.resume({jadwal: '2026-11-05'});                         // pop-up selesai: lanjutkan (resumed menghindari beforemove ulang)
    assert.strictEqual(a.col, 'doing'); assert.strictEqual(ids(k, 'doing'), 'acd');
    await Promise.all(k.persisted);
    const sent = JSON.parse(bodies[0][1].body);
    assert.deepStrictEqual(sent, {jadwal: '2026-11-05', card: 'a', from: 'todo', to: 'doing'});
  });

  await ok('persist: POST JSON dengan CSRF; sukses = tidak ada rollback', async () => {
    let call; fetchImpl = async (url, o) => { call = [url, o]; return {ok: true}; };
    const k = board(); k.move(k.cardById('a'), 'doing', 0); await Promise.all(k.persisted);
    assert.strictEqual(call[0], '/api/move');
    assert.strictEqual(call[1].method, 'POST');
    assert.strictEqual(call[1].headers['X-CSRF-TOKEN'], 'tok');
    assert.strictEqual(call[1].credentials, 'same-origin');
    assert.deepStrictEqual(JSON.parse(call[1].body), {card: 'a', from: 'todo', to: 'doing'});
    assert.strictEqual(ids(k, 'doing'), 'acd');
  });

  await ok('persist ditolak server: kartu kembali ke kolom dan urutan asal, riwayat dibuang, pesan galat', async () => {
    toasts.length = 0; fetchImpl = async () => ({ok: false, statusText: 'Gagal', json: async () => ({message: 'Ditolak server'})});
    const k = board(); const a = k.cardById('a'), before = order(k);
    k.move(a, 'doing', 0); assert.strictEqual(a.col, 'doing');
    await Promise.all(k.persisted);
    assert.strictEqual(order(k), before); assert.strictEqual(a.col, 'todo');
    assert.strictEqual(a.activity.length, 0);                      // catatan "dipindah" ikut dibatalkan
    assert.deepStrictEqual(last(), ['Ditolak server', 'danger']);
  });

  await ok('persist gagal jaringan: rollback + pesan galat', async () => {
    toasts.length = 0; fetchImpl = async () => { throw new Error('offline'); };
    const k = board(); const a = k.cardById('a'), before = order(k);
    k.move(a, 'doing', 0); await Promise.all(k.persisted);
    assert.strictEqual(order(k), before); assert.strictEqual(a.col, 'todo');
    assert.deepStrictEqual(last(), ['offline', 'danger']);
  });

  await ok('tanpa data-move-url: tidak ada permintaan ke server', async () => {
    let called = 0; fetchImpl = async () => { called++; return {ok: true}; };
    const k = board({el: Object.assign(new EventTarget(), {dataset: {}})});
    assert.strictEqual(k.move(k.cardById('a'), 'doing', 0), true); await Promise.all(k.persisted);
    assert.strictEqual(called, 0);
  });

  await ok('Urungkan: hanya bila opt-in; undo mengembalikan indeks asal; undo tidak membuat Urungkan baru', async () => {
    fetchImpl = async () => ({ok: true});
    const off = board(); off.move(off.cardById('b'), 'doing', 0);
    assert.strictEqual(off.bars.length, 0, 'undo mati (bawaan): tanpa bilah Urungkan');
    const k = board({undoOn: true}); const a = k.cardById('a'); const flags = [];
    k.el.addEventListener('dl:kanban-beforemove', e => flags.push(e.detail.undo));
    k.move(a, 'doing', 0);                                         // a = kartu PERTAMA di todo (indeks 0) ke doing
    assert.strictEqual(k.bars.length, 1); assert.match(k.bars[0][0], /k_moved_to/);
    assert.strictEqual(ids(k, 'todo'), 'b');
    k.bars[0][1]();                                                // pencet Urungkan
    assert.strictEqual(ids(k, 'todo'), 'ab');                      // kembali ke indeks 0 (bukan ke ujung kolom)
    assert.strictEqual(a.col, 'todo');
    assert.deepStrictEqual(flags, [false, true]);                  // beforemove tahu ini pindah hasil Urungkan
    assert.strictEqual(k.bars.length, 1, 'pindah hasil Urungkan tidak memunculkan bilah baru');
    // pindah dalam kolom yang sama tidak menawarkan Urungkan
    const s = board({undoOn: true}); s.move(s.cardById('a'), 'todo', 1); assert.strictEqual(s.bars.length, 0);
  });

  await ok('visible: saring prioritas dan teks (judul, deskripsi, label string/objek)', () => {
    const k = board();
    const c = {title: 'Bayar Listrik', desc: 'Tagihan PLN', priority: 'High', tags: ['Urgent', {label: 'Kantor', tone: 'info'}]};
    assert.strictEqual(k.visible(c), true);
    k.q = 'listrik'; assert.strictEqual(k.visible(c), true);       // judul
    k.q = 'pln'; assert.strictEqual(k.visible(c), true);           // deskripsi
    k.q = 'urgent'; assert.strictEqual(k.visible(c), true);        // label string
    k.q = 'kantor'; assert.strictEqual(k.visible(c), true);        // label objek
    k.q = 'tidak ada'; assert.strictEqual(k.visible(c), false);
    k.q = ''; k.prio = 'High'; assert.strictEqual(k.visible(c), true);
    k.prio = 'Low'; assert.strictEqual(k.visible(c), false);
    k.prio = ''; assert.strictEqual(k.visible({title: 'X'}), true);   // tanpa desc/tags tidak error
  });

  await ok('due: zona WIB (+07:00), kosong = null', () => {
    const k = board();
    assert.strictEqual(k.due({due: '2026-11-05 14:30'}).getTime(), new Date('2026-11-05T14:30:00+07:00').getTime());
    assert.strictEqual(k.due({due: ''}), null); assert.strictEqual(k.due({}), null);
  });

  await ok('ini, toneOf, allLabels, cardById', () => {
    const k = board();
    assert.strictEqual(k.ini('budi santoso wijaya'), 'BS'); assert.strictEqual(k.ini(''), '?'); assert.strictEqual(k.ini('x'), 'X');
    assert.strictEqual(k.toneOf('Urgent'), k.toneOf('Urgent')); assert.ok(k.TONES.includes(k.toneOf('apa saja')));
    k.cards = [{id: 1, tags: ['Urgent', {label: 'Kantor', tone: 'info'}]}, {id: 2, tags: ['Urgent']}, {id: 3}];
    const l = k.allLabels();
    assert.deepStrictEqual([...l].map(t => t.label).sort(), ['Kantor', 'Urgent']);   // tanpa duplikat ([...] = array milik tes, bukan milik vm)
    assert.strictEqual(l.find(t => t.label === 'Urgent').tone, k.toneOf('Urgent'));
    assert.strictEqual(l.find(t => t.label === 'Kantor').tone, 'info');
    assert.strictEqual(k.cardById('2').id, 2); assert.strictEqual(k.cardById(3).id, 3); assert.strictEqual(k.cardById('x'), undefined);
  });

  console.log('OK ' + n);
})().catch(e => { console.error(e.stack || e); process.exit(1); });
