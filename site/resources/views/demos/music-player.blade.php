{{-- Pemutar musik latar: bawaan diam; satu trek contoh (nada pendek dibuat di browser tidak tersedia, jadi berkas contoh = alamat yang tidak harus ada). --}}
<div class="dl-demo dl-demo--row">
    <x-dl::music-player storage-key="dl-music-demo" :tracks="[['file' => 'sample.mp3', 'title' => 'Sample Track', 'author' => 'Sample Author', 'url' => 'https://example.com', 'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0/']]" base="/audio" />
</div>
