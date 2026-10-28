import { PhoneLogin } from './screens/PhoneLogin';

// Peraga: #error menampilkan keadaan gagal masuk.
export function App() {
    return <PhoneLogin error={location.hash === '#error' ? 'Nama pengguna atau kata sandi salah.' : null} deviceAction="#" />;
}
