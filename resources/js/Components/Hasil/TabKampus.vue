<script setup>
// Tab "Kampus": kampus terpilih beserta akreditasi prodi & kampus (salinan saat tes selesai).
import { computed } from 'vue';
import Go from '../Go.vue';
import { accLine, campusPlace, ownershipLabel, tanggal } from '../../lib/format';

const props = defineProps({ recs: { type: Object, required: true } });
const isUmb = computed(() => props.recs.level === 'mhs_umb');
const list = computed(() => (isUmb.value ? [...props.recs.umb_programs, ...props.recs.umb_s1_path] : props.recs.programs));
const url = (w) => (w.startsWith('http') ? w : 'https://' + w);
</script>

<template>
    <section class="star-catalog rec-tab">
        <span class="v2-label">{{ isUmb ? 'UNIVERSITAS MERCU BUANA' : 'KAMPUS PILIHAN' }}</span>
        <h2>{{ isUmb ? 'Akreditasi prodi UMB yang direkomendasikan' : 'Satu kampus untuk setiap rekomendasi' }}</h2>
        <p class="star-notice">
            <template v-if="!isUmb">Urutan pemilihan: akreditasi prodi, akreditasi kampus, PTN/PTKN lebih dulu, masa berlaku lebih panjang, lalu nama kampus. Ketiga kampus selalu berbeda. </template>
            <template v-if="recs.accreditation_date">Akreditasi menurut Direktori BAN-PT per {{ tanggal(recs.accreditation_date) }}.</template>
            <template v-else>Data akreditasi BAN-PT belum disinkron.</template>
        </p>
        <div class="star-grid rec-campus-grid">
            <article v-for="p in list" :key="p.id + '-' + p.level" class="star-card rec-campus">
                <div class="star-card-top"><span>{{ p.name }} ({{ p.level }})</span></div>
                <template v-if="p.campus.name">
                    <h3>{{ p.campus.name }}</h3>
                    <p>{{ ownershipLabel(p.campus) }}<template v-if="campusPlace(p.campus)"> · {{ campusPlace(p.campus) }}</template></p>
                    <dl class="rec-acc-list">
                        <dt>Akreditasi prodi</dt><dd>{{ accLine(p.campus.program_accreditation) }}</dd>
                        <dt>Akreditasi kampus</dt><dd>{{ accLine(p.campus.campus_accreditation) }}</dd>
                    </dl>
                    <p v-if="p.campus.program_accreditation?.expiring_soon" class="rec-warn">Akreditasi prodi segera berakhir.</p>
                    <a v-if="p.campus.website" class="star-detail" :href="url(p.campus.website)" target="_blank" rel="noopener">Situs resmi →</a>
                </template>
                <template v-else>
                    <h3>Menunggu data akreditasi</h3>
                    <p>Kampus dipilih otomatis setelah data akreditasi Direktori BAN-PT tersedia.</p>
                </template>
            </article>
        </div>
        <div class="btn-row" style="margin-top:18px"><Go href="/kampus" class="btn">Jelajahi katalog kampus</Go></div>
    </section>
</template>
