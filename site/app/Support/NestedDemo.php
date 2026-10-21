<?php

namespace App\Support;

use Illuminate\Support\Str;

// Nested Grid (skills/devlib/grid.md): grid ke grid saja, tanpa form. Satu tingkat = satu halaman beralamat sendiri; link di sel (kolom 'url' + 'ref')
// membuka grid lain, tingkatnya tidak dibatasi. Jejak (tingkat sebelum halaman ini) dibawa di query t = "type:key,type:key" supaya Back, breadcrumb,
// dan muat ulang selalu sampai di tempat yang sama. Tanpa basis data: baris dari DemoData::customerOrders().
final class NestedDemo
{
    public const SLUG = 'p5-grid-nested';

    private const TYPES = ['customer' => 'Customer', 'order' => 'Order', 'product' => 'Product'];

    public static function orders(): array
    {
        return array_map(fn ($r) => $r + ['customer_slug' => Str::slug($r['customer'])], DemoData::customerOrders());
    }

    private static function order(string $id): ?array
    {
        return collect(self::orders())->firstWhere('id', (int) $id);
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

    // Jejak "type:key,type:key" -> [[type, key], ...]; segmen tak sah dibuang (query bisa diutak-atik).
    public static function parse(?string $t): array
    {
        $out = [];
        foreach (array_filter(explode(',', (string) $t)) as $seg) {
            [$type, $key] = array_pad(explode(':', $seg, 2), 2, '');
            if (isset(self::TYPES[$type]) && self::label($type, $key) !== null) {
                $out[] = [$type, $key];
            }
        }

        return $out;
    }

    public static function chain(array $trail): string
    {
        return implode(',', array_map(fn ($s) => $s[0].':'.$s[1], $trail));
    }

    public static function url(string $type, string $key, array $trail = []): string
    {
        return self::root().'/'.$type.'/'.rawurlencode($key).($trail ? '?t='.rawurlencode(self::chain($trail)) : '');
    }

    // Templat alamat untuk kolom grid: {id} = kunci baris, {kolom} = nilai kolom baris itu (diisi di peramban). $chain = jejak + tingkat sekarang.
    public static function tpl(string $type, string $field, array $chain = []): string
    {
        return self::root().'/'.$type.'/{'.$field.'}'.($chain ? '?t='.rawurlencode(self::chain($chain)) : '');
    }

    // Data satu tingkat: judul, ringkasan, baris, jejak, alamat Back. null = tidak ada (404).
    public static function level(string $type, string $key, ?string $t): ?array
    {
        $title = self::label($type, $key);
        if ($title === null) {
            return null;
        }
        $trail = self::parse($t);
        $all = self::orders();
        $money = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        switch ($type) {
            case 'customer':
                $rows = array_values(array_filter($all, fn ($o) => $o['customer_slug'] === $key));
                $sub = count($rows).' orders · total '.$money(array_sum(array_column($rows, 'total')));
                break;
            case 'order':
                $o = self::order($key);
                $rows = array_map(fn ($l, $i) => $l + ['id' => $i + 1, 'subtotal' => $l['qty'] * $l['price']], $o['lines'], array_keys($o['lines']));
                $sub = $o['customer'].' · '.$o['co_date'].' · total '.$money($o['total']);
                break;
            default:
                $rows = [];
                foreach ($all as $o) {
                    foreach ($o['lines'] as $l) {
                        if ($l['product'] === $key) {
                            $rows[] = ['id' => $o['id'], 'co_no' => $o['co_no'], 'customer' => $o['customer'], 'customer_slug' => $o['customer_slug'], 'qty' => $l['qty'], 'uom' => $l['uom'], 'price' => $l['price']];
                        }
                    }
                }
                $sub = count($rows).' order lines · total qty '.number_format(array_sum(array_column($rows, 'qty')), 0, ',', '.');
        }
        $chain = array_merge($trail, [[$type, $key]]);
        $crumbs = [];
        foreach ($trail as $i => [$ty, $ke]) {
            $crumbs[self::label($ty, $ke)] = self::url($ty, $ke, array_slice($trail, 0, $i));
        }
        $back = $trail ? self::url(...[...end($trail), array_slice($trail, 0, -1)]) : self::root();

        return ['type' => $type, 'key' => $key, 'kind' => self::TYPES[$type], 'title' => $title, 'sub' => $sub, 'rows' => $rows, 'chain' => $chain, 'trail' => $trail,
            'back' => $back, 'crumbs' => $crumbs, 'self' => self::url($type, $key, $trail)];
    }
}
