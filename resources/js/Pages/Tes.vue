<script setup>
// Tes minat v14: panduan → pernyataan satu per satu → hasil.
// Perpindahan soal: jawaban dipilih (berubah biru) → jeda singkat → soal lama bergeser + memudar, soal baru masuk
// dalam 300 ms (ease-in-out), nomor soal dan progress bar ikut diperbarui. Selama jeda dan animasi,
// pilihan jawaban dinonaktifkan agar tidak salah klik. Tombol Sebelumnya/Selanjutnya memberi kendali penuh.
// Setiap jawaban dikirim ke server di latar belakang; jawaban lengkap dikirim ulang saat selesai.
import { computed, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import BackBar from '../Components/BackBar.vue';
import Go from '../Components/Go.vue';
import { postJson } from '../lib/http';
import { toast } from '../lib/toast';

const props = defineProps({
    instrument: { type: Object, required: true }, // { lead, scale[5], items[50] }
    session: { type: Object, default: null }, // { id, answers: {no: v}, current_item }
    startNow: { type: Boolean, default: false },
});

const QN = props.instrument.items.length;
const answers = reactive({});
const answeredCount = computed(() => Object.keys(answers).length);
const firstUnanswered = () => {
    for (let n = 1; n <= QN; n++) if (!answers[n]) return n;
    return QN;
};

const started = ref(false);
const idx = ref(0);

// Dipanggil saat halaman dibuka dan setiap kali sesi berganti (mis. setelah "Mulai dari Awal"),
// karena Inertia bisa memakai ulang komponen yang sama.
function init() {
    Object.keys(answers).forEach((k) => delete answers[k]);
    Object.entries(props.session?.answers ?? {}).forEach(([no, v]) => (answers[no] = v));
    started.value = props.startNow && !!props.session;
    idx.value = firstUnanswered() - 1;
}
init();
watch(() => [props.session?.id, props.startNow], init);
const finishing = ref(false);
let failedSaves = 0;

// Durasi transisi antarsoal (harus sama dengan CSS .q-slide-*) dan jeda konfirmasi setelah memilih jawaban.
const TRANSITION_MS = 300;
const CONFIRM_MS = 250;
const animating = ref(false); // terkunci selama jeda konfirmasi + animasi
const dir = ref('next'); // arah animasi: next (soal lama ke kiri) / prev (soal lama ke kanan)
let timer = null;

const q = computed(() => props.instrument.items[idx.value]);
const cur = computed(() => answers[idx.value + 1] || 0);
const pct = computed(() => Math.round(((idx.value + 1) / QN) * 100));
const isLast = computed(() => answeredCount.value >= QN);

function begin() {
    // Sesi kosong yang sudah ada dipakai ulang; selain itu buat sesi baru.
    if (props.session && answeredCount.value === 0) { started.value = true; idx.value = 0; return; }
    router.post('/tes/mulai');
}
function resume() { idx.value = firstUnanswered() - 1; started.value = true; }
function restart() { router.post('/tes/mulai'); }

/** Pindah soal dengan animasi; pilihan jawaban terkunci sampai animasi selesai. */
function goTo(target, direction) {
    if (target < 0 || target >= QN || target === idx.value) return;
    dir.value = direction;
    animating.value = true;
    idx.value = target;
    clearTimeout(timer);
    // Dibuka saat animasi masuk selesai (@after-enter); batas waktu ini hanya cadangan.
    timer = setTimeout(unlock, TRANSITION_MS + 150);
}
function unlock() { clearTimeout(timer); animating.value = false; }

function answer(v) {
    if (finishing.value || animating.value || !props.session) return;
    const no = idx.value + 1;
    answers[no] = v;
    postJson(`/tes/${props.session.id}/jawab`, { item_no: no, value: v }).catch(() => {
        failedSaves++;
        if (failedSaves === 1) toast('Koneksi tidak stabil. Jawabanmu tetap dikirim saat tes selesai.');
    });

    // Jeda singkat agar warna biru (konfirmasi) terlihat sebelum soal berganti.
    animating.value = true;
    clearTimeout(timer);
    timer = setTimeout(() => {
        animating.value = false;
        if (answeredCount.value >= QN) return finish();
        next();
    }, CONFIRM_MS);
}

/** Soal berikutnya; di soal terakhir lompat ke soal yang belum dijawab, atau selesai bila semua terjawab. */
function next() {
    if (!cur.value || animating.value || finishing.value) return;
    if (isLast.value && idx.value + 1 >= QN) return finish();
    goTo(idx.value + 1 >= QN ? firstUnanswered() - 1 : idx.value + 1, 'next');
}
function back() { if (!animating.value) goTo(idx.value - 1, 'prev'); }

function finish() {
    if (finishing.value) return;
    finishing.value = true;
    router.post(`/tes/${props.session.id}/selesai`, { answers: { ...answers } }, {
        onError: () => toast('Hasil belum tersimpan. Coba pilih jawaban terakhir sekali lagi.'),
        onFinish: () => (finishing.value = false),
    });
}

function onKey(e) {
    if (!started.value || e.target.closest('input,textarea,select')) return;
    if (e.key >= '1' && e.key <= '5') answer(Number(e.key));
    else if (e.key === 'ArrowLeft') back();
    else if (e.key === 'ArrowRight' || (e.key === 'Enter' && !e.target.closest('button'))) next();
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => { window.removeEventListener('keydown', onKey); clearTimeout(timer); });
</script>

<template>
    <Head title="Tes Minat" />
    <div class="wrap" style="padding-top:24px">
        <BackBar />

        <div v-if="!started" class="wrap" style="max-width:980px;padding-top:24px;padding-bottom:80px">
            <div class="panduan-card ios-glass">
                <div class="panduan-header">
                    <Go href="/beranda" class="panduan-back-btn" title="Kembali ke Beranda">
                        <span class="panduan-back-arrow">‹</span>
                        <span class="panduan-header-title">Panduan Tes Minat Bakat</span>
                    </Go>
                </div>
                <div class="panduan-grid">
                    <div class="panduan-col">
                        <div class="panduan-item"><div class="panduan-num">1</div><div class="panduan-content"><h4>Ikuti Intuisi Pertama</h4><p>Jawablah dengan spontan—seringkali intuisi pertamamu adalah yang paling sesuai dengan dirimu</p></div></div>
                        <div class="panduan-item"><div class="panduan-num">2</div><div class="panduan-content"><h4>Kejujuran adalah Kunci</h4><p>Tidak ada jawaban yang benar atau salah. Jawablah dengan jujur sesuai kepribadianmu.</p></div></div>
                    </div>
                    <div class="panduan-col">
                        <div class="panduan-item"><div class="panduan-num">3</div><div class="panduan-content"><h4>Atur Waktumu Sendiri</h4><p>Anda bebas menentukan ritme—tidak ada batasan waktu dalam mengerjakan tes ini.</p></div></div>
                        <div class="panduan-item"><div class="panduan-num">4</div><div class="panduan-content"><h4>Komitmen Hingga Akhir</h4><p>Tes ini terdiri dari {{ QN }} pernyataan yang perlu diselesaikan. Jawabanmu tersimpan otomatis, jadi kalau berhenti di tengah kamu bisa melanjutkannya nanti.</p></div></div>
                    </div>
                </div>
                <div class="panduan-actions">
                    <template v-if="session && answeredCount > 0">
                        <button type="button" class="btn btn-primary panduan-btn-mulai" @click="resume">Lanjutkan Tes (Pernyataan {{ firstUnanswered() }})</button>
                        <button type="button" class="btn" @click="restart">Mulai dari Awal</button>
                    </template>
                    <button v-else type="button" class="btn btn-primary panduan-btn-mulai" @click="begin">Mulai Tes</button>
                </div>
            </div>
        </div>

        <div v-else class="wrap" style="max-width:780px;padding-top:20px;padding-bottom:80px">
            <div class="quiz-card">
                <div class="quiz-top-bar">
                    <button v-if="idx > 0" type="button" class="quiz-back-btn" title="Kembali ke soal sebelumnya" :disabled="animating || finishing" @click="back">← Soal Sebelumnya</button>
                    <span v-else class="quiz-back-placeholder"></span>
                    <span class="quiz-step-badge" aria-live="polite">Pernyataan <b>{{ idx + 1 }}</b> dari {{ QN }}</span>
                </div>
                <div class="bar quiz-progress" role="progressbar" aria-valuemin="0" :aria-valuemax="QN" :aria-valuenow="idx + 1"><i :style="{ width: pct + '%' }"></i></div>

                <div class="quiz-stage" :class="{ 'is-locked': animating }">
                    <Transition :name="'q-slide-' + dir" @after-enter="unlock">
                        <div :key="idx" class="quiz-slide">
                            <p class="quiz-qlead">{{ instrument.lead }}</p>
                            <h3 class="quiz-qtext">“{{ q }}”</h3>
                            <div class="quiz-answers" role="group" aria-label="Pilihan jawaban">
                                <button v-for="(label, k) in instrument.scale" :key="k" type="button" class="quiz-ans-btn" :class="{ 'is-selected': cur === k + 1 }" :aria-pressed="cur === k + 1 ? 'true' : 'false'" :disabled="finishing || animating" @click="answer(k + 1)">
                                    <span class="quiz-ans-icon">{{ k + 1 }}</span>
                                    <span class="quiz-ans-label">{{ label }}</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>

                <div class="quiz-nav">
                    <button type="button" class="quiz-nav-btn quiz-nav-prev" :disabled="idx === 0 || animating || finishing" @click="back"><span aria-hidden="true">←</span> Sebelumnya</button>
                    <button type="button" class="quiz-nav-btn quiz-nav-next" :disabled="!cur || animating || finishing" @click="next">{{ isLast && idx + 1 >= QN ? (finishing ? 'Menyimpan…' : 'Lihat Hasil') : 'Selanjutnya' }} <span aria-hidden="true">→</span></button>
                </div>
            </div>
        </div>
    </div>
</template>
