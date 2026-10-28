@extends('layout', ['title' => 'Lupa password'])
@section('body')
    {{-- Satu kolom HP (kelas phone-login), di dalam scope PWA (/pwa/). Contoh tampilan: tanpa action = PREVIEW. --}}
    @include('auth._touch')
    <main class="dl-plogin" data-dl-pwa-auth>
        <x-dl::alert tone="info" message="Contoh tampilan: tidak ada e-mail yang terkirim dan tidak ada password yang diubah." />
        <x-dl::forgot-box mode="email" login-url="/pwa/" />
        <a class="dl-btn dl-btn--secondary dl-btn--block" href="/pwa/">Kembali ke login</a>
    </main>
@endsection
