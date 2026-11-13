<script setup>
// Rencana pengembangan v14 (Panduan Pengembangan Diri): target per jenjang + target buatan sendiri.
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import BackBar from '../Components/BackBar.vue';
import Go from '../Components/Go.vue';

const props = defineProps({ code: String, top3: Array, targets: Array, activities: { type: Array, default: () => [] } });
const NAMA = { R: 'Praktis', I: 'Peneliti', A: 'Kreatif', S: 'Penolong', E: 'Pemimpin', C: 'Penata' };
const CAT = { akademik: 'Kemampuan Akademik', softskill: 'Pengembangan Soft Skill', karier: 'Kesiapan Karier' };
const level = computed(() => usePage().props.auth.user.level_label);
const filter = ref('all');
const shown = computed(() => (filter.value === 'all' ? props.targets : props.targets.filter((t) => t.cat === filter.value)));
const done = computed(() => props.targets.filter((t) => t.done).length);
const pct = computed(() => (props.targets.length ? Math.round((done.value / props.targets.length) * 100) : 0));
const count = (c) => props.targets.filter((t) => t.cat === c).length;

function toggle(t) { router.post(`/langkah/${t.id}/centang`, {}, { preserveScroll: true, preserveState: true }); }
function remove(t) { if (confirm('Hapus target ini?')) router.delete(`/langkah/${t.id}`, { preserveScroll: true }); }

const adding = ref(false);
const form = useForm({ title: '', category: 'akademik' });
function add() { form.post('/langkah', { preserveScroll: true, onSuccess: () => { form.reset('title'); adding.value = false; } }); }
// Aktivitas eksplorasi dari admin (menu Konten) → jadi target buatan sendiri.
const hasTarget = (title) => props.targets.some((t) => t.custom && t.text === title);
function adopt(a) { router.post('/langkah', { title: a.title, category: a.meta?.category || 'akademik' }, { preserveScroll: true }); }

