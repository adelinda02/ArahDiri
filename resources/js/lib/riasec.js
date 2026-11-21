// Teks hasil RIASEC, diambil apa adanya dari ArahDiri v14 (KARTU_6_TIPE, RIASEC_DIM_INFO,
// getArtiKombinasiMinat, getPlayfulPros, getPlayfulDevAreas).
export const ORDER = ['R', 'I', 'A', 'S', 'E', 'C'];

export const KARTU = {
    R: { name: 'Realistis', label: 'Realistis (R)', img: '/img/kartu_6_tipe/R_Realistic.png', desc: 'Tipe Realistis ini suka hal-hal praktis dan bekerja langsung dengan alat, mesin, atau kegiatan lapangan. Lebih suka aksi nyata daripada sekadar teori. Cocok jadi insinyur, teknisi, arsitek, dll.' },
    I: { name: 'Investigatif', label: 'Investigatif (I)', img: '/img/kartu_6_tipe/I_Investigative.png', desc: 'Orang bertipe Investigatif ini punya rasa ingin tahu tinggi dan gemar menganalisis hal rumit. Dia suka riset, data, dan logika ilmiah untuk memecahkan masalah. Cocok jadi peneliti, data analyst, dll.' },
    A: { name: 'Artistik', label: 'Artistik (A)', img: '/img/kartu_6_tipe/A_Artistic.png', desc: 'Kepribadian Artistik ini penuh kreativitas, imajinasi, dan suka mengekspresikan ide lewat karya orisinal. Gak suka aturan kaku. Cocok jadi desainer, kreator konten, penulis, dll.' },
    S: { name: 'Sosial', label: 'Sosial (S)', img: '/img/kartu_6_tipe/S_Social.png', desc: 'Kepribadian Sosial ini sukanya membantu. Biasanya dia gak suka kerja pakai mesin. Dia suka eksplorasi jadi guru, konselor, dan lain-lain, deh!' },
    E: { name: 'Enterprising', label: 'Enterprising (E)', img: '/img/kartu_6_tipe/E_Enterprising.png', desc: 'Kepribadian berjiwa wirausaha ini jago bicara dan cocok jadi pemimpin. Cocok kerja bareng orang lain, apalagi eksplorasi di bidang politik atau penjualan!' },
    C: { name: 'Konvensional', label: 'Konvensional (C)', img: '/img/kartu_6_tipe/C_Conventional.png', desc: 'Orang yang berjiwa Konvensional ini sangat teliti dan teratur. Dia suka kerja dengan angka dan kata-kata daripada berinteraksi dengan orang lain. Cocok jadi akuntan, pekerja administrasi, dll.' },
};

export const DIM = {
    R: { name: 'Realistis', alias: 'Praktis & Teknikal', desc: 'Cenderung menyukai aktivitas praktis, penerapan teknologi nyata, rekayasa fisik, dan kegiatan langsung di lapangan.' },
    I: { name: 'Investigatif', alias: 'Analitis & Riset', desc: 'Cenderung menyukai pengamatan mendalam, eksplorasi data, penelitian ilmiah, dan pemecahan masalah rumit berbasis logika.' },
    A: { name: 'Artistik', alias: 'Kreatif & Inovasi', desc: 'Cenderung menyukai ekspresi bebas, penciptaan konsep orisinal, kepekaan estetika visual, dan eksplorasi non-konvensional.' },
    S: { name: 'Sosial', alias: 'Empatik & Edukasi', desc: 'Cenderung menyukai kolaborasi positif, pendampingan orang lain, komunikasi antarpribadi, dan kegiatan sosial bermakna.' },
    E: { name: 'Enterprising', alias: 'Pemimpin & Inisiatif', desc: 'Cenderung menyukai inisiatif baru, kepemimpinan tim, komunikasi persuasif, pengambilan keputusan, dan pencapaian target.' },
    C: { name: 'Konvensional', alias: 'Terstruktur & Presisi', desc: 'Cenderung menyukai keteraturan alur kerja, ketelitian data angka/dokumen, kepatuhan prosedur, dan manajemen terorganisir.' },
};

