// Dipanggil ComponentJsLogicTest: menjalankan grid.js tanpa browser (Devlib tiruan) dan menguji logika data murni Grid:
// val, match (where), saring (statistik, tab, pilihan, kriteria kolom, cari), colMatch (>, <, >=, <=, =), urut, kolom tampil, pemilihan formatter.
// Format angka/tanggal itu sendiri milik core.js: di sini formatter ditiru dengan penanda supaya yang diuji adalah PILIHAN formatter oleh Grid.
// Yang TIDAK diuji di sini (terikat DOM): menggambar tabel, pager, filter pop-up, ekspor, seret kolom. Argumen: jalur grid.js.
const fs = require('fs'), vm = require('vm'), assert = require('assert');

let cls;
const Devlib = {
  Component: class {},
  define: (n, c) => { cls = c; },
  h: {
    srcAttr: () => '', $: () => null, $$: () => [], esc: s => String(s), toast: () => {}, at: () => null, val: () => '', emit: () => null,
    T: k => k, Tf: (k, o) => k + ':' + JSON.stringify(o), downloadXlsx: () => {},
    parseNum: v => +String(v),
    DlDateTime: {of: () => null}, DlTime: {of: () => null},
    fmt: {number: (v, d) => `N(${v},${d})`, money: (v, cur, d) => `M(${v},${cur},${d})`, gridDate: v => `D(${v})`},
  },
};
vm.runInNewContext(fs.readFileSync(process.argv[2], 'utf8'), {window: {Devlib}, document: {}, localStorage: {}, sessionStorage: {}, location: {search: ''}});

const COLS = [
  {key: 'name', label: 'Nama', type: 'text', code: 'sku'},
  {key: 'status', label: 'Status', type: 'text'},
  {key: 'qty', label: 'Qty', type: 'integer'},
  {key: 'price', label: 'Harga', type: 'money', currency: 'IDR'},
  {key: 'note', label: 'Catatan', type: 'text'},
];
const ROWS = [
  {id: 1, name: 'Kertas A4', sku: 'KRT-001', status: 'open', qty: 10, price: 50000, note: 'rahasia'},
  {id: 2, name: 'Tinta Hitam', sku: 'TNT-002', status: 'hold', qty: 2, price: 1500, note: ''},
  {id: 3, name: 'item 10', sku: 'ITM-010', status: 'open', qty: 100, price: 12, note: 'x'},
  {id: 4, name: 'item 2', sku: 'ITM-002', status: 'done', qty: 5, price: 1000, note: 'y'},
];
function grid(over = {}) {
  const g = Object.create(cls.prototype);
  Object.assign(g, {
    cols: COLS, rows: ROWS, chk: false, hasExp: false, entry: true,
    cfg: {stats: [{label: 'All'}, {label: 'Open', where: {status: 'open'}}, {label: 'OpenHold', where: {status: ['open', 'hold']}}], tabs: [], actions: 'none', ...over.cfg},
    st: {search: '', sort: null, dir: 1, stat: null, tab: 0, filters: {}, colf: {}, hidden: ['note'], ...over.st},
  }, over.root || {});
  return g;
}
const idsOf = list => [...list].map(r => r.id).join(',');

let n = 0; const ok = (name, fn) => { try { fn(); n++; } catch (e) { e.message = `[${name}] ${e.message}`; throw e; } };

ok('val: kosong jadi "", angka 0 dan false dipertahankan', () => {
  const g = grid();
  assert.strictEqual(g.val({a: null}, 'a'), ''); assert.strictEqual(g.val({}, 'a'), '');
  assert.strictEqual(g.val({a: 0}, 'a'), 0); assert.strictEqual(g.val({a: false}, 'a'), false);
});

ok('match: tanpa where = lolos; nilai tunggal dan daftar; dibandingkan sebagai teks', () => {
  const g = grid();
  assert.strictEqual(g.match(ROWS[0], undefined), true); assert.strictEqual(g.match(ROWS[0], null), true);
  assert.strictEqual(g.match(ROWS[0], {status: 'open'}), true); assert.strictEqual(g.match(ROWS[0], {status: 'hold'}), false);
  assert.strictEqual(g.match(ROWS[0], {status: ['hold', 'open']}), true);
  assert.strictEqual(g.match(ROWS[0], {qty: '10'}), true);        // 10 == "10"
  assert.strictEqual(g.match(ROWS[0], {status: 'open', qty: 99}), false);   // semua syarat harus cocok
  assert.strictEqual(g.match({}, {status: ''}), true);            // kolom tak ada = ''
});

