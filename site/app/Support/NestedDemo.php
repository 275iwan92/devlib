<?php

namespace App\Support;

use Illuminate\Support\Str;

// Nested Grid (skills/devlib/grid.md): grid ke grid saja, tanpa form. Satu tingkat = satu halaman beralamat sendiri; link di sel (kolom 'url' + 'ref')
// membuka grid lain, tingkatnya tidak dibatasi. Jejak (tingkat sebelum halaman ini) dibawa di query t = "type:key,type:key" supaya Back, breadcrumb,
// dan muat ulang selalu sampai di tempat yang sama; segmen pertama = daftar asal (home:orders | home:purchases), jadi jejak ada sejak tingkat pertama.
// Dua contoh data: Customer Order (customer, order, product) dan Purchase Order (supplier, po, item). Tanpa basis data: baris dari DemoData.
final class NestedDemo
{
    public const SLUG = 'p5-grid-nested';

    private const TYPES = ['customer' => 'Customer', 'order' => 'Order', 'product' => 'Product', 'supplier' => 'Supplier', 'po' => 'Purchase Order', 'item' => 'Item'];

    private const HOME = ['orders' => ['Customer Orders', 'grid-nested'], 'purchases' => ['Purchase Orders', 'grid-nested-purchase']];   // [judul, id section di halaman katalog]

    private const MAX_TRAIL = 24;   // query dari luar: jejak dipotong sebelum diproses
    private const MAX_CRUMBS = 5;   // lebih panjang: pertama + "…" + tiga terakhir

    public static function orders(): array
    {
        return array_map(fn ($r) => $r + ['customer_slug' => Str::slug($r['customer'])], DemoData::customerOrders());
    }

    public static function purchases(): array
    {
        return array_map(fn ($r) => $r + ['supplier_slug' => Str::slug($r['supplier'])], DemoData::purchaseOrders());
    }

    private static function order(string $id): ?array
    {
        return collect(self::orders())->firstWhere('id', (int) $id);
    }

    private static function po(string $id): ?array
    {
        return collect(self::purchases())->firstWhere('id', (int) $id);
    }

    private static function itemName(string $code): ?string
    {
        foreach (self::purchases() as $p) {
            foreach ($p['lines'] as $l) {
                if ($l['item'] === $code) {
                    return $l['item_label'];
                }
            }
        }

        return null;
    }

    private static function customerName(string $slug): ?string
    {
        return collect(self::orders())->firstWhere('customer_slug', $slug)['customer'] ?? null;
    }

    private static function productName(string $code): ?string
    {
        foreach (self::orders() as $o) {
            foreach ($o['lines'] as $l) {
                if ($l['product'] === $code) {
                    return $l['product_label'];
                }
            }
        }

        return null;
    }

    public static function label(string $type, string $key): ?string
    {
        return match ($type) {
            'home' => self::HOME[$key][0] ?? null,
            'supplier' => collect(self::purchases())->firstWhere('supplier_slug', $key)['supplier'] ?? null,
            'po' => self::po($key)['po_no'] ?? null,
            'item' => self::itemName($key),
            'customer' => self::customerName($key),
            'order' => self::order($key)['co_no'] ?? null,
            'product' => self::productName($key),
            default => null,
        };
    }

    public static function root(): string
    {
        return '/lib/'.self::SLUG;
    }

    // Jejak "type:key,type:key" -> [[type, key], ...]; segmen tak sah dibuang (query bisa diutak-atik), "home" hanya di awal, dan tingkat yang
    // muncul lagi = kembali ke sana (jejak dipotong, tidak menumpuk).
    public static function parse($t): array
    {
        $out = [];
        foreach (array_slice(array_filter(explode(',', is_string($t) ? $t : '')), 0, self::MAX_TRAIL) as $seg) {
            [$type, $key] = array_pad(explode(':', $seg, 2), 2, '');
            if (self::label($type, $key) === null || ($type === 'home' && $out)) {
                continue;
            }
            $i = array_search([$type, $key], $out, true);
            $out = $i === false ? $out : array_slice($out, 0, $i);
            $out[] = [$type, $key];
        }

        return $out;
    }

    public static function home(string $key): array
    {
        return [['home', $key]];
    }

    public static function chain(array $trail): string
    {
        return implode(',', array_map(fn ($s) => $s[0].':'.$s[1], $trail));
    }

    public static function url(string $type, string $key, array $trail = []): string
    {
        if ($type === 'home') {
            return self::root().'#'.self::HOME[$key][1];
        }

        return self::root().'/'.$type.'/'.rawurlencode($key).($trail ? '?t='.rawurlencode(self::chain($trail)) : '');
    }

