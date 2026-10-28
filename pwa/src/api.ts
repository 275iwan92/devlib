// Klien Laravel untuk layar login: satu asal (situs yang menyajikan /pwa/), jadi cookie sesi dan token CSRF berlaku apa adanya.
export type Lang = 'id' | 'en';
export type LoginError = 'empty' | 'wrong' | 'network';

const json = { Accept: 'application/json', 'Content-Type': 'application/json' };

export type Links = { register?: string; forgot?: string; device?: string };

/** Token CSRF, bahasa tamu (cookie dl_lang dibaca server) dan alamat tautan dari server. `null` bila server tidak terjangkau. */
export async function loadSession(): Promise<{ csrf: string; lang: Lang; links: Links } | null> {
    try {
        const r = await fetch('/pwa/session', { headers: json, credentials: 'same-origin' });
        if (!r.ok) return null;
        const s = await r.json();
        return { csrf: s.csrf, lang: s.lang === 'en' ? 'en' : 'id', links: s.links ?? {} };
    } catch {
        return null;
    }
}

/** POST /login: `{redirect}` bila berhasil, selain itu kode galat server (empty / wrong) atau network. */
export async function signIn(csrf: string, login: string, password: string): Promise<{ redirect: string } | { error: LoginError }> {
    try {
        const r = await fetch('/login', { method: 'POST', headers: { ...json, 'X-CSRF-TOKEN': csrf }, credentials: 'same-origin', body: JSON.stringify({ login, password }) });
        const b = await r.json().catch(() => ({}));
        if (r.ok && b.redirect) return { redirect: b.redirect };
        return { error: b.error === 'empty' ? 'empty' : r.status === 422 ? 'wrong' : 'network' };
    } catch {
        return { error: 'network' };
    }
}

/** Bahasa dari cookie dl_lang (sinkron, tanpa jaringan); `null` bila belum dipilih atau nilainya tidak sah. */
export function cookieLang(): Lang | null {
    const v = document.cookie.match(/(?:^|;\s*)dl_lang=(id|en)(?:;|$)/);
    return v ? (v[1] as Lang) : null;
}

/** Sama dengan core.js DevLib: cookie dl_lang dibaca server (Locale::lang). */
export function saveLang(lang: Lang) {
    document.cookie = `dl_lang=${lang};path=/;max-age=31536000;SameSite=Lax`;
}