// Ikon garis per dimensi (arahLineIcon v14).
export const ICON = {
    R: '<path d="m14 6 4-4a6 6 0 0 1-8 8l-6 6a2 2 0 0 0 3 3l6-6a6 6 0 0 1-8-8l4 4z"/>',
    I: '<circle cx="10" cy="10" r="6"/><path d="m15 15 5 5M7 10h6M10 7v6"/>',
    A: '<path d="M12 3a9 9 0 1 0 0 18h1a2 2 0 0 0 1-4c-1-1 0-3 2-3h2c4 0 4-11-6-11Z"/><circle cx="7" cy="10" r=".7"/><circle cx="10" cy="6.5" r=".7"/><circle cx="15" cy="7" r=".7"/>',
    S: '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5"/>',
    E: '<path d="M5 21V3M5 4h13l-3 4 3 4H5M10 20l4-4 3 2 4-5"/>',
    C: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 3h6v4H9zM9 12h6M9 16h6"/>',
};

const T_MAP = {
    R: 'penerapan teknologi praktis dan eksekusi nyata di lapangan',
    I: 'rasa ingin tahu dan kemampuan analisis mendalam',
    A: 'kreativitas dalam menghasilkan ide dan inovasi orisinal',
    S: 'kepekaan sosial dan kepedulian terhadap kolaborasi bersama',
    E: 'inisiatif kepemimpinan dan dorongan untuk menggerakkan peluang baru',
    C: 'ketelitian dalam menjaga keteraturan sistem dan keakuratan alur kerja',
};
const ACT_MAP = {
    IA: 'Kamu mungkin tertarik pada aktivitas yang melibatkan pemecahan masalah sekaligus proses merancang, berkreasi, atau membuat sesuatu yang baru.',
    AI: 'Kamu mungkin sangat menikmati proses melahirkan ide-ide konseptual yang segar sekaligus membedah persoalan secara logis dan terstruktur.',
    IR: 'Kamu mungkin menyukai aktivitas yang menantang pemikiran kritis dan logika teknis, serta langsung menguji solusinya pada penerapan fisik atau sistem nyata.',
    RI: 'Kamu mungkin tertarik pada rekayasa teknologi, pengoperasian alat cerdas, atau eksperimen langsung yang memerlukan penalaran analitis mendalam.',
    AR: 'Kamu mungkin senang merancang karya kreatif, purwarupa desain, atau visual yang diwujudkan melalui keahlian praktis dan perkakas terapan.',
    RA: 'Kamu mungkin menyukai pembuatan karya nyata, instalasi teknis, atau produk fisik yang memiliki nilai estetika dan sentuhan inovatif.',
    IS: 'Kamu mungkin tertarik pada penelitian, kajian ilmiah, atau analisis data yang bertujuan meningkatkan kesejahteraan dan kualitas hidup sesama.',
    SI: 'Kamu mungkin senang mendampingi, mengedukasi, atau memahami dinamika manusia dengan pendekatan yang bijaksana, objektif, dan berbasis fakta.',
    AS: 'Kamu mungkin sangat terdorong mengekspresikan ide secara kreatif untuk menginspirasi, menghibur, atau mendidik lingkungan di sekitarmu.',
    SA: 'Kamu mungkin menikmati aktivitas yang mempertemukan banyak orang melalui medium seni, komunikasi kreatif, serta pendampingan yang hangat.',
    IE: 'Kamu mungkin tertarik pada peran strategis yang memadukan pemahaman data analitis dengan inisiatif mengambil keputusan dan memimpin arah proyek.',
    EI: 'Kamu mungkin menyukai peluang untuk memimpin tim, melihat potensi pasar baru, dan merumuskan rencana berbasis bukti serta analisis yang tajam.',
    AE: 'Kamu mungkin berkembang optimal dalam melahirkan konsep kreatif sekaligus memimpin inisiatif peluncuran karya atau bisnis inovatif.',
    EA: 'Kamu mungkin memancarkan jiwa wirausaha dan visi berani yang dihidupkan dengan strategi komunikasi kreatif dan ideasi yang memikat.',
    SE: 'Kamu mungkin sangat antusias pada inisiatif sosial atau organisasi yang memberdayakan banyak orang, memotivasi tim, dan mencapai visi bersama.',
    ES: 'Kamu mungkin mahir memimpin pergerakan komunitas, menggalang kolaborasi tim, dan mengarahkan relasi antarpribadi menuju dampak positif.',
    IC: 'Kamu mungkin menyukai lingkungan yang menuntut ketelitian analitis tinggi, audit data, pemodelan sistem, serta pengujian sistematis.',
    CI: 'Kamu mungkin merasa nyaman bekerja dengan data yang presisi, verifikasi kepatuhan aturan, serta analisis berbasis standar metodologi yang ketat.',
    AC: 'Kamu mungkin mahir menyajikan ide-ide kreatif dan konten visual dengan standar tata letak yang bersih, teratur, dan konsisten.',
    CA: 'Kamu mungkin menikmati pengelolaan proyek kreatif dengan pengaturan jadwal rapi, pengarsipan tertata, dan ketelitian tinggi.',
    SC: 'Kamu mungkin andal dalam mendukung pelayanan publik, administrasi sosial, dan koordinasi komunitas dengan alur kerja yang teratur dan ramah.',
    CS: 'Kamu mungkin mengutamakan ketepatan prosedur dan keandalan operasional dalam melayani kebutuhan organisasi serta orang-orang di sekitarmu.',
    RC: 'Kamu mungkin sangat disiplin dalam pemeliharaan teknis, keselamatan kerja, dan memastikan mesin atau infrastruktur berjalan sesuai standar.',
    CR: 'Kamu mungkin menyukai manajemen logistik, pengawasan operasional fasilitas, atau pemantauan data teknis yang terstruktur rapi.',
    RE: 'Kamu mungkin siap mengambil tanggung jawab memimpin pelaksanaan proyek teknis di lapangan dengan fokus pada hasil yang nyata dan terukur.',
    ER: 'Kamu mungkin proaktif mengelola bisnis operasional, ekspansi lapangan, atau pengembangan sarana fisik secara langsung.',
    EC: 'Kamu mungkin berbakat dalam tata kelola manajemen, pengawasan anggaran, dan memastikan strategi organisasi terlaksana secara efisien.',
    CE: 'Kamu mungkin mahir mengelola sistem keuangan, administrasi korporat, dan perencanaan operasional yang mendukung pertumbuhan institusi.',
};

