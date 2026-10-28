import { useRef } from 'react';
import type { Lang } from './api';
import { useInstall } from './useInstall';

const T = {
    id: { btn: 'Pasang aplikasi', hint: 'Buka seperti aplikasi, layar penuh', title: 'Pasang di iPhone / iPad', s1: 'Buka halaman ini di Safari, lalu ketuk tombol Bagikan.', s2: 'Pilih "Tambah ke Layar Utama".', s3: 'Ketuk "Tambah". Ikon DevLib muncul di layar utama.', close: 'Tutup' },
    en: { btn: 'Install app', hint: 'Opens like an app, full screen', title: 'Install on iPhone / iPad', s1: 'Open this page in Safari, then tap the Share button.', s2: 'Choose "Add to Home Screen".', s3: 'Tap "Add". The DevLib icon appears on your home screen.', close: 'Close' },
} as const;

/** Menu "Pasang aplikasi": dialog bawaan di Android, petunjuk manual di iOS, hilang bila sudah terpasang atau tidak didukung. */
export function InstallPrompt({ lang }: { lang: Lang }) {
    const { mode, install } = useInstall();
    const sheet = useRef<HTMLDialogElement>(null);
    const t = T[lang];

    if (mode === 'installed' || mode === 'none') return null;

    return (
        <>
            <button type="button" className="pinstall" onClick={() => (mode === 'ios' ? sheet.current?.showModal() : install())}>
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                    <path d="M7 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H7zm5 15a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-.9-9V5h1.8v3h2.1L12 12 9 8h2.1z" />
                </svg>
                <span>
                    <strong>{t.btn}</strong>
                    <small>{t.hint}</small>
                </span>
            </button>
            {mode === 'ios' && (
                <dialog ref={sheet} className="pinstall__sheet" aria-labelledby="pinstall-t" onClick={(e) => e.target === sheet.current && sheet.current.close()}>
                    <h2 id="pinstall-t">{t.title}</h2>
                    <ol>
                        <li>{t.s1}</li>
                        <li>{t.s2}</li>
                        <li>{t.s3}</li>
                    </ol>
                    <button type="button" onClick={() => sheet.current?.close()}>
                        {t.close}
                    </button>
                </dialog>
            )}
        </>
    );
}
