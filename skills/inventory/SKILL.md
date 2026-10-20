---
name: inventory
description: Aturan domain inventori/stok untuk SEMUA proyek milik Iwan (sstock, tmerp, dst.): kode barang vs batch, kedaluwarsa, FEFO, peringatan batch, titik pesan ulang per produk, satuan stok/beli/pakai. Muat sebelum merancang atau mengubah master barang/produk, batch, kedaluwarsa, urutan pengeluaran, atau perhitungan Reorder Point.
---

# Inventory: aturan domain lintas proyek

Hasil riset (keyakinan tinggi: pola GS1, SAP, Odoo) yang dipegang pemilik sebagai dasar desain di semua proyek inventori. Proyek mengacu ke sini; menyimpang hanya dengan alasan kuat dan izin pemilik, dan alasannya dicatat di skill proyek itu. Aturan kerja, layar, dan siklus dokumen ada di skill proyek (mis. `stoku`) dan `devlib`.

## 1. Tanggal kedaluwarsa TIDAK masuk kode barang
Kode barang tetap satu per produk; batch dan kedaluwarsa adalah data terpisah.
- GS1 memisahkan nomor batch (AI 10) dan kedaluwarsa (AI 17, format YYMMDD) dari kode barang (GTIN).
- SAP menyimpan masa simpan di master material dan menghitung tanggal kedaluwarsa per batch saat barang diterima.
- Penamaan SKU hanya memasukkan atribut yang stabil, seperti kategori.

## 2. Urutan pengambilan = FEFO, bukan FIFO
- Ukuran utamanya tanggal kedaluwarsa; tanggal masuk hanya pemecah seri bila tanggalnya sama.
- FIFO cukup hanya bila urutan datang sama dengan urutan kedaluwarsa, atau barangnya tidak punya kedaluwarsa.

## 3. Mengambil batch yang kedaluwarsanya lebih lama = PERINGATAN saja
Jangan memblokir. Memblokir dengan kode alasan ditolak 0-3, jadi bukan standar.

## 4. Titik pesan ulang dihitung PER PRODUK, bukan per batch
Dari stok yang tersedia: stok fisik dikurangi yang akan keluar, ditambah yang sudah dipesan. Pemicu: jumlah itu di bawah minimum (Reorder Point); pesanan mengisi sampai maksimum.

## 5. Satuan barang: tiga satuan, didefinisikan jelas di master (keputusan pemilik 2026-10-20)
Satu barang bisa punya tiga satuan berbeda. Ketiganya wajib tertulis di master barang, tidak boleh tersirat.
- **Satuan stok** (base): satuan buku besar. Semua saldo, kartu stok, biaya rata-rata, Reorder Point, dan Reorder Qty dalam satuan ini. Satu per barang, wajib. Tidak boleh diubah setelah ada transaksi, kecuali lewat dokumen penyesuaian.
- **Satuan beli** (disebut juga **satuan penerimaan**; dua nama untuk satu satuan, pakai salah satunya secara konsisten per layar): satuan di PO, penerimaan, dan harga supplier (mis. karton).
- **Satuan pakai**: satuan di pengeluaran, permintaan, dan resep/BOM (mis. pcs).
- **Satuan beli WAJIB diisi.** Satuan stok atau pakai yang dikosongkan **disalin dari satuan yang terisi** saat disimpan: stok kosong = satuan beli; pakai kosong = satuan stok (setelah stok terisi). Satuan hasil salinan sama dengan sumbernya, jadi faktor 1 dan tidak perlu baris Conversions. Hasil salinan ditampilkan di form setelah Save, bukan disimpan diam-diam kosong.
- **Master barang WAJIB punya bagian Conversions** (daftar per barang: satuan, faktor ke satuan stok; 1 karton = 24 pcs). Tiap satuan selain satuan stok yang dipakai barang (beli, pakai, atau input dokumen) harus ada barisnya di Conversions. Faktor > 0, boleh desimal. Tabel konversi global (1 kg = 1000 g) hanya contoh isian awal saat menambah baris Conversions, BUKAN pengganti: yang berlaku hanya yang tercatat di barang itu.
- **Satuan berbeda = konversi WAJIB dicek.** Di master: satuan beli/pakai yang berbeda dari satuan stok tidak boleh disimpan tanpa faktor valid (> 0). Di dokumen: bila satuan yang diinput berbeda dari satuan stok, sistem memeriksa daftar Conversions barang itu (hanya milik barang itu, bukan global); tidak ada = **ditolak** dengan pesan yang menyebut barang dan pasangan satuannya. Dilarang diam-diam memakai faktor 1 atau menebak. Hasil konversi diperiksa ulang saat Confirm (faktor bisa berubah sejak draf).
- Dokumen menyimpan jumlah dalam satuan yang diinput dan hasil konversinya, dengan faktor saat itu sebagai snapshot supaya perubahan faktor tidak mengubah histori. Buku besar hanya memakai satuan stok.
- Harga beli per satuan beli; biaya rata-rata per satuan stok (harga beli dibagi faktor).
- Satuan selalu tampil di samping angka (kolom dan form).
- Acuan: sstock baru punya satu satuan per produk (`products.unit`), konversi global (`UnitConversion`), dan `entered_unit` di baris dokumen. Satuan beli dan pakai per barang belum ada; bangun bila diminta.

## Turunan untuk implementasi (dari riset, bukan kutipannya)
- Pisahkan master produk (kode tetap, kategori, satuan, penanda melacak kedaluwarsa, Reorder Point/Qty, Lead Time, masa simpan bila ada) dari batch (nomor batch, tanggal kedaluwarsa, jumlah). Barang tanpa kedaluwarsa tidak wajib batch.
- Kedaluwarsa per batch dihitung/diisi saat barang diterima (masa simpan di master bila ada). Stok per batch ikut buku besar, dan pembatalan harus mengembalikan batch yang sama.
- Pengeluaran menyarankan batch menurut FEFO; memilih batch lain boleh dengan peringatan yang terlihat.
- Reorder Point dihitung per produk (jumlah semua batch yang masih layak, batch kedaluwarsa tidak dihitung) dan memakai pesanan terbuka sebagai "sudah dipesan".
- Batch yang sudah/akan kedaluwarsa mendapat peringatan terpisah (kartu ringkasan dan laporan kedaluwarsa).

## Implementasi acuan
- **sstock** (acuan terdekat): lihat skill `stok-batch-expiry` di `sstock/.claude/skills/`. Produk dan batch, `StockDocuments` (FEFO), `Product::availableSql()`.
- **tmerp** punya modul inventaris sendiri (`Services/Inventory`, `MatRaw`, PPIC): baca kodenya dulu dan selaraskan bertahap; jangan mengubah perilaku yang sedang dipakai tanpa izin pemilik.