ok('filtered: statistik (All = tanpa saringan), daftar nilai, tab', () => {
  const g = grid();
  assert.strictEqual(idsOf(g.filtered()), '1,2,3,4');
  g.st.stat = 0; assert.strictEqual(idsOf(g.filtered()), '1,2,3,4');   // All tidak menyaring
  g.st.stat = 1; assert.strictEqual(idsOf(g.filtered()), '1,3');
  g.st.stat = 2; assert.strictEqual(idsOf(g.filtered()), '1,2,3');
  assert.strictEqual(idsOf(g.filtered({noStat: true})), '1,2,3,4');    // hitungan di tombol statistik mengabaikan statistik
  const t = grid({cfg: {tabs: [{label: 'Semua'}, {label: 'Done', where: {status: 'done'}}]}, st: {tab: 1}});
  assert.strictEqual(idsOf(t.filtered()), '4'); assert.strictEqual(idsOf(t.filtered({noTab: true})), '1,2,3,4');
});

ok('filtered: pilihan tetap (filters) dan kriteria kolom (colf)', () => {
  const g = grid({st: {filters: {status: 'open'}}});
  assert.strictEqual(idsOf(g.filtered()), '1,3');
  g.st.filters = {status: ''}; assert.strictEqual(idsOf(g.filtered()), '1,2,3,4');   // '' = tidak aktif
  g.st.filters = {}; g.st.colf = {qty: '>5'}; assert.strictEqual(idsOf(g.filtered()), '1,3');
  g.st.colf = {qty: '>5', status: 'open'}; assert.strictEqual(idsOf(g.filtered()), '1,3');
  g.st.colf = {qty: '>5', status: 'hold'}; assert.strictEqual(idsOf(g.filtered()), '');   // semua kriteria harus cocok
});

ok('filtered: cari hanya di kolom TAMPIL + kode; tanpa beda huruf; digabung dengan statistik', () => {
  const g = grid();
  g.st.search = 'KERTAS'; assert.strictEqual(idsOf(g.filtered()), '1');
  g.st.search = 'tnt-002'; assert.strictEqual(idsOf(g.filtered()), '2');      // lewat kolom code (sku), bukan kolom tampil
  g.st.search = 'rahasia'; assert.strictEqual(idsOf(g.filtered()), '');       // kolom note disembunyikan = tidak dicari
  g.st.hidden = []; assert.strictEqual(idsOf(g.filtered()), '1');              // ditampilkan = dicari
  g.st.search = '  item  '; g.st.hidden = ['note']; assert.strictEqual(idsOf(g.filtered()), '3,4');   // spasi pinggir dibuang
  g.st.stat = 1; assert.strictEqual(idsOf(g.filtered()), '3');                 // cari AND statistik
  g.st.stat = null; g.st.search = 'N(10,0)'; assert.strictEqual(idsOf(g.filtered()), '1');   // angka dicari lewat teks hasil format
});

ok('colMatch: operator angka dan teks mengandung', () => {
  const g = grid();
  const m = (k, t, i) => !!g.colMatch(k, t, ROWS[i]);   // tak cocok pada kolom tanpa code = undefined (falsy): yang dijaga adalah benar/salahnya
  assert.strictEqual(m('qty', '>5', 0), true); assert.strictEqual(m('qty', '>10', 0), false);    // 10 > 10 salah
  assert.strictEqual(m('qty', '>=10', 0), true); assert.strictEqual(m('qty', '<=2', 1), true); assert.strictEqual(m('qty', '<2', 1), false);
  assert.strictEqual(m('qty', '=10', 0), true); assert.strictEqual(m('qty', '= 10', 0), true);   // spasi setelah operator boleh
  assert.strictEqual(m('price', '>1000', 0), true); assert.strictEqual(m('price', '<1000', 2), true);   // money juga numerik
  assert.strictEqual(m('qty', '', 0), true); assert.strictEqual(m('qty', '   ', 0), true); assert.strictEqual(m('qty', undefined, 0), true);
  assert.strictEqual(m('name', 'kertas', 0), true); assert.strictEqual(m('name', 'KERTAS', 0), true); assert.strictEqual(m('name', 'tinta', 0), false);
  assert.strictEqual(m('name', '>5', 0), false);                  // kolom teks: ">5" dicari sebagai teks biasa, bukan operator
  assert.strictEqual(m('name', 'krt-001', 0), true);              // lewat kolom code
  assert.strictEqual(m('qty', '>abc', 0), false);                 // bukan pola operator valid: dicari sebagai teks
});

