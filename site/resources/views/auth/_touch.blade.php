{{-- Sasaran sentuh >= 44 px untuk komponen auth di luar x-dl::frame (halaman Daftar / Lupa password di dalam scope PWA). Berlaku hanya di [data-dl-pwa-auth]. --}}
<style>
    /* .dl-plogin memusatkan isi dengan justify-content:center; isi yang lebih tinggi dari layar jadi terpotong di atas dan tak bisa digulir. Margin auto memusatkan tanpa memotong. */
    [data-dl-pwa-auth] { justify-content: flex-start; }
    [data-dl-pwa-auth] > :first-child { margin-top: auto; }
    [data-dl-pwa-auth] > :last-child { margin-bottom: auto; }
    [data-dl-pwa-auth] .dl-langs { align-self: flex-end; gap: 8px; }
    [data-dl-pwa-auth] .dl-flag { min-width: 44px; min-height: 44px; }
    [data-dl-pwa-auth] .dl-dd__btn { min-height: 52px; }
    [data-dl-pwa-auth] .dl-field__trigger { min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; }
</style>
