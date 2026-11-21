<script setup>
// Halaman hasil v14 (vHasil): header, tab, kartu 3 minat, radar, arti kombinasi, 6 dimensi, kelebihan/area.
// Rekomendasi per jenjang (tab Jurusan/Karier/Kampus) masuk di Tahap 3.
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Go from '../Components/Go.vue';
import RadarChart from '../Components/RadarChart.vue';
import CharactersModal from '../Components/CharactersModal.vue';
import RecSummary from '../Components/Hasil/RecSummary.vue';
import TabJurusan from '../Components/Hasil/TabJurusan.vue';
import TabKarier from '../Components/Hasil/TabKarier.vue';
import TabKampus from '../Components/Hasil/TabKampus.vue';
import { ORDER, KARTU, DIM, ICON, artiKombinasi, pros, devAreas } from '../lib/riasec';
import { toast } from '../lib/toast';

const props = defineProps({
    result: { type: Object, default: null }, // { code, raw, norm, rank, max, level, completed_at }
    recs: { type: Object, default: null }, // rekomendasi per jenjang (RecommendationPresenter)
    favorites: { type: Object, default: () => ({}) },
    answered: { type: Number, default: 0 },
    total: { type: Number, default: 50 },
    reportCategories: { type: Object, default: () => ({}) },
});

// Laporan "hasil tidak sesuai" → kotak masuk admin.
const reporting = ref(false);
const report = useForm({ category: 'tidak_sesuai_minat', message: '' });
function sendReport() {
    report.post(`/hasil/${R.value.session_id}/laporan`, { preserveScroll: true, onSuccess: () => { reporting.value = false; report.reset(); } });
}

