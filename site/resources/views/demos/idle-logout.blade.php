<div class="dl-demo">
    <x-dl::button variant="primary" icon="fa-play" label="Mulai simulasi" onclick="window.dlIdleLogout && window.dlIdleLogout.reset()" />
    <p class="dl-note">Simulasi memakai batas <code>minutes="1"</code> dan <code>warn="0.75"</code>: diam saja 15 detik, lalu peringatan muncul di pojok kanan bawah dengan hitung mundur 45 detik. Gerakan tikus atau ketikan sebelum itu menunda hitungan. Saat peringatan terbuka hanya tombol <b>Tetap masuk</b> yang memperpanjang. Saat habis hanya tampil pesan: demo ini memakai <code>preview</code>, tidak mengirim apa pun dan tidak mengeluarkan Anda dari situs.</p>
    <p class="dl-note">Di aplikasi: <code>&lt;x-dl::idle-logout logout-url="/logout" ping-url="/session/ping" /&gt;</code> satu kali di layout (bawaan 30 menit, peringatan 2 menit sebelumnya).</p>
</div>
<x-dl::idle-logout :minutes="1" :warn="0.75" logout-url="/logout" ping-url="/session/ping" preview />