    // Templat alamat untuk kolom grid: {id} = kunci baris, {kolom} = nilai kolom baris itu (diisi di peramban). $chain = jejak + tingkat sekarang.
    public static function tpl(string $type, string $field, array $chain = []): string
    {
        return self::root().'/'.$type.'/{'.$field.'}'.($chain ? '?t='.rawurlencode(self::chain($chain)) : '');
    }

    // Data satu tingkat: judul, ringkasan, baris, jejak, alamat Back. null = tidak ada (404).
    public static function level(string $type, string $key, $t): ?array
    {
        $title = isset(self::TYPES[$type]) ? self::label($type, $key) : null;
        if ($title === null) {
            return null;
        }
        $trail = self::parse($t);
        $at = array_search([$type, $key], $trail, true);
        $trail = $at === false ? $trail : array_slice($trail, 0, $at);   // tingkat ini sudah di jejak = kembali ke sana
        $money = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $qty = fn ($rows) => number_format(array_sum(array_column($rows, 'qty')), 0, ',', '.');
        $orders = self::orders();
        $pos = self::purchases();
        switch ($type) {
            case 'customer':
                $rows = array_values(array_filter($orders, fn ($o) => $o['customer_slug'] === $key));
                $sub = count($rows).' orders · total '.$money(array_sum(array_column($rows, 'total')));
                break;
            case 'order':
                $o = self::order($key);
                $rows = array_map(fn ($l, $i) => $l + ['id' => $i + 1, 'subtotal' => $l['qty'] * $l['price']], $o['lines'], array_keys($o['lines']));
                $sub = $o['customer'].' · '.$o['co_date'].' · total '.$money($o['total']);
                break;
            case 'product':
                $rows = [];
                foreach ($orders as $o) {
                    foreach ($o['lines'] as $l) {
                        if ($l['product'] === $key) {
                            $rows[] = ['id' => $o['id'], 'co_no' => $o['co_no'], 'customer' => $o['customer'], 'customer_slug' => $o['customer_slug'], 'qty' => $l['qty'], 'uom' => $l['uom'], 'price' => $l['price']];
                        }
                    }
                }
                $sub = count($rows).' order lines · total qty '.$qty($rows);
                break;
            case 'supplier':
                $rows = array_values(array_filter($pos, fn ($p) => $p['supplier_slug'] === $key));
                $sub = count($rows).' purchase orders · total '.$money(array_sum(array_column($rows, 'total')));
                break;
            case 'po':
                $p = self::po($key);
                $rows = array_map(fn ($l, $i) => $l + ['id' => $i + 1], $p['lines'], array_keys($p['lines']));
                $sub = $p['supplier'].' · '.$p['po_date'].' · total '.$money($p['total']);
                break;
            default:   // item
                $rows = [];
                foreach ($pos as $p) {
                    foreach ($p['lines'] as $l) {
                        if ($l['item'] === $key) {
                            $rows[] = ['id' => $p['id'], 'po_no' => $p['po_no'], 'supplier' => $p['supplier'], 'supplier_slug' => $p['supplier_slug'], 'qty' => $l['qty'], 'uom' => $l['uom'], 'price' => $l['price']];
                        }
                    }
                }
                $sub = count($rows).' purchase lines · total qty '.$qty($rows);
        }
        $chain = array_merge($trail, [[$type, $key]]);
        $crumbs = [];
        foreach ($trail as $i => [$ty, $ke]) {
            $crumbs[] = [self::label($ty, $ke), self::url($ty, $ke, array_slice($trail, 0, $i))];
        }
        if (count($crumbs) > self::MAX_CRUMBS) {   // jejak panjang: pertama, "…" (ke tingkat tersembunyi terdekat), tiga terakhir
            $crumbs = [$crumbs[0], ['…', $crumbs[count($crumbs) - 4][1]], ...array_slice($crumbs, -3)];
        }
        $back = $trail ? self::url(...[...end($trail), array_slice($trail, 0, -1)]) : self::root();

        return ['type' => $type, 'key' => $key, 'kind' => self::TYPES[$type], 'title' => $title, 'sub' => $sub, 'rows' => $rows, 'chain' => $chain, 'trail' => $trail,
            'back' => $back, 'start' => count($trail) > 2 ? self::url(...[...$trail[0], []]) : null, 'crumbs' => array_column($crumbs, 1, 0), 'self' => self::url($type, $key, $trail)];
    }
}
