import { useEffect, useState } from 'react';
import { cookieLang, loadSession, saveLang, signIn, type Lang, type Links, type LoginError } from './api';
import { PhoneLogin } from './screens/PhoneLogin';

export function App() {
    const [csrf, setCsrf] = useState('');
    // Cookie dibaca sinkron agar render pertama sudah berbahasa pilihan pengguna (tanpa kedipan menunggu server).
    const [lang, setLang] = useState<Lang>(() => cookieLang() ?? 'id');
    const [offline, setOffline] = useState(false);
    const [links, setLinks] = useState<Links>({});

    useEffect(() => {
        loadSession().then((s) => {
            if (!s) return setOffline(true);
            setCsrf(s.csrf);
            if (!cookieLang()) setLang(s.lang);
            setLinks(s.links);
        });
    }, []);

    const onLang = (l: Lang) => {
        setLang(l);
        saveLang(l);
    };

    const onSignIn = async (login: string, password: string): Promise<LoginError | null> => {
        // Sesi belum termuat (server mati saat dibuka): coba lagi sebelum kirim.
        let token = csrf;
        if (!token) {
            const s = await loadSession();
            if (!s) return 'network';
            setCsrf((token = s.csrf));
        }
        const r = await signIn(token, login, password);
        if ('error' in r) return r.error;
        location.assign(r.redirect);
        return null;
    };

    return <PhoneLogin lang={lang} onLang={onLang} onSignIn={onSignIn} offline={offline} csrfToken={csrf} registerUrl={links.register} forgotUrl={links.forgot} deviceAction={links.device} />;
}