const cats = [
    ['akademik', 'Kemampuan Akademik', 'Perdalam bidang yang diminati, tingkatkan pemahaman, dan eksplorasi ilmu baru.', ['Perdalam bidang yang diminati', 'Tingkatkan pemahaman sesuai potensi', 'Eksplorasi ilmu & keterampilan baru'], '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>'],
    ['softskill', 'Pengembangan Soft Skill', 'Latih komunikasi, kerja sama, kepemimpinan, dan kemampuan memecahkan masalah.', ['Melatih kemampuan komunikasi', 'Meningkatkan kerja sama tim', 'Mengembangkan kepemimpinan', 'Meningkatkan problem solving'], '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
    ['karier', 'Kesiapan Karier', 'Mulai mengenal dunia kerja, membangun portofolio, dan mencari pengalaman relevan.', ['Mengenali bidang pekerjaan yang diminati', 'Mengikuti pelatihan / sertifikasi', 'Membangun pengalaman & portofolio', 'Mempersiapkan CV & pengalaman magang'], '<path d="M12 12h.01"/><path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M22 13a18.15 18.15 0 0 1-20 0"/><rect width="20" height="14" x="2" y="6" rx="2"/>'],
];
</script>

<template>
    <Head title="Rencana pengembangan" />
    <div class="wrap" style="padding-top:24px">
        <BackBar />
        <div class="dev-guide-card">
            <div class="dev-guide-header">
                <div class="dev-guide-header-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10" /><path d="M10 20c5.5-2.5.8-6.4 3-10" /><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4.1 5.5.8z" /><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z" /></svg></div>
                <div class="dev-guide-header-text">
                    <div class="dev-guide-eyebrow">PENGEMBANGAN DIRI</div>
                    <h3 class="dev-guide-title">Panduan Pengembangan Diri</h3>
                    <p class="dev-guide-desc">Kenali potensi, tingkatkan kemampuan, dan persiapkan masa depanmu.</p>
                    <div class="dev-guide-meta-badge">
                        <span v-if="code"> Personalisasi RIASEC: <b>{{ code }}</b> ({{ top3.map((t) => NAMA[t]).join(', ') }})</span>
                        <span v-else> Kerjakan tes minat untuk target yang lebih personal</span>
                        <span>• {{ level }}</span>
                    </div>
                </div>
            </div>
            <button type="button" class="star-add-target" aria-label="Tambah target pengembangan diri" @click="adding = !adding">+</button>

            <form v-if="adding" class="login-form dev-add-form" @submit.prevent="add">
                <label>Target baru<input v-model="form.title" class="login-input" maxlength="160" placeholder="Contoh: Ikut lomba karya tulis tingkat kota" required></label>
                <span v-if="form.errors.title" class="field-error">{{ form.errors.title }}</span>
                <label>Kategori<select v-model="form.category" class="login-input"><option v-for="(n, k) in CAT" :key="k" :value="k">{{ n }}</option></select></label>
                <div class="btn-row"><button class="btn btn-primary" type="submit" :disabled="form.processing">Simpan target</button><button class="btn" type="button" @click="adding = false">Batal</button></div>
            </form>

            <div class="dev-guide-divider"></div>
            <div class="dev-fokus-header"><h4 class="dev-fokus-title">Fokus pengembanganmu</h4><span class="dev-cat-badge">3 kategori</span></div>
            <div class="dev-categories-list">
                <div v-for="c in cats" :key="c[0]" class="dev-cat-card" :class="{ active: filter === c[0] }" :title="`Klik untuk memfilter target ${c[1].toLowerCase()}`" @click="filter = filter === c[0] ? 'all' : c[0]">
                    <div class="dev-cat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="c[4]"></svg></div>
                    <div class="dev-cat-content">
                        <h5 class="dev-cat-name">{{ c[1] }}</h5>
                        <p class="dev-cat-desc">{{ c[2] }}</p>
                        <div class="dev-cat-points"><span v-for="p in c[3]" :key="p" class="dev-cat-point-pill">• {{ p }}</span></div>
                    </div>
                </div>
            </div>

            <div class="dev-guide-divider"></div>
            <div class="dev-target-header">
                <h4 class="dev-target-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><circle cx="12" cy="6" r="6" /><circle cx="12" cy="2" r="2" /></svg><span>Target pengembangan diri</span></h4>
                <span class="dev-target-progress-badge">{{ done }} dari {{ targets.length }} selesai ({{ pct }}%)</span>
            </div>
            <div class="dev-progress-track"><div class="dev-progress-fill" :style="{ width: pct + '%' }"></div></div>
            <div class="dev-filter-chips">
                <button class="dev-filter-btn" :class="{ active: filter === 'all' }" @click="filter = 'all'">Semua Target ({{ targets.length }})</button>
                <button class="dev-filter-btn" :class="{ active: filter === 'akademik' }" @click="filter = 'akademik'">Akademik</button>
                <button class="dev-filter-btn" :class="{ active: filter === 'softskill' }" @click="filter = 'softskill'">Soft Skill</button>
                <button class="dev-filter-btn" :class="{ active: filter === 'karier' }" @click="filter = 'karier'">Kesiapan Karier</button>
            </div>
            <div class="dev-checklist-container">
                <label v-for="t in shown" :key="t.id" class="dev-chk-item" :class="{ checked: t.done }">
                    <input type="checkbox" class="dev-chk-native" :checked="t.done" @change="toggle(t)">
                    <span class="dev-chk-circle"><svg class="dev-chk-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12" /></svg></span>
                    <div class="dev-chk-content">
                        <span class="dev-chk-title">{{ t.text }}</span>
                        <div class="dev-chk-meta-row">
                            <span class="dev-chk-tag" :class="t.cat">{{ CAT[t.cat] }}</span>
                            <span class="dev-chk-hint">• {{ t.hint }}</span>
                            <button v-if="t.custom" type="button" class="v2-link dev-chk-remove" @click.prevent="remove(t)">Hapus</button>
                        </div>
                    </div>
                </label>
            </div>
        </div>
        <section class="star-catalog">
            <h2>Aktivitas untuk langkahmu</h2>
            <div v-if="activities.length" class="star-grid" style="margin-bottom:18px">
                <article v-for="a in activities" :key="a.id" class="star-card">
                    <h3>{{ a.title }}</h3>
                    <p v-if="a.body">{{ a.body }}</p>
                    <button type="button" class="star-detail" :disabled="hasTarget(a.title)" @click="adopt(a)">{{ hasTarget(a.title) ? 'Sudah jadi target ✓' : 'Jadikan target saya →' }}</button>
                </article>
            </div>
            <p>Lihat bidang, keterampilan, dan sumber referensi yang dipakai ArahDiri.</p>
            <Go href="/referensi" class="btn">Lihat bidang, skill &amp; sumber referensi →</Go>
        </section>
    </div>
</template>
