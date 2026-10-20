<?php

namespace Devlib\Assistant;

// Daftar terlarang, opt-in lewat Assistant::$policy. Hanya mencocokkan TEKS yang dinyatakan program (kunci Tool/layar/topik, modul,
// judul, frasa, path, pertanyaan); DevLib tidak memahami arti data: program wajib memberi label yang benar dan mengisi daftar
// terlindung. Program tanpa teks yang cocok tidak terhalang sama sekali. Tiga lapis:
//   forbids()  gaji, payroll, formula HPP: terlarang total. Absensi / kehadiran bukan payroll; NILAI HASIL HPP (rupiah) bukan
//              formulanya: itu tingkat nilai (Sensitivity::Value), hanya untuk ValueAsker.
//   personal() frasa data pribadi (KTP, alamat rumah, tanggal lahir, kata sandi, dst.): terlarang total; layar / bantuan tempat
//              orang mengelola datanya sendiri tetap boleh ditunjukkan (itu pengetahuan menu, bukan data).
//   protects() orang yang ditunjuk program (nama / login): pertanyaan yang menyebutnya tidak pernah dijawab.
final class DataPolicy
{
    // "slip gaji" / "penggajian" sudah memuat "gaji". Tanda baca dan garis bawah dianggap spasi ("hpp_formula" = "hpp formula").
    public const WORDS = ['payroll', 'payslip', 'salary', 'gaji',
        'hpp formula', 'formula hpp', 'rumus hpp', 'cara hitung hpp', 'cara menghitung hpp', 'komponen hpp', 'parameter hpp',
        'costing formula', 'formula costing', 'cost formula', 'formula cost', 'rumus costing', 'costing parameter'];

    // Frasa spesifik, dicocokkan sebagai KATA UTUH ("nik" tidak cocok dengan "unik" / "teknik"). Sengaja tidak memuat kata
    // umum seperti "alamat", "telepon", "email", "rekening", "npwp": alamat pelanggan / pengiriman dan kontak pemasok adalah
    // data bisnis. Program menambah kata miliknya lewat argumen $personal.
    public const PERSONAL = ['data pribadi', 'data diri', 'personal data', 'personal information', 'informasi pribadi', 'private data',
        'alamat rumah', 'home address', 'residential address',
        'nomor ktp', 'no ktp', 'ktp', 'nik', 'nomor induk kependudukan', 'kartu keluarga', 'paspor', 'passport',
        'tanggal lahir', 'tgl lahir', 'tempat lahir', 'date of birth', 'birth date', 'birthdate',
        'telepon pribadi', 'telpon pribadi', 'hp pribadi', 'email pribadi', 'personal email', 'personal phone',
        'data keluarga', 'family data', 'ibu kandung', 'nama istri', 'nama suami',
        'rekening pribadi', 'npwp pribadi', 'personal bank account',
        'data kesehatan', 'riwayat penyakit', 'rekam medis', 'health data', 'medical record', 'medical history',
        'kata sandi', 'password', 'passphrase'];

    /** @var list<string> */
    private array $words;

    /** @var list<string> */
    private array $personal;

    /** @var list<list<string>> kata-kata tiap orang terlindung (nama atau login), sudah dinormalkan */
    private array $protected = [];

    /**
     * @param  list<string>  $extra  kata terlarang tambahan milik program (cocok sebagai potongan teks)
     * @param  list<string>  $protected  nama / login / alias orang yang tidak boleh disentuh asisten (satu entri satu orang atau alias)
     * @param  list<string>  $personal  frasa data pribadi tambahan milik program (cocok sebagai kata utuh)
     */
    public function __construct(array $extra = [], array $protected = [], array $personal = [])
    {
        $this->words = array_map($this->normalize(...), [...self::WORDS, ...$extra]);
        $this->personal = array_map($this->normalize(...), [...self::PERSONAL, ...$personal]);
        foreach ($protected as $who) {
            if (($n = $this->normalize($who)) !== '') {
                $this->protected[] = explode(' ', $n);
            }
        }
    }

    public function forbids(?string ...$texts): bool
    {
        foreach ($texts as $text) {
            $text = $this->normalize((string) $text);
            foreach ($this->words as $word) {
                if ($text !== '' && str_contains($text, $word)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function personal(?string ...$texts): bool
    {
        foreach ($texts as $text) {
            $text = ' '.$this->normalize((string) $text).' ';
            foreach ($this->personal as $phrase) {
                if ($phrase !== '' && str_contains($text, " {$phrase} ")) {
                    return true;
                }
            }
        }

        return false;
    }

    // Satu orang terlindung cocok bila SEMUA kata namanya ada (urutan bebas, huruf besar-kecil dan tanda baca tidak berpengaruh).
    // Sebagian nama saja ("Budi") TIDAK cocok sendirian: ia akan memblokir orang lain yang senama; program mendaftarkan panggilan
    // atau login sebagai entri tersendiri.
    public function protects(?string ...$texts): bool
    {
        foreach ($texts as $text) {
            $tokens = explode(' ', $this->normalize((string) $text));
            foreach ($this->protected as $who) {
                if (! array_diff($who, $tokens)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Semua yang tidak boleh diindeks / didaftarkan sebagai sumber: terlarang, data pribadi, atau orang terlindung. */
    public function excludes(?string ...$texts): bool
    {
        return $this->forbids(...$texts) || $this->personal(...$texts) || $this->protects(...$texts);
    }

    /** Tool terlarang tidak boleh didaftarkan. */
    public function assertTool(Tool $tool): void
    {
        if ($this->excludes($tool->key(), $tool->module(), $tool->title(), ...$tool->phrases())) {
            throw new AssistantException("Tool [{$tool->key()}] touches a forbidden source (salary / payroll / costing formula / personal data): the assistant never serves it.");
        }
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)));
    }
}
