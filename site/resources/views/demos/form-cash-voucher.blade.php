@php
    // Single Form transaksional (user 2026-10-21): SATU record tanpa baris detail; siklus ketat Draft -> Confirmed -> Cancelled.
    // Dibuka dari grid alur (/lib/p5-flow-single/form/{id}); Save dan Confirm tetap di form, hanya Back (dan Delete) kembali ke grid.
    $flow ??= null;
    $id = $flow['id'] ?? 'new';
    $dup = request()->filled('copy');
    $status = $id === 'new' ? 'new' : request('status', $flow['status'] ?? 'draft');
    $isNew = $status === 'new';
    $blank = $isNew && ! $dup;
    $r = $flow['row'] ?? null;
    $src = $r ? ['cv_no' => $dup ? null : $r['cv_no'], 'cv_date' => $dup ? now()->format('Y-m-d') : $r['cv_date'], 'payee' => $r['payee'], 'category' => $r['category'],
        'paid_from' => $r['paid_from'], 'amount' => $r['amount'], 'description' => $r['description']] : [];
    // Record baru: isian TIDAK kosong, nilai bawaan yang masuk akal; hanya Payee dan Amount yang harus diisi pengguna.
    $defaults = ['cv_date' => now()->format('Y-m-d'), 'paid_from' => 'Main Cash', 'category' => 'Office supplies'];
    $v = fn ($k) => $blank ? ($defaults[$k] ?? null) : ($src[$k] ?? null);
    $cats = ['Office supplies', 'Maintenance', 'Meals', 'Cleaning', 'Freight'];
    $log = $isNew ? [] : array_values(array_filter([
        in_array($status, ['confirmed', 'cancelled'], true) ? ['at' => now()->format('Y-m-d H:i:s'), 'by' => 'cashier', 'action' => ucfirst($status), 'note' => $status === 'cancelled' ? 'Wrong amount, re-issued.' : null] : null,
        ['at' => '2026-10-02 09:40:00', 'by' => 'cashier', 'action' => 'Saved', 'note' => null],
        ['at' => '2026-10-02 08:12:00', 'by' => 'cashier', 'action' => 'Created', 'note' => null],
    ]));
@endphp
<div class="dl-row" style="margin-bottom:8px">
    <span class="dl-note" style="margin:0">View status:</span>
    @foreach (['draft' => 'Draft', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'] as $sk => $sl)
        <x-dl::button size="sm" :variant="$sk === $status ? 'primary' : 'secondary'" :label="$sl" :href="\App\Support\FlowDemo::formUrl('p5-flow-single', $flow['id'], '', $flow['back'], ['status' => $sk])" />
    @endforeach
</div>
<x-dl::form :title="$isNew ? 'Cash Voucher · New'.($dup ? ' (copy)' : '') : 'Cash Voucher · '.$src['cv_no']" :subtitle="$blank ? null : $v('payee')"
    panel="top" kind="document" :status="$status" :strict="true" :log="$log"
    :action="url()->current()" method="POST" :back="$flow['back']" :print-url="\App\Support\FlowDemo::printUrl('p5-flow-single', $flow['id'], '', $status, null)" :hidden="['status' => $status, 'from' => $flow['back']]"
    :created-by="$isNew ? null : 'cashier'" :created-at="$isNew ? null : '2026-10-02 08:12:00'" :updated-by="$isNew ? null : 'cashier'" :updated-at="$isNew ? null : '2026-10-02 09:40:00'">
    <x-dl::form.row :cols="3">
        <x-dl::field.text name="cv_no" label="Voucher No." :value="$v('cv_no')" placeholder="(automatic)" code readonly :case-toggle="false" hint="Made on the first Save." />
        <x-dl::field.date name="cv_date" label="Date" :value="$v('cv_date')" required />
        <x-dl::field.select name="paid_from" label="Paid from" :options="['Main Cash' => 'Main Cash', 'Petty Cash' => 'Petty Cash']" :value="$v('paid_from')" required />
    </x-dl::form.row>
    <x-dl::form.row :cols="3">
        <x-dl::field.text name="payee" label="Payee" :value="$v('payee')" placeholder="Who receives the money" required />
        <x-dl::field.select name="category" label="Category" :options="array_combine($cats, $cats)" :value="$v('category')" required />
        <x-dl::field.number-words name="amount" label="Amount" currency="IDR" :value="$v('amount')" required />
    </x-dl::form.row>
    <x-dl::form.row>
        <x-dl::field.text name="description" label="Description" multiline :value="$v('description')" />
    </x-dl::form.row>
</x-dl::form>
<p class="dl-note">Single Form <b>transaksional</b>: satu record tanpa baris. Baru (Draft, belum tersimpan): Save · Undo. Draft tersimpan: terkunci, Delete · Edit · Print · Confirm. Confirmed: Cancel (wajib alasan) · Print, semua isian read-only dan berwarna. Cancelled: Print. Cap status di pojok kanan atas.</p>