ok('sorted: tanpa urut = daftar yang sama; angka sebagai angka; teks sadar angka; arah; tidak mengubah masukan', () => {
  const g = grid(); const list = ROWS.slice();
  assert.strictEqual(g.sorted(list), list);
  g.st.sort = 'qty'; assert.strictEqual(idsOf(g.sorted(list)), '2,4,1,3');      // 2 < 5 < 10 < 100 (bukan urut huruf)
  g.st.dir = -1; assert.strictEqual(idsOf(g.sorted(list)), '3,1,4,2');
  g.st.sort = 'name'; g.st.dir = 1; assert.strictEqual(idsOf(g.sorted(list)), '4,3,1,2');   // "item 2" < "item 10" < "Kertas" < "Tinta" (urut sadar angka)
  assert.strictEqual(idsOf(list), '1,2,3,4');                                    // masukan tidak berubah
  g.st.sort = 'price'; g.st.dir = 1; assert.strictEqual(idsOf(g.sorted(list)), '3,4,2,1');
  const gl = grid({root: {cols: [{key: 'ref', type: 'link', format: 'integer'}]}, st: {sort: 'ref'}});
  assert.strictEqual(idsOf(gl.sorted([{id: 1, ref: 10}, {id: 2, ref: 9}])), '2,1');   // kolom link diurut sebagai angka
  // Pecahan dan negatif: urut teks sadar-angka (localeCompare numeric) SALAH di sini (1.25 dianggap > 1.5), urut angka benar. Tiap tipe numerik diuji.
  for (const type of ['integer', 'number', 'money', 'link']) {
    const gd = grid({root: {cols: [{key: 'v', type}]}, st: {sort: 'v'}});
    assert.strictEqual(idsOf(gd.sorted([{id: 1, v: 1.5}, {id: 2, v: 1.25}, {id: 3, v: -2}])), '3,2,1', 'tipe ' + type);
  }
  const gt = grid({root: {cols: [{key: 'v', type: 'text'}]}, st: {sort: 'v'}});
  assert.strictEqual(idsOf(gt.sorted([{id: 1, v: 'b'}, {id: 2, v: 'a'}])), '2,1');   // tipe teks tetap diurut sebagai teks
});

ok('shown, span, actFirst, actLast', () => {
  const g = grid();
  assert.strictEqual([...g.shown()].map(c => c.key).join(','), 'name,status,qty,price');   // note disembunyikan
  assert.strictEqual(g.span(), 4);
  g.chk = true; g.hasExp = true; g.cfg.actions = 'edit'; assert.strictEqual(g.span(), 7);   // + centang + expand + aksi
  assert.strictEqual(g.actFirst(), true); assert.strictEqual(g.actLast(), false);          // entry: aksi di kiri
  g.entry = false; assert.strictEqual(g.actFirst(), false); assert.strictEqual(g.actLast(), true);
  g.cfg.actions = 'none'; assert.strictEqual(g.actLast(), false);
});

ok('fmtCell: pilihan formatter per tipe kolom', () => {
  const g = grid();
  assert.strictEqual(g.fmtCell({type: 'text'}, 'abc'), 'abc');
  assert.strictEqual(g.fmtCell({type: 'integer'}, 5), 'N(5,0)');
  assert.strictEqual(g.fmtCell({type: 'money'}, 5), 'M(5,IDR,0)');
  assert.strictEqual(g.fmtCell({type: 'money', currency: 'USD', decimals: 2}, 5), 'M(5,USD,2)');
  assert.strictEqual(g.fmtCell({type: 'number'}, 5), 'N(5,0)'); assert.strictEqual(g.fmtCell({type: 'number'}, 5.5), 'N(5.5,2)');   // desimal otomatis
  assert.strictEqual(g.fmtCell({type: 'number', decimals: 3}, 5), 'N(5,3)');
  assert.strictEqual(g.fmtCell({type: 'date'}, '2026-11-05'), 'D(2026-11-05)');
  assert.strictEqual(g.fmtCell({type: 'link', format: 'integer'}, 7), 'N(7,0)');           // link meminjam format kolomnya
  assert.strictEqual(g.fmtCell({type: 'link'}, 7), 'N(7,0)');                               // tanpa format = number
  assert.strictEqual(g.fmtCell({type: 'datetime'}, 'x'), 'x'); assert.strictEqual(g.fmtCell({type: 'time'}, 'y'), 'y');   // tak terbaca = nilai apa adanya
});

console.log('OK ' + n);
