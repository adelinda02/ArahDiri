<script setup>
// Langkah awal jenjang Mahasiswa: pilih kampus (cari) lalu prodi yang sedang dijalani. Pengakuan sendiri, tanpa verifikasi.
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    current: { type: Object, default: null }, // { program_offering_id, program, campus, semester }
    feelsMismatch: { type: Boolean, default: null }, // jawaban "merasa salah jurusan?"; null = belum menjawab
});

const form = useForm({
    program_offering_id: props.current?.program_offering_id ?? null,
    semester: props.current?.semester ?? '',
    self_declared: false,
    feels_mismatch: props.feelsMismatch,
});

const getJson = async (url) => {
    const r = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
};

// 1) Kampus
const campus = ref(props.current?.campus ?? null);
const campusQuery = ref('');
const campusResults = ref([]);
const searching = ref(false);
let timer;
watch(campusQuery, (q) => {
    clearTimeout(timer);
    if (q.trim().length < 2) { campusResults.value = []; return; }
    timer = setTimeout(async () => {
        searching.value = true;
        try { campusResults.value = await getJson('/mulai/prodi/kampus?q=' + encodeURIComponent(q.trim())); } catch (e) { campusResults.value = []; }
        searching.value = false;
    }, 250);
});

// 2) Prodi di kampus terpilih
const programs = ref([]);
const loadingPrograms = ref(false);
const programFilter = ref('');
async function loadPrograms(id) {
    loadingPrograms.value = true;
    try { programs.value = await getJson('/mulai/prodi/kampus/' + id); } catch (e) { programs.value = []; }
    loadingPrograms.value = false;
}
if (campus.value) loadPrograms(campus.value.id);

function chooseCampus(c) {
    campus.value = c;
    campusQuery.value = '';
    campusResults.value = [];
    form.program_offering_id = null;
    programFilter.value = '';
    loadPrograms(c.id);
}
function changeCampus() {
    campus.value = null;
    programs.value = [];
    form.program_offering_id = null;
}
const shownPrograms = computed(() => {
    const f = programFilter.value.trim().toLowerCase();
    return f ? programs.value.filter((p) => (p.name + ' ' + p.level).toLowerCase().includes(f)) : programs.value;
});
const place = (c) => [c.city, c.province].filter(Boolean).join(', ');

function submit() { form.post('/mulai/prodi'); }
</script>

<template>
    <Head title="Kampus dan prodimu" />
    <section class="login-wrap">
        <div class="login-card" style="max-width:560px;margin:32px auto;padding:32px">
            <img src="/brand-logo.png" alt="ArahDiri" style="width:64px;height:64px;border-radius:50%;display:block;margin:0 auto 16px">
            <h1 style="font-size:28px">Kampus dan prodimu saat ini</h1>
            <p>Pilih kampus dan prodi D3/D4/S1 yang sedang kamu jalani, lalu jawab satu pertanyaan singkat. Jawabanmu menentukan arah rekomendasi setelah tes.</p>
            <form class="login-form" novalidate @submit.prevent="submit">
                <!-- Langkah 1: kampus -->
                <div class="onb-step"><span class="onb-step-no">1</span><b>Kampus</b></div>
                <div v-if="campus" class="onb-chosen">
                    <div><b>{{ campus.name }}</b><small v-if="place(campus)">{{ place(campus) }}</small></div>
                    <button type="button" class="v2-link" @click="changeCampus">Ganti</button>
                </div>
                <template v-else>
                    <label>Cari kampus<input v-model="campusQuery" class="login-input" type="search" placeholder="Contoh: Universitas Indonesia atau Bandung" autocomplete="off"></label>
                    <div v-if="campusQuery.trim().length >= 2" class="umb-prodi-list" role="listbox" aria-label="Hasil pencarian kampus">
                        <button v-for="c in campusResults" :key="c.id" type="button" class="umb-prodi-option onb-option" @click="chooseCampus(c)">
                            <span>{{ c.name }} <small>{{ place(c) || 'Lokasi tidak tercatat' }}</small></span>
                        </button>
                        <p v-if="searching" style="opacity:.7">Mencari…</p>
                        <p v-else-if="!campusResults.length" style="opacity:.7">Kampus tidak ditemukan. Coba nama singkat atau nama kota.</p>
                    </div>
                </template>

                <!-- Langkah 2: prodi -->
                <template v-if="campus">
                    <div class="onb-step"><span class="onb-step-no">2</span><b>Prodi</b></div>
                    <label>Cari prodi<input v-model="programFilter" class="login-input" type="search" placeholder="Contoh: Teknik Informatika" autocomplete="off"></label>
                    <div class="umb-prodi-list" role="radiogroup" aria-label="Prodi">
                        <p v-if="loadingPrograms" style="opacity:.7">Memuat prodi…</p>
                        <label v-for="p in shownPrograms" v-else :key="p.id" class="umb-prodi-option" :class="{ selected: form.program_offering_id === p.id }">
                            <input v-model="form.program_offering_id" type="radio" name="program_offering_id" :value="p.id">
                            <span>{{ p.name }} <small>{{ p.level }}<template v-if="p.field"> · {{ p.field }}</template></small></span>
                        </label>
                        <p v-if="!loadingPrograms && !shownPrograms.length" style="opacity:.7">Prodi tidak ditemukan.</p>
                    </div>
                </template>
                <span v-if="form.errors.program_offering_id" class="field-error">{{ form.errors.program_offering_id }}</span>

                <!-- Langkah 3: perasaan terhadap jurusan -->
                <div class="onb-step"><span class="onb-step-no">3</span><b>Apakah saat ini kamu merasa salah jurusan?</b></div>
                <div class="onb-choice" role="radiogroup" aria-label="Apakah saat ini kamu merasa salah jurusan?">
                    <label class="umb-prodi-option" :class="{ selected: form.feels_mismatch === true }">
                        <input v-model="form.feels_mismatch" type="radio" name="feels_mismatch" :value="true">
                        <span>Iya, merasa salah jurusan <small>Kami carikan karier yang cocok dengan minatmu, lalu jurusan yang sejalan dengan karier itu.</small></span>
                    </label>
                    <label class="umb-prodi-option" :class="{ selected: form.feels_mismatch === false }">
                        <input v-model="form.feels_mismatch" type="radio" name="feels_mismatch" :value="false">
                        <span>Tidak, aku suka jurusanku <small>Kami tunjukkan profesi dari jurusanmu yang paling cocok dengan minatmu.</small></span>
                    </label>
                </div>
                <span v-if="form.errors.feels_mismatch" class="field-error">{{ form.errors.feels_mismatch }}</span>

                <label>Semester saat ini<select v-model="form.semester" class="login-input" name="semester">
                        <option value="">Pilih semester</option>
                        <option v-for="s in 14" :key="s" :value="s">Semester {{ s }}</option>
                    </select>
                    <span v-if="form.errors.semester" class="field-error">{{ form.errors.semester }}</span></label>
                <label class="login-remember-wrap"><input v-model="form.self_declared" type="checkbox"> Saya menyatakan sedang kuliah di prodi dan kampus ini.</label>
                <span v-if="form.errors.self_declared" class="field-error">{{ form.errors.self_declared }}</span>
                <button class="btn btn-primary" type="submit" :disabled="form.processing || !form.program_offering_id || form.feels_mismatch === null">Simpan dan lanjut</button>
            </form>
        </div>
    </section>
</template>
