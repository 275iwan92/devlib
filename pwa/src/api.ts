// Klien Laravel untuk layar login: satu asal (situs yang menyajikan /pwa/), jadi cookie sesi dan token CSRF berlaku apa adanya.
export type Lang = 'id' | 'en';
export type LoginError = 'empty' | 'wrong' | 'network';

const json = { Accept: 'application/json', 'Content-Type': 'application/json' };

/** Token CSRF + bahasa tamu (cookie dl_lang dibaca server). `null` bila server tidak terjangkau. */
export async function loadSession(): Promise<{ csrf: string; lang: Lang } | null> {
    try {
        const r = await fetch('/pwa/session', { headers: json, credentials: 'same-origin' });
        if (!r.ok) return null;
        const s = await r.json();
        return { csrf: s.csrf, lang: s.lang === 'en' ? 'en' : 'id' };
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

/** Sama dengan core.js DevLib: cookie dl_lang dibaca server (Locale::lang). */
export function saveLang(lang: Lang) {
    document.cookie = `dl_lang=${lang};path=/;max-age=31536000;SameSite=Lax`;
}
