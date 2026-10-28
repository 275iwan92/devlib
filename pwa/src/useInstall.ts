import { useEffect, useState } from 'react';
import { installMode, isIos, type InstallMode } from './platform';

// Chrome (Android/desktop) mengirim beforeinstallprompt; Safari iOS tidak punya dialog pasang.
type PromptEvent = Event & { prompt: () => Promise<void> };

const standalone = () => matchMedia('(display-mode: standalone)').matches || (navigator as { standalone?: boolean }).standalone === true;

// ?install=ios|android|installed memaksa mode (peraga/uji tanpa perangkat): android = tombol tanpa dialog sungguhan.
const forced = (): InstallMode | null => {
    const v = new URLSearchParams(location.search).get('install');
    return v === 'ios' ? 'ios' : v === 'android' ? 'prompt' : v === 'installed' ? 'installed' : null;
};

export function useInstall(): { mode: InstallMode; install: () => Promise<void> } {
    const [event, setEvent] = useState<PromptEvent | null>(null);
    const [done, setDone] = useState(false);

    useEffect(() => {
        const onPrompt = (e: Event) => {
            e.preventDefault();
            setEvent(e as PromptEvent);
        };
        const onInstalled = () => setDone(true);
        addEventListener('beforeinstallprompt', onPrompt);
        addEventListener('appinstalled', onInstalled);
        return () => {
            removeEventListener('beforeinstallprompt', onPrompt);
            removeEventListener('appinstalled', onInstalled);
        };
    }, []);

    const mode = forced() ?? installMode(done || standalone(), event !== null, isIos(navigator.userAgent, navigator.maxTouchPoints));

    // Dialog hanya bisa dipanggil sekali per kejadian.
    const install = async () => {
        if (!event) return;
        await event.prompt();
        setEvent(null);
    };

    return { mode, install };
}
