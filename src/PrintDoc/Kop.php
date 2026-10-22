<?php

/*
 * REMARK
 * Tujuan       : Satu-satunya tempat yang memutuskan isi kop perusahaan pada SEMUA cetakan (x-dl::print-doc, print-sheet, print-out, report): logo, nama, nama badan hukum, alamat.
 * Sumber hulu  : array company dari Document::companyUsing() / companyOf() atau prop company / companyPpn.
 * Input        : kunci company: name, initials, logo, logoBw, tagline, lines, address, contact, npwp, + BARU (opsional) style, legal_name.
 * Output       : bendera tampil: styled, badge, logo, side, name, legal, addr (+ nilai name, initials, logo, logoBw, tagline, legal_name, npwp).
 * Dipakai oleh : partials/print-doc-kop, components/print-sheet, components/print-out (dan x-dl::report yang turunannya).
 * Status       : Aktif. Tanpa style: persis perilaku lama (logo ATAU lencana inisial + nama).
 */

namespace Devlib\PrintDoc;

class Kop
{
    public const STYLES = ['logo_name_address', 'logo_name', 'name'];

    /**
     * @return array{styled: bool, badge: bool, logo: bool, side: bool, name: bool, legal: bool, addr: bool, nameText: string, initials: string, logoUrl: ?string, logoBw: ?string, tagline: ?string, legalText: string, npwp: ?string}
     */
    public static function of(array $c): array
    {
        $raw = (string) ($c['name'] ?? '');
        $name = trim($raw);
        $url = ($c['logo'] ?? null) ?: null;
        $style = in_array($c['style'] ?? null, self::STYLES, true) ? $c['style'] : null;
        $legal = trim((string) ($c['legal_name'] ?? ''));
        // Nama PT yang sama dengan nama (beda huruf besar/kecil dan spasi saja) tidak ditulis dua kali.
        $legal = self::same($legal, $name) ? '' : $legal;

        if ($style === null) { // perilaku lama: logo menggantikan inisial + nama
            $logo = $url !== null;
            $showName = ! $logo;
            $badge = ! $logo;
            $addr = true;
        } else {
            $logo = $url !== null && $style !== 'name';
            $showName = $name !== '' || ! $logo;
            $badge = ! $logo && $name === '';
            $addr = $style === 'logo_name_address';
        }

        return [
            'styled' => $style !== null, 'badge' => $badge, 'logo' => $logo, 'side' => $logo && $showName, 'name' => $showName, 'legal' => $legal !== '', 'addr' => $addr,
            'nameText' => $raw, 'initials' => (string) ($c['initials'] ?? mb_substr($c['name'] ?? 'TM', 0, 2)), 'logoUrl' => $url, 'logoBw' => ($c['logoBw'] ?? null) ?: $url,
            'tagline' => ($c['tagline'] ?? null) ?: null, 'legalText' => $legal, 'npwp' => ($c['npwp'] ?? null) ?: null,
        ];
    }

    private static function same(string $a, string $b): bool
    {
        return $a === '' || mb_strtolower(preg_replace('/\s+/u', '', $a)) === mb_strtolower(preg_replace('/\s+/u', '', $b));
    }
}
