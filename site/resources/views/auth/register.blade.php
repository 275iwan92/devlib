@extends('layout', ['title' => 'Daftar'])
@section('body')
    {{-- Satu kolom HP (kelas phone-login: isian 52 px, teks 16 px, area aman), di dalam scope PWA (/pwa/). Contoh tampilan: tanpa action = PREVIEW. --}}
    @include('auth._touch')
    <main class="dl-plogin" data-dl-pwa-auth>
        <x-dl::alert tone="info" message="Contoh tampilan: tidak ada akun yang dibuat dan tidak ada e-mail atau OTP yang terkirim." />
        <x-dl::register-box login-url="/pwa/" />
        <a class="dl-btn dl-btn--secondary dl-btn--block" href="/pwa/">Kembali ke login</a>
    </main>
@endsection
