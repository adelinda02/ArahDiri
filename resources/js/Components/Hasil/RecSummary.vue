<script setup>
// Bagian rekomendasi di tab Ringkasan (gaya star-catalog + ad-conclusion v14), per jenjang.
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Go from '../Go.vue';
import LineIcon from './LineIcon.vue';
import { KIND_ICON, pct, tanggal, accLine, campusPlace } from '../../lib/format';
import { DIM, becauseText } from '../../lib/riasec';

const props = defineProps({
    recs: { type: Object, required: true },
    code: { type: String, required: true },
    top3: { type: Array, required: true },
    umbProgram: { type: Object, default: null },
    favorites: { type: Object, default: () => ({}) },
});
const isFav = (id) => (props.favorites.program ?? []).includes(String(id));
const toggleFav = (id) => router.post('/favorit', { kind: 'program', target_id: String(id) }, { preserveScroll: true, preserveState: true });
const emit = defineEmits(['open']);

const level = computed(() => props.recs.level);
const names = computed(() => props.top3.map((t) => DIM[t].name).join(', '));
const accNote = computed(() => props.recs.accreditation_date
    ? `Akreditasi menurut Direktori BAN-PT per ${tanggal(props.recs.accreditation_date)}.`
    : 'Data akreditasi BAN-PT belum disinkron, jadi kampus belum dipilih.');
const ev = computed(() => props.recs.umb_eval);
const progNames = (list) => list.map((p) => p.name).join(', ');
// Jalur SMP (rec-v2): arah SMA terkuat yang menyambung ke prodi; hasil lama tidak punya from_tracks.
const strongTracks = (p) => (p.from_tracks ?? []).filter((f) => f.strong).map((f) => f.track);
const viaLine = (p) => {
    const t = strongTracks(p);
    const parts = [t.length ? 'Lewat SMA arah ' + t.join(' / ') : null, p.from_smk?.length ? 'SMK ' + p.from_smk.join(' / ') : null].filter(Boolean);
    return parts.join(' atau ');
};
const nextNames = (x) => (x?.next_programs ?? []).map((n) => n.name).join(', ');
const smpDetail = (i) => 1 + (props.recs.smk_programs?.length ?? 0) + i;
// SMP rec-v3: jalur per profesi.
const paths = computed(() => props.recs.career_paths ?? []);
const smkName = (c) => (c.smk.length ? c.smk.map((k) => k.name).join(' / ') : null);
const kuliahName = (c) => c.programs.map((p) => p.name).join(' / ');
</script>

