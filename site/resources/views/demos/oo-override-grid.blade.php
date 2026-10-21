<script>
    // Turunan JS: hanya method yang berbeda yang ditulis; sisanya diwarisi dari Grid standar.
    (window.DevlibReady ||= []).push(Devlib => {
        class GridStockMovement extends Devlib.classes.Grid {
            // Kolom bertanda 'signed' menampilkan tanda + di depan angka positif; angka lain tidak berubah.
            fmtCell(c, v) {
                const text = super.fmtCell(c, v);
                return c.signed && Number(v) > 0 ? '+' + text : text;
            }
        }
        Devlib.register(GridStockMovement);
    });
</script>
@php
    $moves = [
        ['IN/26/0001', 'Art Paper 150gr', '2026-07-03', 'Receipt', 'AP-2603-A', 2000],
        ['OUT/26/0034', 'Art Paper 150gr', '2026-07-05', 'Issue', 'AP-2603-A', -1100],
        ['RTN/26/0012', 'Art Paper 150gr', '2026-07-09', 'Return', 'AP-2603-A', 140],
        ['OUT/26/0041', 'Art Paper 150gr', '2026-07-14', 'Issue', 'AP-2603-A', -640],
        ['IN/26/0035', 'Duplex 350gr', '2026-07-18', 'Receipt', 'DP-2605', 1500],
        ['OUT/26/0047', 'Duplex 350gr', '2026-07-25', 'Issue', 'DP-2605', -1520],
        ['ADJ/26/0019', 'Duplex 350gr', '2026-07-31', 'Adjustment', 'DP-2605', -30],
        ['OUT/26/0050', 'Duplex 350gr', '2026-08-04', 'Issue', 'DP-2605', -280],
        ['IN/26/0041', 'Art Paper 190gr', '2026-08-12', 'Receipt', 'AP-2608', 1800],
        ['OUT/26/0058', 'Art Paper 190gr', '2026-08-19', 'Issue', 'AP-2608', -950],
    ];
    $rows = array_map(fn ($m) => array_combine(['doc', 'item', 'date', 'type', 'lot', 'change'], $m), $moves);
@endphp
<x-demo.grid-stock-movement :rows="$rows" />
