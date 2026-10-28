import { useId, useState, type FormEvent } from 'react';

export type PhoneLoginProps = {
    action?: string;
    csrfToken?: string;
    error?: string | null;
    username?: string;
    forgotUrl?: string;
    brand?: string;
    tagline?: string;
    deviceAction?: string;
};

/** Layar login HP (padanan React dari x-dl::phone-login): satu kolom 320 sampai 440 px, kolom isian 52 px, teks 16 px. */
export function PhoneLogin({
    action = '',
    csrfToken,
    error = null,
    username = '',
    forgotUrl = '#',
    brand = 'DevLib',
    tagline = 'Masuk untuk melanjutkan',
    deviceAction,
}: PhoneLoginProps) {
    const [show, setShow] = useState(false);
    const id = useId();
    // Tanpa action (peraga) formulir tidak dikirim.
    const onSubmit = (e: FormEvent) => action || e.preventDefault();

    return (
        <main className="plogin">
            <form className="plogin__box" method="POST" action={action || undefined} onSubmit={onSubmit}>
                {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}
                <h1 className="plogin__brand">{brand}</h1>
                <p className="plogin__tag">{tagline}</p>
                {error && (
                    <p className="plogin__error" role="alert">
                        {error}
                    </p>
                )}
                <label htmlFor={`${id}u`}>Nama pengguna</label>
                <input id={`${id}u`} name="username" defaultValue={username} autoComplete="username" autoCapitalize="none" spellCheck={false} required />
                <label htmlFor={`${id}p`}>Kata sandi</label>
                <div className="plogin__pw">
                    <input id={`${id}p`} name="password" type={show ? 'text' : 'password'} autoComplete="current-password" required />
                    <button type="button" aria-pressed={show} onClick={() => setShow(!show)}>
                        {show ? 'Sembunyi' : 'Tampil'}
                    </button>
                </div>
                <a className="plogin__forgot" href={forgotUrl}>
                    Lupa kata sandi?
                </a>
                <button className="plogin__go" type="submit">
                    Masuk
                </button>
            </form>
            {deviceAction && (
                <form method="POST" action={deviceAction} className="plogin__desk">
                    {csrfToken && <input type="hidden" name="_token" value={csrfToken} />}
                    <input type="hidden" name="ui" value="desktop" />
                    <button type="submit">Tampilan desktop</button>
                </form>
            )}
        </main>
    );
}
