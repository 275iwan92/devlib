<script>
    // Turunan JS: hanya method yang berbeda yang ditulis; sisanya diwarisi dari Kanban standar.
    (window.DevlibReady ||= []).push(Devlib => {
        class KanbanProduksi extends Devlib.classes.Kanban {
            // Aturan tambahan pabrik: kartu yang sudah QC tidak boleh balik ke Finishing lewat drag.
            allowed(from, to) {
                return super.allowed(from, to) && !(from === 'qc' && to === 'finish');
            }
            // Tampilan kartu: kartu induk + penanda nomor WO.
            cardHtml(card) {
                return super.cardHtml(card).replace('dl-kcard__title">', 'dl-kcard__title"><span class="dl-badge dl-badge--info">WO</span> ');
            }
        }
        Devlib.register(KanbanProduksi);
    });
</script>
<x-demo.kanban-produksi me="Demo Admin" :members="['Budi Santoso', 'Rina Wulandari', 'Andi Pratama']" :cards="\App\Support\DemoData::kanbanCards()" />