<template>
    <!-- ============ SMA / SMK ============ -->
    <section v-if="level === 'sma_smk'" class="star-catalog" id="rekomendasi-ringkas-sec">
        <span class="v2-label">PILIHAN UNTUK DIJELAJAHI</span>
        <h2>3 Rekomendasi Prodi &amp; Kampus</h2>
        <p class="star-notice">Diurutkan dari keselarasan minat tertinggi; tiap prodi punya jalur karier utama yang berbeda. Persentase adalah keselarasan profil enam dimensi minat, bukan peluang diterima. {{ accNote }}</p>
        <div class="star-grid">
            <article v-for="(p, i) in recs.programs" :key="p.id" class="star-card">
                <div class="star-card-top">
                    <span>Rekomendasi #{{ p.rank }} · {{ p.level }}</span>
                    <button type="button" class="star-favorite" :aria-pressed="isFav(p.id) ? 'true' : 'false'" :aria-label="isFav(p.id) ? 'Hapus dari favorit' : 'Simpan ke favorit'" @click="toggleFav(p.id)">
                        <svg width="20" height="20" viewBox="0 0 24 24" :fill="isFav(p.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
                    </button>
                </div>
                <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.majors" /></span><span>{{ p.name }}</span></h3>
                <p v-if="p.campus.name">{{ p.campus.name }}<template v-if="campusPlace(p.campus)"> · {{ campusPlace(p.campus) }}</template></p>
                <p v-else>Kampus menunggu data akreditasi</p>
                <span class="star-score">Keselarasan profil {{ pct(p.score) }}%</span>
                <button type="button" class="star-detail" @click="emit('open', 'jurusan', i)">Lihat detail →</button>
            </article>
        </div>

        <section v-if="recs.umb_promos.length" class="ad-conclusion umb-promo">
            <span class="v2-label">INFORMASI DARI UNIVERSITAS MERCU BUANA</span>
            <h2>Pilihanmu juga ada di UMB</h2>
            <p>Prodi di Universitas Mercu Buana (Jakarta) yang selaras dengan rekomendasimu. Informasi ini tidak mengubah urutan rekomendasi di atas.</p>
            <div class="ad-path-grid">
                <article v-for="u in recs.umb_promos" :key="u.name + u.level">
                    <span>{{ u.type === 'same_family' ? 'PRODI YANG SAMA TERSEDIA DI UMB' : 'PRODI TERKAIT DI UMB' }}</span>
                    <h3>{{ u.name }} ({{ u.level }})</h3>
                    <p>Selaras dengan rekomendasi #{{ u.for_rank }}: {{ u.for_name }}<template v-if="u.via">, lewat jabatan <b>{{ u.via }}</b></template>.</p>
                    <small>{{ accLine(u.accreditation) }}</small>
                </article>
            </div>
        </section>

        <section class="ad-conclusion">
            <span class="v2-label">ARAH BERIKUTNYA UNTUKMU</span>
            <h2>Kesimpulan &amp; rekomendasi pendidikan</h2>
            <p>Dari hasil yang kamu peroleh, kombinasi minat {{ code }} menunjukkan kecenderungan pada {{ names }}. Tiga prodi berikut paling selaras dengan profilmu, masing-masing dengan satu kampus pilihan berdasarkan akreditasi prodi dan kampus.</p>
            <div class="ad-path-grid">
                <article v-for="p in recs.programs" :key="'c' + p.id">
                    <span>0{{ p.rank }} / {{ p.level }}</span>
                    <h3>{{ p.name }}</h3>
                    <p>{{ p.campus.name || 'Kampus menunggu data akreditasi' }}.</p>
                    <small>Contoh karier: {{ p.careers.slice(0, 3).map((c) => c.title).join(', ') || '-' }}.</small>
                </article>
            </div>
            <p class="ad-conclusion-line">Kesimpulannya, kamu dapat mempertimbangkan {{ progNames(recs.programs) }} sebagai pilihan kuliah. Bandingkan kurikulum, biaya, dan jalur masuk di situs resmi kampus sebelum mendaftar.</p>
            <p class="ad-method">Ringkasan otomatis berdasarkan skor RIASEC, katalog prodi PDDikti, jabatan KBJI 2026, dan akreditasi BAN-PT. Gunakan bersama nilai pelajaran, pengalaman, dan diskusi dengan guru BK; hasil ini bukan keputusan penerimaan kampus.</p>
            <button type="button" class="btn btn-primary" @click="emit('open', 'jurusan', 0)">Lihat detail jurusan</button>
        </section>
    </section>

    <!-- ============ SMP: jalur per profesi (rec-v3) ============ -->
    <section v-else-if="level === 'smp' && paths.length" class="star-catalog" id="rekomendasi-ringkas-sec">
        <span class="v2-label">JALUR MENUJU CITA-CITA</span>
        <h2>3 Profesi &amp; Jalur Pendidikannya</h2>
        <p class="star-notice">Mulai dari profesi yang paling cocok dengan minatmu, lalu langkah mundurnya: arah SMA atau program SMK, kemudian prodi kuliah. Persentase adalah keselarasan profil minat, bukan peluang diterima.</p>
        <div class="star-grid">
            <article v-for="(c, i) in paths" :key="c.id" class="star-card">
                <div class="star-card-top"><span>Profesi #{{ c.rank }}</span></div>
                <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.careers" /></span><span>{{ c.title }}</span></h3>
                <ol class="rec-path-steps">
                    <li><b>SMA</b> arah {{ c.sma.name }}<template v-if="smkName(c)"> · <b>SMK</b> {{ smkName(c) }}</template></li>
                    <li><b>Kuliah</b> {{ kuliahName(c) }}</li>
                </ol>
                <span class="star-score">Keselarasan profil {{ pct(c.score) }}%</span>
                <button type="button" class="star-detail" @click="emit('open', 'jurusan', i)">Lihat jalur lengkap →</button>
            </article>
        </div>

        <section class="ad-conclusion">
            <span class="v2-label">ARAH BERIKUTNYA UNTUKMU</span>
            <h2>Jalur menuju {{ paths[0].title }}</h2>
            <p>Kombinasi minat {{ code }} menunjukkan kecenderungan pada {{ names }}<template v-if="paths[0].because.length">, sejalan dengan profesi {{ paths[0].title }} yang menuntut minat {{ becauseText(paths[0].because) }}</template>. Begini langkahnya:</p>
            <div class="ad-path-grid">
                <article>
                    <span>01 / JALUR SMA</span>
                    <h3>Arah {{ paths[0].sma.name }}</h3>
                    <p>{{ paths[0].sma.subjects.join(', ') }}.</p>
                    <small v-if="!paths[0].sma.any && paths[0].sma.via.length">Fokuskan pada {{ paths[0].sma.via.join(', ') }}: mapel pendukung prodinya.</small>
                </article>
                <article>
                    <span>01 / ATAU JALUR SMK</span>
                    <h3>{{ smkName(paths[0]) || 'Belum ada yang linear' }}</h3>
                    <p v-if="paths[0].smk.length">{{ paths[0].smk.map((k) => k.field).filter((v, i, a) => a.indexOf(v) === i).join(', ') }}.</p>
                    <p v-else>Untuk profesi ini, jalur SMA arah {{ paths[0].sma.name }} lebih disarankan.</p>
                </article>
                <article>
                    <span>02 / KULIAH</span>
                    <h3>{{ kuliahName(paths[0]) }}</h3>
                    <p>{{ paths[0].programs.map((p) => p.level + ', ' + p.offerings_count + ' kampus').join(' · ') }}.</p>
                    <small>Kampus belum perlu dipilih sekarang.</small>
                </article>
            </div>
            <p class="ad-conclusion-line">Kesimpulannya, kalau kamu tertarik menjadi {{ paths[0].title }}, ambil SMA arah {{ paths[0].sma.name }}<template v-if="smkName(paths[0])"> atau SMK {{ smkName(paths[0]) }}</template>, lalu kuliah {{ kuliahName(paths[0]) }}. Profesi lain yang juga cocok: {{ paths.slice(1).map((c) => c.title).join(' dan ') }}.</p>
            <p class="ad-method">Disusun otomatis dari skor RIASEC, jabatan KBJI 2026 dan profil O*NET, katalog prodi PDDikti, mapel pendukung prodi SNBP (Kepmendikdasmen 102/M/2025), dan Spektrum Keahlian SMK (Kepmendikbudristek 244/M/2024). Gunakan bersama nilai pelajaran, pengalaman, dan diskusi dengan guru BK serta orang tua.</p>
            <button type="button" class="btn btn-primary" @click="emit('open', 'jurusan', 0)">Lihat detail jalur</button>
        </section>
    </section>

    <!-- ============ SMP ============ -->
    <section v-else-if="level === 'smp'" class="star-catalog" id="rekomendasi-ringkas-sec">
        <span class="v2-label">PILIHAN UNTUK DIJELAJAHI</span>
        <h2>Arah Sekolah &amp; Gambaran Kuliah</h2>
        <p class="star-notice">Persentase adalah keselarasan profil enam dimensi minat, bukan peluang diterima. Arah SMA ditulis sebagai arah IPA/IPS/Bahasa dan mapel pilihan karena aturan penjurusan SMA 2027 masih disusun.</p>
        <div class="star-grid">
            <article v-if="recs.sma_tracks[0]" class="star-card">
                <div class="star-card-top"><span>Arah SMA</span></div>
                <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.majors" /></span><span>Arah {{ recs.sma_tracks[0].name }}</span></h3>
                <p>{{ recs.sma_tracks[0].subjects.slice(0, 3).join(', ') }}<template v-if="nextNames(recs.sma_tracks[0])"> · lanjut ke {{ nextNames(recs.sma_tracks[0]) }}</template></p>
                <span class="star-score">Keselarasan profil {{ pct(recs.sma_tracks[0].score) }}%</span>
                <button type="button" class="star-detail" @click="emit('open', 'jurusan', 0)">Lihat detail →</button>
            </article>
            <article v-if="recs.smk_programs[0]" class="star-card">
                <div class="star-card-top"><span>Program SMK</span></div>
                <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.careers" /></span><span>{{ recs.smk_programs[0].name }}</span></h3>
                <p>{{ recs.smk_programs[0].field }}<template v-if="nextNames(recs.smk_programs[0])"> · lanjut ke {{ nextNames(recs.smk_programs[0]) }}</template></p>
                <span class="star-score">Keselarasan profil {{ pct(recs.smk_programs[0].score) }}%</span>
                <button type="button" class="star-detail" @click="emit('open', 'jurusan', 1)">Lihat detail →</button>
            </article>
            <article v-if="recs.programs[0]" class="star-card">
                <div class="star-card-top"><span>Gambaran kuliah</span></div>
                <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.campuses" /></span><span>{{ recs.programs[0].name }}</span></h3>
                <p v-if="recs.programs[0].from_tracks">{{ viaLine(recs.programs[0]) || 'Bisa dari arah SMA mana pun' }} · cocok karena {{ becauseText(recs.programs[0].because) }}</p>
                <p v-else>{{ recs.programs[0].campus.name || 'Kampus menunggu data akreditasi' }}</p>
                <span class="star-score">Keselarasan profil {{ pct(recs.programs[0].score) }}%</span>
                <button type="button" class="star-detail" @click="emit('open', 'jurusan', smpDetail(0))">Lihat detail →</button>
            </article>
        </div>

        <section class="ad-conclusion">
            <span class="v2-label">ARAH BERIKUTNYA UNTUKMU</span>
            <h2>Kesimpulan &amp; rekomendasi pendidikan</h2>
            <p>Dari hasil yang kamu peroleh, kombinasi minat {{ code }} menunjukkan kecenderungan pada {{ names }}. Berikut arah SMA, SMK, dan kuliah yang bisa kamu pertimbangkan.</p>
            <div class="ad-path-grid">
                <article>
                    <span>01 / JALUR SMA</span>
                    <h3>Arah {{ recs.sma_tracks[0]?.name }}</h3>
                    <p>{{ recs.sma_tracks[0]?.subjects.join(', ') }}.</p>
                    <small v-if="nextNames(recs.sma_tracks[0])">Bisa lanjut kuliah ke {{ nextNames(recs.sma_tracks[0]) }}.</small>
                    <small v-else>Mapel pendukung dari minatmu: {{ recs.sma_tracks[0]?.support_subjects.join(', ') }}.</small>
                </article>
                <article>
                    <span>02 / JALUR SMK</span>
                    <h3>Program yang bisa dijelajahi</h3>
                    <p>{{ recs.smk_programs.map((s) => s.name).join(', ') }}.</p>
                    <small v-if="nextNames(recs.smk_programs[0])">{{ recs.smk_programs[0].name }} bisa lanjut kuliah ke {{ nextNames(recs.smk_programs[0]) }}.</small>
                    <small v-else>Bandingkan materi, praktik, dan fasilitas program di sekolah tujuan.</small>
                </article>
                <article>
                    <span>03 / JURUSAN KULIAH</span>
                    <h3>Gambaran tujuan kuliah nanti</h3>
                    <p>{{ progNames(recs.programs) }}.</p>
                    <small v-if="recs.programs[0]?.from_tracks">{{ recs.programs.map((p) => p.name + (strongTracks(p).length ? ' (arah ' + strongTracks(p).join('/') + ')' : '')).join(', ') }}. Tidak perlu dipilih sekarang.</small>
                    <small v-else>Tidak perlu dipilih sekarang; ini peta agar pilihan SMA atau SMK-mu terarah.</small>
                </article>
            </div>
            <p v-if="recs.sma_tracks[0]?.next_programs" class="ad-conclusion-line">Kesimpulannya, jalur yang bersambung untukmu: SMA arah {{ recs.sma_tracks[0].name }} lalu kuliah {{ nextNames(recs.sma_tracks[0]) || '-' }}; atau SMK {{ recs.smk_programs[0]?.name }} lalu kuliah {{ nextNames(recs.smk_programs[0]) || '-' }}. Prodi yang paling selaras dengan minatmu: {{ progNames(recs.programs) }}.</p>
            <p v-else class="ad-conclusion-line">Kesimpulannya, kamu dapat mempertimbangkan arah {{ recs.sma_tracks[0]?.name }} di SMA; {{ recs.smk_programs.slice(0, 2).map((s) => s.name).join(' atau ') }} untuk jalur SMK; serta {{ progNames(recs.programs) }} sebagai gambaran jurusan kuliah.</p>
            <p class="ad-method">Ringkasan otomatis berdasarkan skor RIASEC, Spektrum Keahlian SMK (Kepmendikbudristek 244/M/2024), mapel pendukung prodi SNBP, katalog prodi PDDikti, dan jabatan KBJI 2026. Gunakan bersama nilai pelajaran, pengalaman, dan diskusi dengan guru BK serta orang tua.</p>
            <button type="button" class="btn btn-primary" @click="emit('open', 'jurusan', 0)">Lihat detail jalur</button>
        </section>
    </section>

    <!-- ============ Mahasiswa UMB ============ -->
    <section v-else-if="level === 'mhs_umb'" class="star-catalog" id="rekomendasi-ringkas-sec">
        <span class="v2-label">EVALUASI PRODIMU DI UMB</span>
        <template v-if="ev">
            <h2>{{ ev.name }} ({{ ev.level }}): peringkat {{ ev.position ?? '-' }} dari {{ ev.total }}</h2>
            <p class="star-notice">Peringkat keselarasan prodimu di antara prodi {{ ev.level }} Universitas Mercu Buana untuk profil minat {{ code }} (kode minat prodi: {{ ev.program_holland || '-' }}). Ini gambaran kecocokan minat, bukan vonis salah jurusan.</p>
            <div class="star-grid">
                <article v-for="(p, i) in recs.umb_programs" :key="p.id" class="star-card">
                    <div class="star-card-top"><span>{{ p.is_current ? 'Prodimu sekarang' : 'Prodi UMB #' + p.rank }}</span></div>
                    <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.majors" /></span><span>{{ p.name }} ({{ p.level }})</span></h3>
                    <p>{{ p.is_current ? 'Prodimu sudah sesuai dengan minatmu.' : (p.campus.program_accreditation?.rating ? 'Akreditasi ' + p.campus.program_accreditation.rating : 'Prodi aktif UMB') }}</p>
                    <span class="star-score">Keselarasan profil {{ pct(p.score) }}%</span>
                    <button type="button" class="star-detail" @click="emit('open', 'jurusan', i)">Lihat detail →</button>
                </article>
            </div>
        </template>
        <template v-else>
            <h2>Lengkapi prodi UMB-mu</h2>
            <p class="star-notice">Evaluasi membutuhkan prodi UMB yang sedang kamu jalani.</p>
            <Go href="/mulai/prodi-umb" class="btn btn-primary">Pilih prodi UMB</Go>
        </template>

        <section v-if="ev" class="ad-conclusion">
            <span class="v2-label">ARAH BERIKUTNYA UNTUKMU</span>
            <h2>Bertahan atau belok arah?</h2>
            <p>Keduanya pilihan yang sah. Berikut dua jalur yang bisa kamu timbang bersama dosen pembimbing akademik.</p>
            <div class="ad-path-grid">
                <article>
                    <span>01 / BERTAHAN SAMBIL BELOK ARAH</span>
                    <h3>Karier dari {{ ev.name }} yang tetap cocok</h3>
                    <p>{{ recs.careers.map((c) => c.title).join(', ') || '-' }}.</p>
                    <small>Perkuat lewat mata kuliah pilihan, magang, organisasi, atau sertifikasi.</small>
                </article>
                <article>
                    <span>02 / PRODI UMB YANG PALING SELARAS</span>
                    <h3>{{ ev.in_top3 ? 'Prodimu termasuk 3 besar' : 'Pertimbangkan prodi lain' }}</h3>
                    <p>{{ progNames(recs.umb_programs) }}.</p>
                    <small v-if="recs.umb_s1_path.length">Jalur lanjutan S1 UMB: {{ progNames(recs.umb_s1_path) }}.</small>
                    <small v-else>Peringkat dihitung dari profil minat setiap prodi UMB jenjang {{ ev.level }}.</small>
                </article>
                <article>
                    <span>03 / LANGKAH BERIKUTNYA</span>
                    <h3>Bicarakan dengan dosen PA</h3>
                    <p>Bawa hasil ini ke dosen pembimbing akademik atau layanan konseling UMB.</p>
                    <small>Aturan pindah prodi, syarat, dan konversi SKS mengikuti ketentuan kampus.</small>
                </article>
            </div>
            <p class="ad-method">Ringkasan otomatis berdasarkan skor RIASEC, profil minat prodi dari jabatan KBJI 2026, dan daftar prodi aktif UMB. {{ accNote }}</p>
            <button type="button" class="btn btn-primary" @click="emit('open', 'jurusan', 0)">Lihat detail prodi UMB</button>
        </section>
    </section>
</template>
