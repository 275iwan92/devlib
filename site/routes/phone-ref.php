<?php

use App\Support\PhoneRef;
use Illuminate\Support\Facades\Route;

// Layar contoh "Phone shell" (halaman katalog p7-phone, galeri acuan). Dimuat di dalam grup devlib.auth oleh routes/web.php.
// Data fiktif netral (App\Support\PhoneRef); tidak ada yang tersimpan. Dibuka di <iframe> oleh x-dl::device-frame, jadi @media dan tinggi layar mengikuti bingkai.
Route::get('/ref/phone-shell', fn () => redirect()->route('lib', 'p7-phone'));

Route::get('/ref/phone/screen/{name}', function (string $name) {
    abort_unless(isset(PhoneRef::SCREENS[$name]), 404);

    return view('phone-ref.screen-'.$name, ['title' => PhoneRef::SCREENS[$name][0], 'active' => PhoneRef::SCREENS[$name][1]]);
})->name('phone.ref');

// Isi yang dibingkai shell: form, grid, report (halaman biasa; shell membaca aksinya dan memunculkannya di bilah).
Route::get('/ref/phone/inner/{name}', function (string $name) {
    abort_unless(in_array($name, ['form', 'grid', 'report'], true), 404);

    return view('phone-ref.inner-'.$name);
})->name('phone.ref.inner');
