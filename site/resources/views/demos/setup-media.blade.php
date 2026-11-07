{{-- Halaman Setup Music dan Screen animation: tampil di dialog Setup Layer Menu bila ditaruh di slot profilePages x-dl::layer-shell; di sini dirender apa adanya. --}}
<div class="dl-demo">
    <x-dl::music-player storage-key="dl-music-demo2" :tracks="[['file' => 'sample.mp3', 'title' => 'Sample Track', 'author' => 'Sample Author'], ['file' => 'sample2.mp3', 'title' => 'Second Track', 'author' => 'Sample Author']]" base="/audio" />
    <x-dl::setup-media />
</div>
