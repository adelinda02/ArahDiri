<script setup>
// Tab "Kampus": kampus pilihan tiap rekomendasi + kampus lain yang juga membuka prodinya.
import { computed, reactive } from 'vue';
import Go from '../Go.vue';
import LineIcon from './LineIcon.vue';
import { campusPlace, campusShort, ownershipLabel, siteUrl, tanggal, KIND_ICON } from '../../lib/format';

const props = defineProps({ recs: { type: Object, required: true } });
const isMhs = computed(() => props.recs.level === 'mhs');
const list = computed(() => (isMhs.value ? [...props.recs.switch_programs, ...props.recs.local_programs] : props.recs.programs));
const now = computed(() => props.recs.current);

// Tampilan saja: rincian akreditasi tanpa predikat (predikat tampil sebagai badge).
const accDetail = (a) => {
    if (!a) return 'Belum ada data akreditasi';
    const parts = [];
    if (a.decree_no) parts.push('SK ' + a.decree_no + (a.body ? ` (${a.body})` : ''));
    if (a.expires_at) parts.push('Berlaku s.d. ' + tanggal(a.expires_at));
    return parts.join(' · ') || 'Rincian SK belum tersedia';
};
// Kampus lain: 3 pertama tampil, sisanya bisa dibuka/tutup per kartu.
const ALT_VISIBLE = 3;
const openAlt = reactive({});
const altShown = (p) => (openAlt[p.id + '-' + p.level] ? p.alt_campuses : p.alt_campuses.slice(0, ALT_VISIBLE));
const toggleAlt = (p) => { const k = p.id + '-' + p.level; openAlt[k] = !openAlt[k]; };
const UI_ICON = {
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    pin: '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
    award: '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 7 5-3 5 3-1.5-7"/>',
    layers: '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
    chevron: '<path d="m6 9 6 6 6-6"/>',
};
</script>

