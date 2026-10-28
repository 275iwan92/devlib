import { useOnline } from './useOnline';

export function App() {
    const online = useOnline();
    return (
        <main className="screen">
            <h1>DevLib</h1>
            <p>Kerangka PWA React + TypeScript.</p>
            <p className={online ? 'ok' : 'off'}>{online ? 'Online' : 'Offline'}</p>
        </main>
    );
}