export function artiKombinasi(code, top3) {
    const [c1, c2, c3] = top3;
    const act = ACT_MAP[c1 + c2] || 'Kamu mungkin menyukai aktivitas yang memberikan ruang untuk menggabungkan eksplorasi ide, kolaborasi yang bermakna, serta penerapan nyata.';
    return `Kombinasi ${code} menggambarkan kecenderungan yang memadukan ${T_MAP[c1]}, ${T_MAP[c2]}, serta ketertarikan pada ${T_MAP[c3]}. ${act}`;
}

const PROS = {
    R: ['Eksekusi Praktis', 'Cenderung cekatan mewujudkan konsep menjadi aksi nyata dan solusi fungsional di lapangan.'],
    I: ['Ketajaman Analisis', 'Cenderung kritis membedah persoalan dan mengambil kesimpulan berlandaskan data terpercaya.'],
    A: ['Ideasi Orisinal', 'Cenderung kaya konsep segar, memiliki kepekaan estetika, dan sudut pandang out-of-the-box.'],
    S: ['Komunikasi Empatik', 'Cenderung mudah memahami kebutuhan orang lain dan membangun suasana kolaboratif yang hangat.'],
    E: ['Inisiatif Berani', 'Cenderung proaktif melihat peluang baru, persuasif memotivasi tim, dan fokus pada kemajuan.'],
    C: ['Presisi & Keteraturan', 'Cenderung teliti menjaga akurasi detail, kerapian prosedur, dan konsistensi mutu hasil kerja.'],
};
const DEV = {
    R: ['Eksplorasi Konseptual', 'Bisa melatih kesabaran saat mendiskusikan konsep abstrak atau perencanaan jangka panjang.'],
    I: ['Pengambilan Keputusan Cepat', 'Dapat membiasakan diri mengambil aksi nyata tanpa harus menunggu data serba 100% sempurna.'],
    A: ['Fokus & Penyelesaian', 'Bisa membangun kebiasaan menuntaskan satu karya utama sebelum melompat ke inspirasi berikutnya.'],
    S: ['Batasan Energi Pribadi', 'Dapat melatih keberanian menetapkan batasan waktu agar tidak terlalu membebani energi diri sendiri.'],
    E: ['Perhatian pada Detail Mikro', 'Bisa meluangkan waktu sejenak memeriksa detail teknis operasional agar eksekusi semakin kokoh.'],
    C: ['Fleksibilitas Improvisasi', 'Dapat mengasah kenyamanan beradaptasi ketika situasi membutuhkan perubahan rencana spontan.'],
};
export const pros = (top3) => top3.map((t) => ({ title: `${PROS[t][0]} (${t})`, desc: PROS[t][1] }));
export const devAreas = (top3) => top3.map((t) => ({ title: DEV[t][0], desc: DEV[t][1] }));

/** ['I','S'] → "Investigatif + Sosial" (alasan kecocokan prodi). */
export const becauseText = (letters = []) => letters.map((t) => DIM[t]?.name ?? t).join(' + ');