<template>
    <section class="star-catalog rec-tab rkp">
        <span class="v2-label">KAMPUS PILIHAN</span>
        <h2>{{ isMhs ? 'Kampusmu dan Kampus untuk Jalur Pindah Jurusan' : 'Kampus untuk Setiap Rekomendasi' }}</h2>
        <p class="rkp-notice">
            <LineIcon :paths="UI_ICON.info" />
            <span>
                <template v-if="!isMhs">Kampus utama dipilih berurutan dari akreditasi prodi, akreditasi kampus, PTN/PTKN lebih dulu, masa berlaku terpanjang, lalu nama kampus. Ketiga kampus utama selalu berbeda. </template>
                <template v-if="recs.accreditation_date">Akreditasi menurut Direktori BAN-PT per {{ tanggal(recs.accreditation_date) }}.</template>
                <template v-else>Data akreditasi BAN-PT belum disinkron; kampus diambil dari katalog PDDikti dan akreditasinya belum diverifikasi.</template>
            </span>
        </p>

        <article v-if="isMhs && now" class="rkp-own">
            <span class="rkp-tag">Kampusmu sekarang</span>
            <h3>{{ now.campus?.name }}</h3>
            <p>{{ ownershipLabel(now.campus) }}<template v-if="campusPlace(now.campus)"> · {{ campusPlace(now.campus) }}</template></p>
            <p>{{ now.name }} ({{ now.level }}): {{ now.campus?.program_rating ? 'akreditasi ' + now.campus.program_rating : 'akreditasi belum tersedia' }}<template v-if="now.campus?.campus_rating"> · kampus {{ now.campus.campus_rating }}</template></p>
            <p v-if="recs.local_programs.length">Prodi lain di kampus ini yang lebih cocok: <b>{{ recs.local_programs.map((p) => p.name).join(', ') }}</b>.</p>
        </article>

        <div class="rkp-stack">
            <article v-for="p in list" :key="p.id + '-' + p.level" class="rkp-card">
                <!-- Kolom 1: kampus utama -->
                <div class="rkp-col rkp-main">
                    <span class="rkp-tag">{{ p.name }} ({{ p.level }})</span>
                    <div class="rkp-campus">
                        <span class="rkp-campus-icon"><LineIcon :paths="KIND_ICON.majors" /></span>
                        <div>
                            <h3>{{ p.campus.name || 'Belum ada kampus yang memenuhi syarat' }}</h3>
                            <p v-if="p.campus.name" class="rkp-place"><LineIcon :paths="UI_ICON.pin" /><span>{{ ownershipLabel(p.campus) }}<template v-if="campusPlace(p.campus)"> · {{ campusPlace(p.campus) }}</template><template v-if="p.same_campus"> · kampusmu sendiri</template></span></p>
                            <a v-if="p.campus.website" class="rkp-site" :href="siteUrl(p.campus.website)" target="_blank" rel="noopener">Situs resmi ↗</a>
                        </div>
                    </div>
                </div>

                <!-- Kolom 2: akreditasi -->
                <div v-if="p.campus.name" class="rkp-col rkp-acc">
                    <div class="rkp-acc-item">
                        <span class="rkp-acc-icon"><LineIcon :paths="UI_ICON.award" /></span>
                        <div>
                            <p class="rkp-acc-head">Akreditasi prodi <span v-if="p.campus.program_accreditation?.rating" class="rkp-badge">{{ p.campus.program_accreditation.rating }}</span></p>
                            <p class="rkp-acc-text">{{ accDetail(p.campus.program_accreditation) }}</p>
                        </div>
                    </div>
                    <div class="rkp-acc-item">
                        <span class="rkp-acc-icon"><LineIcon :paths="KIND_ICON.campuses" /></span>
                        <div>
                            <p class="rkp-acc-head">Akreditasi kampus <span v-if="p.campus.campus_accreditation?.rating" class="rkp-badge">{{ p.campus.campus_accreditation.rating }}</span></p>
                            <p class="rkp-acc-text">{{ accDetail(p.campus.campus_accreditation) }}</p>
                        </div>
                    </div>
                    <p v-if="p.campus.status !== 'ready'" class="rec-warn-soft">Akreditasi belum diverifikasi dari Direktori BAN-PT.</p>
                    <p v-if="p.campus.program_accreditation?.expiring_soon" class="rec-warn">Akreditasi prodi segera berakhir.</p>
                </div>

                <!-- Kolom 3: pilihan kampus lain -->
                <div class="rkp-col rkp-alt">
                    <p class="rkp-alt-title">Pilihan kampus lain</p>
                    <ul v-if="p.alt_campuses?.length" class="rkp-alt-list">
                        <li v-for="c in altShown(p)" :key="c.campus_id">
                            <span class="rkp-alt-icon"><LineIcon :paths="KIND_ICON.majors" /></span>
                            <div><b>{{ c.name }}</b><small>{{ campusShort(c) }}</small></div>
                            <span class="rkp-chip">{{ c.program_rating ? 'Prodi ' + c.program_rating : 'Belum diverifikasi' }}</span>
                        </li>
                    </ul>
                    <p v-else class="rkp-alt-empty">Belum ada kampus lain di daftar pilihan untuk prodi ini.</p>
                    <button v-if="p.alt_campuses?.length > ALT_VISIBLE" type="button" class="rkp-more" :aria-expanded="openAlt[p.id + '-' + p.level] ? 'true' : 'false'" @click="toggleAlt(p)">
                        {{ openAlt[p.id + '-' + p.level] ? 'Tutup' : `Tampilkan ${p.alt_campuses.length - ALT_VISIBLE} lainnya` }}
                        <LineIcon :paths="UI_ICON.chevron" :class="{ 'is-open': openAlt[p.id + '-' + p.level] }" />
                    </button>
                    <Go :href="'/jurusan/' + p.id" class="rkp-all">Semua {{ p.offerings_count || '' }} kampus untuk {{ p.name }} <span aria-hidden="true">→</span></Go>
                </div>
            </article>
        </div>

        <div class="rkp-actions"><Go href="/kampus" class="rk-explore"><LineIcon :paths="UI_ICON.layers" />Jelajahi katalog kampus <span aria-hidden="true">→</span></Go></div>
    </section>
</template>
