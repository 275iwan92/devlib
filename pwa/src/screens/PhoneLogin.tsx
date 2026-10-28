import { useId, useState, type FormEvent, type ReactNode } from 'react';
import type { Lang, LoginError } from '../api';
import { InstallPrompt } from '../InstallPrompt';

const T = {
    id: { title: 'Masuk', sub: 'Gunakan akun terdaftar untuk mengakses aplikasi.', user: 'Username atau email', pass: 'Password', forgot: 'Lupa password?', go: 'Masuk', or: 'atau', noacc: 'Belum punya akun?', reg: 'Daftar', desk: 'Tampilan desktop', show: 'Tampilkan password', hide: 'Sembunyikan password',
        empty: 'Isi username/email dan password.', wrong: 'Username atau password salah.', network: 'Server tidak terjangkau. Periksa koneksi, lalu coba lagi.' },
    en: { title: 'Sign in', sub: 'Use your registered account to access the application.', user: 'Username or email', pass: 'Password', forgot: 'Forgot password?', go: 'Sign in', or: 'or', noacc: "Don't have an account?", reg: 'Register', desk: 'Desktop view', show: 'Show password', hide: 'Hide password',
        empty: 'Enter your username/email and password.', wrong: 'Incorrect username or password.', network: 'Server unreachable. Check your connection and try again.' },
} as const;

export type PhoneLoginProps = {
    lang: Lang;
    onLang: (lang: Lang) => void;
    /** Kirim ke server; `null` = berhasil (halaman berpindah), selain itu kode galat. */
    onSignIn: (login: string, password: string) => Promise<LoginError | null>;
    /** Server tidak terjangkau saat layar dibuka. */
    offline?: boolean;
    csrfToken?: string;
    forgotUrl?: string;
    registerUrl?: string;
    deviceAction?: string;
};

const Svg = ({ children }: { children: ReactNode }) => (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
        {children}
    </svg>
);

/** Layar login HP (padanan React dari x-dl::phone-login + login-box): satu kolom, isian 52 px teks 16 px, warna dari token --dl-*. */
export function PhoneLogin({ lang, onLang, onSignIn, offline = false, csrfToken, forgotUrl = '#', registerUrl = '#', deviceAction }: PhoneLoginProps) {
    const [show, setShow] = useState(false);
    const [error, setError] = useState<LoginError | null>(null);
    const [busy, setBusy] = useState(false);
    const id = useId();
    const t = T[lang];

    const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        setBusy(true);
        setError(await onSignIn(String(f.get('login') ?? ''), String(f.get('password') ?? '')));
        setBusy(false);
    };
    const shown = error ?? (offline ? 'network' : null);

    return (
        <main className="plogin">
            <div className="plogin__top">
                <span className="plogin__logo" aria-hidden="true">
                    <Svg>
                        <path d="M12 2a8 8 0 0 0-8 8v5a3 3 0 0 0 3 3h1v-7H6v-1a6 6 0 0 1 12 0v1h-2v7h2v1a2 2 0 0 1-2 2h-3v2h3a4 4 0 0 0 4-4V10a8 8 0 0 0-8-8z" />
                    </Svg>
                </span>
                <div className="plogin__langs" role="group" aria-label="Language">
                    {(['en', 'id'] as const).map((l) => (
                        <button key={l} type="button" aria-pressed={lang === l} onClick={() => onLang(l)}>
                            {l === 'en' ? 'GB' : 'ID'}
                        </button>
                    ))}
                </div>
            </div>
            <form className="plogin__box" onSubmit={onSubmit} noValidate>
                <h1>{t.title}</h1>
                <p className="plogin__sub">{t.sub}</p>
                {shown && (
                    <p className="plogin__error" role="alert">
                        {t[shown]}
                    </p>
                )}
                <label htmlFor={`${id}u`}>{t.user}</label>
                <div className="plogin__field">
                    <Svg>
                        <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4 0-8 2-8 5v3h16v-3c0-3-4-5-8-5z" />
                    </Svg>
                    <input id={`${id}u`} name="login" autoComplete="username" autoCapitalize="none" spellCheck={false} />
                </div>
                <label htmlFor={`${id}p`}>{t.pass}</label>
                <div className="plogin__field">
                    <Svg>
                        <path d="M17 9V7a5 5 0 0 0-10 0v2H5v12h14V9h-2zM9 7a3 3 0 0 1 6 0v2H9V7z" />
                    </Svg>
                    <input id={`${id}p`} name="password" type={show ? 'text' : 'password'} autoComplete="current-password" />
                    <button type="button" aria-pressed={show} aria-label={show ? t.hide : t.show} onClick={() => setShow(!show)}>
                        <Svg>
                            <path d="M12 5C6 5 2 12 2 12s4 7 10 7 10-7 10-7-4-7-10-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-6a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                        </Svg>
                    </button>
                </div>
                <a className="plogin__forgot" href={forgotUrl}>
                    {t.forgot}
                </a>
                <button className="plogin__go" type="submit" disabled={busy}>
                    {t.go}
                </button>
                <p className="plogin__or">
                    <span>{t.or}</span>
                </p>
                <p className="plogin__reg">
                    {t.noacc} <a href={registerUrl}>{t.reg}</a>
                </p>
            </form>
            <InstallPrompt lang={lang} />
            {deviceAction && (
                <form method="POST" action={deviceAction} className="plogin__desk">
                    {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}
                    <input type="hidden" name="ui" value="desktop" />
                    <button type="submit">{t.desk}</button>
                </form>
            )}
        </main>
    );
}