const tab = ref('ringkasan');
const startIdx = ref(0);
function openTab(t, i = 0) {
    startIdx.value = i;
    tab.value = t;
    document.querySelector('.hasil-pills-bar')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
const showChars = ref(false);
// SMP dan mahasiswa tidak dipilihkan kampus: tab Kampus hanya untuk SMA/SMK.
const tabs = computed(() => [['ringkasan', ' Ringkasan'], ['jurusan', props.recs?.level === 'mhs' ? ' Detail' : ' Jurusan'], ['karir', ' Karier'], ...(['smp', 'mhs'].includes(props.recs?.level) ? [] : [['kampus', ' Kampus']])]);
const R = computed(() => props.result);
const top3 = computed(() => R.value?.rank.slice(0, 3) ?? []);
const pct = (t) => Math.round((R.value.norm[t] ?? 0) * 100);
const subtitle = computed(() => top3.value.map((t) => `${KARTU[t].name} (${pct(t)}%)`).join(' · '));
// Nama 3 dimensi dominan, dirangkai "A, B, dan C" untuk panel Hasil Utama.
const domSentence = computed(() => {
    const n = top3.value.map((t) => DIM[t].name);
    return n.length <= 1 ? n.join('') : `${n.slice(0, -1).join(', ')}, dan ${n[n.length - 1]}`;
});
const arti = computed(() => (R.value ? artiKombinasi(R.value.code, top3.value) : ''));
const completedAt = computed(() => R.value?.completed_at
    ? new Date(R.value.completed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB'
    : '');

function restart() { router.visit('/tes'); }
async function share() {
    const text = `Halo! Saya baru saja mencoba asesmen minat bakat di ArahDiri dan mendapatkan profil minat ${R.value.code}. Coba tes minatmu sekarang!`;
    const url = location.origin;
    try {
        if (navigator.share) return await navigator.share({ title: `Hasil Tes Minat ArahDiri (${R.value.code})`, text, url });
        await navigator.clipboard.writeText(text + ' ' + url);
        toast('Tautan hasil berhasil disalin ke clipboard!');
    } catch (e) { /* dibatalkan */ }
}
function downloadPdf() { window.open(`/hasil/${R.value.session_id}/cetak`, '_blank', 'noopener'); }
function scrollToRec() { document.querySelector('#rekomendasi-ringkas-sec')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
</script>

<template>
    <Head title="Hasil Tes" />

    <div v-if="!R" class="wrap" style="max-width:680px;padding-top:20px">
        <div class="panel">
            <h2 style="font-size:1.3rem;font-weight:800;color:var(--ink);margin-top:0">Hasil Belum Tersedia</h2>
            <p class="lead" style="margin-top:6px">Kamu belum menyelesaikan tes minat bakat<template v-if="answered > 0"> ({{ answered }} dari {{ total }} pertanyaan telah dijawab)</template>. Selesaikan seluruh {{ total }} pernyataan RIASEC untuk membuka rekomendasi lengkap.</p>
            <div class="btn-row" style="margin-top:18px">
                <Go href="/tes" class="btn btn-primary">{{ answered > 0 ? `Lanjutkan Tes (Pernyataan ${answered + 1})` : 'Mulai Tes RIASEC Sekarang' }}</Go>
                <Go href="/beranda" class="btn">Kembali ke Beranda</Go>
            </div>
        </div>
    </div>

    <div v-else class="hasil-mobile-frame">
        <div class="hasil-top-header">
            <Go href="/beranda" class="hasil-back-btn"><span style="font-size:1.2rem;line-height:1">‹</span><span>Beranda</span></Go>
            <div style="text-align:center">
                <div style="font-size:0.95rem;font-weight:800;color:var(--ink)">Rekomendasi Hasil Tes</div>
                <div style="font-size:0.75rem;color:var(--muted);font-weight:600">Model RIASEC · {{ total }} Soal</div>
            </div>
            <div class="hasil-top-actions"><span class="hasil-header-badge">Kode: {{ R.code }}</span></div>
        </div>

        <div class="hasil-pills-bar">
            <button v-for="t in tabs" :key="t[0]" type="button" class="hasil-pill-btn" :class="{ active: tab === t[0] }" @click="tab = t[0]">{{ t[1] }}</button>
        </div>

        <template v-if="tab === 'ringkasan'">
            <div class="kartu-bakat-container">
                <div class="kartu-bakat-header-row">
                    <div class="kartu-bakat-title-wrap">
                        <span class="kartu-bakat-back-icon" title="Kembali ke Beranda" role="link" tabindex="0" @click="router.visit('/beranda')" @keydown.enter="router.visit('/beranda')">‹</span>
                        <div>
                            <div class="kartu-bakat-eyebrow">HASIL TES MINAT RIASEC</div>
                            <h2 class="kartu-bakat-main-title">Kenali tiga minat terkuatmu</h2>
                            <p class="kartu-bakat-subtitle">Profil Minatmu: <b style="color:#204692">{{ R.code }}</b> ({{ subtitle }})</p>
                        </div>
                    </div>
                    <div class="kartu-bakat-actions">
                        <button type="button" class="btn-bakat-action btn-bakat-outline" @click="restart"><span>↻</span><span>Ulangi Tes</span></button>
                        <button type="button" class="btn-bakat-action btn-bakat-primary" @click="share">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><line x1="8.59" y1="13.51" x2="15.42" y2="17.49" /><line x1="15.41" y1="6.51" x2="8.59" y2="10.49" /></svg>
                            <span>Bagikan</span>
                        </button>
                        <button type="button" class="btn-bakat-action btn-bakat-primary" title="Unduh Laporan Hasil Tes (PDF)" @click="downloadPdf"><span>Unduh PDF</span></button>
                    </div>
                </div>
                <div class="kartu-bakat-grid">
                    <div v-for="t in top3" :key="t" class="bakat-card-item">
                        <div class="bakat-card-top">
                            <div class="bakat-card-header-bar">
                                <div class="bakat-badge-pill"><span>{{ KARTU[t].label }}</span></div>
                                <div class="bakat-pct-badge" title="Tingkat Keselarasan Minat"><span>{{ pct(t) }}%</span></div>
                            </div>
                            <div class="bakat-card-img-wrap"><img :src="KARTU[t].img" :alt="KARTU[t].label" class="bakat-card-img"></div>
                        </div>
                        <div class="bakat-card-bottom">{{ KARTU[t].desc }}</div>
                    </div>
                </div>
                <div class="kartu-bakat-footer">
                    <button type="button" class="btn-lihat-semua-karakter" @click="showChars = true"><span>Lihat Semua Karakter Lainnya</span></button>
                </div>
            </div>

            <div class="hasil-riasec-card">
                <div class="hasil-riasec-grid">
                    <div class="hasil-radar-box">
                        <div class="hasil-radar-svg-wrap"><RadarChart :norm="R.norm" :code="R.code" /></div>
                        <div class="hasil-radar-footer-pill">Peta 6 dimensi minat</div>
                    </div>
                    <div class="hasil-arti-card" style="margin-top:0">
                        <div class="hasil-arti-header"><h3 class="hasil-arti-title">Apa arti kombinasi minatmu?</h3></div>
                        <p class="hasil-arti-paragraph">{{ arti }}</p>
                        <div class="hasil-arti-disclaimer">Ini adalah gambaran kecenderungan minat, bukan batasan kemampuan atau penentu karier mutlak.</div>
                    </div>
                </div>
                <button type="button" class="hasil-scroll-down-btn" aria-label="Lihat Rekomendasi Pilihan" @click="scrollToRec"><span>↓</span></button>
            </div>

            <section class="ad-dimensions">
                <div class="ad-section-heading">
                    <div>
                        <span class="v2-label">SKOR LENGKAP TESMU</span>
                        <h2>Profil 6 Dimensi RIASEC</h2>
                        <p>Skor setiap dimensi berasal dari jawaban tesmu, dibandingkan dengan skor maksimal dimensi tersebut.</p>
                    </div>
                    <span class="ad-code"><svg class="ad-spark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.2 7.3L22 12l-7.8 2.7L12 22l-2.2-7.3L2 12l7.8-2.7z" /></svg>Kode minat: <b>{{ R.code }}</b></span>
                </div>
                <div class="ad-dimension-grid">
                    <article v-for="t in ORDER" :key="t" class="ad-dimension" :class="{ 'ad-top': top3.includes(t) }">
                        <div class="ad-dim-head">
                            <div class="ad-dim-head-left">
                                <span class="ad-letter ad-dimension-icon" :title="t">
                                    <svg class="arah-line-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" v-html="ICON[t]"></svg>
                                </span>
                                <h3>{{ DIM[t].name }}</h3>
                            </div>
                            <div class="ad-dim-head-right">
                                <span v-if="top3.includes(t)" class="ad-dom-badge"><svg class="ad-spark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.2 7.3L22 12l-7.8 2.7L12 22l-2.2-7.3L2 12l7.8-2.7z" /></svg>Dominan #{{ top3.indexOf(t) + 1 }}</span>
                                <strong>{{ pct(t) }}%</strong>
                            </div>
                        </div>
                        <div class="ad-dim-meta">
                            <span>{{ top3.includes(t) ? `Minat tertinggi #${top3.indexOf(t) + 1}` : DIM[t].alias }}</span>
                            <span>{{ R.raw[t] }}/{{ R.max[t] }} poin</span>
                        </div>
                        <div class="ad-meter" role="meter" :aria-label="DIM[t].name" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="pct(t)"><i :style="{ width: pct(t) + '%' }"></i></div>
                        <p>{{ DIM[t].desc }}</p>
                    </article>
                </div>
                <div class="ad-hasil-utama">
                    <span class="ad-hu-label"><svg class="ad-spark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.2 7.3L22 12l-7.8 2.7L12 22l-2.2-7.3L2 12l7.8-2.7z" /></svg>Hasil Utama</span>
                    <p>Kode minat <b>{{ R.code }}</b> menunjukkan bahwa kamu paling dominan pada tipe <b>{{ domSentence }}</b>.</p>
                </div>
                <div class="hasil-two-cards-grid hasil-synergy-meta-grid">
                    <div class="pastel-card pastel-card-pros card-pros">
                        <div class="pastel-card-header"><h3 class="pastel-header-title">Kelebihan &amp; Potensi</h3></div>
                        <ul class="pastel-bullets meta-list-wrap">
                            <li v-for="p in pros(top3)" :key="p.title" class="pastel-bullet-item"><div><b>{{ p.title }}:</b> {{ p.desc }}</div></li>
                        </ul>
                    </div>
                    <div class="pastel-card pastel-card-cons card-cons">
                        <div class="pastel-card-header"><h3 class="pastel-header-title">Area yang Bisa Dikembangkan</h3></div>
                        <ul class="pastel-bullets meta-list-wrap">
                            <li v-for="p in devAreas(top3)" :key="p.title" class="pastel-bullet-item"><div><b>{{ p.title }}:</b> {{ p.desc }}</div></li>
                        </ul>
                    </div>
                </div>
            </section>

            <RecSummary v-if="recs" :recs="recs" :code="R.code" :top3="top3" :norm="R.norm" :favorites="favorites" @open="openTab" />
        </template>

        <TabJurusan v-else-if="tab === 'jurusan' && recs" :recs="recs" :start="startIdx" />
        <TabKarier v-else-if="tab === 'karir' && recs" :recs="recs" />
        <TabKampus v-else-if="tab === 'kampus' && recs" :recs="recs" />

        <p v-if="recs" class="hasil-report-link">Rekomendasinya terasa tidak cocok denganmu? <button type="button" @click="reporting = true">Beri tahu kami</button></p>

        <div id="cp"></div>
    </div>

    <Teleport to="body">
        <div v-if="reporting" class="career-modal-backdrop open" role="dialog" aria-modal="true" aria-labelledby="report-title" style="display:flex" @click.self="reporting = false" @keydown.esc="reporting = false">
            <div class="career-modal-dialog profile-modal-dialog">
                <button type="button" class="career-modal-close" aria-label="Tutup jendela" @click="reporting = false">&#10005;</button>
                <div class="pmodal-header">
                    <div>
                        <h3 id="report-title" class="pmodal-title">Laporkan hasil tidak sesuai</h3>
                        <p class="pmodal-sub">Laporanmu membantu tim ArahDiri memperbaiki rekomendasi. Identitasmu tidak ditampilkan ke peninjau.</p>
                    </div>
                </div>
                <form class="pmodal-body" @submit.prevent="sendReport">
                    <div class="pform-group">
                        <label class="pform-label" for="rep-cat">Apa yang terasa tidak sesuai?</label>
                        <select id="rep-cat" v-model="report.category" class="pform-input"><option v-for="(l, k) in reportCategories" :key="k" :value="k">{{ l }}</option></select>
                    </div>
                    <div class="pform-group">
                        <label class="pform-label" for="rep-msg">Ceritakan sedikit (opsional)</label>
                        <textarea id="rep-msg" v-model="report.message" class="pform-input" rows="4" maxlength="1000" placeholder="Mis. aku suka menggambar, tapi yang muncul justru akuntansi."></textarea>
                        <span v-if="report.errors.message || report.errors.category" class="field-error">{{ report.errors.message || report.errors.category }}</span>
                    </div>
                    <div class="pmodal-footer" style="padding:0;border:0;background:transparent">
                        <button type="submit" class="btn btn-primary" :disabled="report.processing">Kirim laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <CharactersModal v-if="showChars" :norm="R.norm" :top3="top3" @close="showChars = false" />
</template>
