<script setup>
// Tab "Kampus": kampus pilihan tiap rekomendasi + kampus lain yang juga membuka prodinya.
import { computed } from 'vue';
import Go from '../Go.vue';
import { accLine, campusPlace, campusShort, ownershipLabel, siteUrl, tanggal } from '../../lib/format';

const props = defineProps({ recs: { type: Object, required: true } });
const isMhs = computed(() => props.recs.level === 'mhs');
const list = computed(() => (isMhs.value ? [...props.recs.switch_programs, ...props.recs.local_programs] : props.recs.programs));
const now = computed(() => props.recs.current);
</script>

<template>
    <section class="star-catalog rec-tab">
        <span class="v2-label">KAMPUS PILIHAN</span>
        <h2>{{ isMhs ? 'Kampusmu dan kampus untuk jalur pindah jurusan' : 'Kampus untuk setiap rekomendasi' }}</h2>
        <p class="star-notice">
            <template v-if="!isMhs">Kampus utama dipilih berurutan dari akreditasi prodi, akreditasi kampus, PTN/PTKN lebih dulu, masa berlaku terpanjang, lalu nama kampus. Ketiga kampus utama selalu berbeda. </template>
            <template v-if="recs.accreditation_date">Akreditasi menurut Direktori BAN-PT per {{ tanggal(recs.accreditation_date) }}.</template>
            <template v-else>Data akreditasi BAN-PT belum disinkron; kampus diambil dari katalog PDDikti dan akreditasinya belum diverifikasi.</template>
        </p>

        <article v-if="isMhs && now" class="rx-campus-block rx-campus-own">
            <div class="rx-campus-head"><span class="rx-tag">Kampusmu sekarang</span><h3>{{ now.campus?.name }}</h3></div>
            <p>{{ ownershipLabel(now.campus) }}<template v-if="campusPlace(now.campus)"> · {{ campusPlace(now.campus) }}</template></p>
            <p>{{ now.name }} ({{ now.level }}): {{ now.campus?.program_rating ? 'akreditasi ' + now.campus.program_rating : 'akreditasi belum tersedia' }}<template v-if="now.campus?.campus_rating"> · kampus {{ now.campus.campus_rating }}</template></p>
            <p v-if="recs.local_programs.length">Prodi lain di kampus ini yang lebih cocok: <b>{{ recs.local_programs.map((p) => p.name).join(', ') }}</b>.</p>
        </article>

        <div class="rx-campus-stack">
            <article v-for="p in list" :key="p.id + '-' + p.level" class="rx-campus-block">
                <div class="rx-campus-head">
                    <span class="rx-tag">{{ p.name }} ({{ p.level }})</span>
                    <h3>{{ p.campus.name || 'Belum ada kampus yang memenuhi syarat' }}</h3>
                </div>
                <template v-if="p.campus.name">
                    <p>{{ ownershipLabel(p.campus) }}<template v-if="campusPlace(p.campus)"> · {{ campusPlace(p.campus) }}</template><template v-if="p.same_campus"> · kampusmu sendiri</template></p>
                    <dl class="rec-acc-list">
                        <dt>Akreditasi prodi</dt><dd>{{ accLine(p.campus.program_accreditation) }}</dd>
                        <dt>Akreditasi kampus</dt><dd>{{ accLine(p.campus.campus_accreditation) }}</dd>
                    </dl>
                    <p v-if="p.campus.status !== 'ready'" class="rec-warn-soft">Akreditasi belum diverifikasi dari Direktori BAN-PT.</p>
                    <p v-if="p.campus.program_accreditation?.expiring_soon" class="rec-warn">Akreditasi prodi segera berakhir.</p>
                    <a v-if="p.campus.website" class="star-detail" :href="siteUrl(p.campus.website)" target="_blank" rel="noopener">Situs resmi →</a>
                </template>
                <template v-if="p.alt_campuses?.length">
                    <p class="rx-steps-title">Pilihan kampus lain</p>
                    <ul class="rx-alt-campus">
                        <li v-for="c in p.alt_campuses" :key="c.campus_id">
                            <div><b>{{ c.name }}</b><br><small>{{ campusShort(c) }}</small></div>
                            <span class="rx-acc-chip">{{ c.program_rating ? 'Prodi ' + c.program_rating : 'Belum diverifikasi' }}</span>
                        </li>
                    </ul>
                </template>
                <Go :href="'/jurusan/' + p.id" class="v2-link">Semua {{ p.offerings_count || '' }} kampus untuk {{ p.name }} →</Go>
            </article>
        </div>
        <div class="btn-row" style="margin-top:18px"><Go href="/kampus" class="btn">Jelajahi katalog kampus</Go></div>
    </section>
</template>
