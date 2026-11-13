<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';
import Pager from '../../Components/Admin/Pager.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ type: String, items: Object, filters: Object });
const page = usePage();
const q = ref(props.filters.q ?? '');
const onlyEdited = ref(!!props.filters.disunting);
const TYPES = { prodi: 'Prodi', karier: 'Karier', smk: 'Program SMK', sma: 'Arah SMA', kampus: 'Kampus' };
const EDITABLE = ['prodi', 'karier', 'smk', 'sma'];
const go = (jenis) => router.get('/admin/katalog', { jenis, ...(q.value ? { q: q.value } : {}), ...(onlyEdited.value ? { disunting: 1 } : {}) }, { preserveState: true });
const toggle = (id) => router.post('/admin/katalog/tampil', { jenis: props.type, id }, { preserveScroll: true });
const reimport = () => confirm('Impor ulang seluruh katalog dari CSV? Baris yang sudah disunting admin tidak ditimpa. Proses ini bisa memakan waktu beberapa menit.') && router.post('/admin/katalog/impor');
const label = { prodi: ['Direkomendasikan', 'Tidak direkomendasikan', 'Sembunyikan', 'Tampilkan'], karier: ['Aktif', 'Disembunyikan', 'Sembunyikan', 'Tampilkan'], kampus: ['Aktif', 'Disembunyikan', 'Sembunyikan', 'Tampilkan'] };

// ------------------------------------------------------------ editor
const DIMS = [['r', 'R'], ['i', 'I'], ['a', 'A'], ['s', 'S'], ['e', 'E'], ['c', 'C']];
const dlg = ref(null);
const ed = ref(null); // data dari server
const f = reactive({});
const saving = ref(false);
const errors = computed(() => page.props.errors || {});

async function open(x) {
    const r = await fetch(`/admin/katalog/${props.type}/${encodeURIComponent(x.id)}`, { headers: { Accept: 'application/json' } });
    if (!r.ok) return alert('Gagal memuat data.');
    ed.value = await r.json();
    const d = ed.value;
    Object.keys(f).forEach((k) => delete f[k]);
    Object.assign(f, {
        stamp: d.stamp, riasec: { ...d.riasec },
        title: d.title, description: d.description ?? '', is_active: d.is_active, is_recommendable: d.is_recommendable,
        skills: [...(d.skills || [])], support_override: d.support_override ? [...d.support_override] : [], careers: [...(d.careers || [])],
        next_keywords: d.next_keywords ?? '', next_exclude: d.next_exclude ?? '', name: d.name, subjects: (d.subjects || []).join('\n'),
    });
    picker.q = ''; picker.found = [];
    dlg.value.showModal();
}
const holland = computed(() => (f.riasec ? DIMS.map(([d]) => d).sort((a, b) => (f.riasec[b] ?? 0) - (f.riasec[a] ?? 0)).slice(0, 3).join('').toUpperCase() : ''));
function save() {
    const d = ed.value;
    const base = { stamp: f.stamp, riasec: f.riasec };
    const body = {
        karier: { ...base, title: f.title, description: f.description, is_active: f.is_active, skills: f.skills },
        prodi: { ...base, description: f.description, is_recommendable: f.is_recommendable, support_override: f.support_override, careers: f.careers.map((c) => c.id) },
        smk: { ...base, next_keywords: f.next_keywords, next_exclude: f.next_exclude },
        sma: { ...base, name: f.name, subjects: f.subjects.split('\n').map((s) => s.trim()).filter(Boolean) },
    }[d.jenis];
    saving.value = true;
    router.put(`/admin/katalog/${d.jenis}/${encodeURIComponent(d.id)}`, body, {
        preserveScroll: true,
        onSuccess: () => { if (!Object.keys(page.props.errors || {}).length) open({ id: d.id }); },
        onFinish: () => (saving.value = false),
    });
}
const release = () => confirm('Lepas kunci suntingan? Impor ulang katalog berikutnya akan menimpa data ini dengan data sumber.') && router.post(`/admin/katalog/${ed.value.jenis}/${encodeURIComponent(ed.value.id)}/lepas`, {}, { preserveScroll: true, onSuccess: () => open({ id: ed.value.id }) });

// Pemilih karier terkait (prodi).
const picker = reactive({ q: '', found: [] });
let t;
function findCareers() {
    clearTimeout(t);
    t = setTimeout(async () => {
        const r = await fetch('/admin/katalog/cari-karier?q=' + encodeURIComponent(picker.q), { headers: { Accept: 'application/json' } });
        picker.found = r.ok ? (await r.json()).filter((c) => !f.careers.some((x) => x.id === c.id)) : [];
    }, 250);
}
const addCareer = (c) => { f.careers.push(c); picker.found = picker.found.filter((x) => x.id !== c.id); };
const moveUp = (i) => i > 0 && f.careers.splice(i - 1, 0, f.careers.splice(i, 1)[0]);
const toggleIn = (list, v) => (list.includes(v) ? list.splice(list.indexOf(v), 1) : list.push(v));
const when = (s) => (s ? new Date(String(s).replace(' ', 'T') + (String(s).includes('Z') ? '' : 'Z')).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '');
</script>

<template>
    <Head title="Admin · Katalog" />
    <Topline title="Katalog" desc="Cari lalu sunting prodi, karier, program SMK, dan arah SMA, atau sembunyikan item dari rekomendasi. Item yang disunting dikunci dari impor ulang katalog.">
        <button type="button" @click="reimport">Impor ulang katalog</button>
    </Topline>
    <form class="toolbar" @submit.prevent="go(type)">
        <select :value="type" aria-label="Jenis katalog" @change="go($event.target.value)"><option v-for="(l, k) in TYPES" :key="k" :value="k">{{ l }}</option></select>
        <input v-if="type !== 'sma'" v-model="q" placeholder="Cari nama atau kode…" aria-label="Cari katalog">
        <label v-if="['prodi', 'karier', 'smk'].includes(type)" class="kat-check"><input v-model="onlyEdited" type="checkbox" @change="go(type)"> Hanya yang disunting</label>
        <button v-if="type !== 'sma'" type="submit">Cari</button>
    </form>
    <div class="table-wrap"><table>
        <thead><tr><th>Nama / judul</th><th>Kode minat</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody><tr v-for="x in items.data" :key="x.id">
            <td><strong>{{ x.title }}</strong><small>{{ x.sub }}</small></td>
            <td>{{ x.holland || '-' }}</td>
            <td><span v-if="label[type]" class="badge" :class="{ hidden: !x.visible }">{{ x.visible ? label[type][0] : label[type][1] }}</span> <span v-if="x.edited" class="badge kat-edited">Disunting</span></td>
            <td class="actions">
                <button v-if="EDITABLE.includes(type)" type="button" class="primary" @click="open(x)">Sunting</button>
                <button v-if="label[type]" type="button" @click="toggle(x.id)">{{ x.visible ? label[type][2] : label[type][3] }}</button>
            </td>
        </tr></tbody>
    </table><p v-if="!items.data.length" style="padding:20px">Tidak ada data yang cocok.</p></div>
    <Pager :p="items" label="item" />

    <dialog ref="dlg" aria-labelledby="ed-title">
        <template v-if="ed">
            <div class="dialog-head">
                <div><span class="eyebrow">{{ TYPES[ed.jenis] }} · {{ ed.id }}</span><h2 id="ed-title">{{ ed.title || ed.name }}</h2></div>
                <button type="button" class="quiet" aria-label="Tutup" @click="dlg.close()">✕</button>
            </div>
            <p v-if="errors.stamp" class="error">{{ errors.stamp }}</p>
            <dl v-if="ed.info" class="kat-info"><template v-for="(v, k) in ed.info" :key="k"><dt>{{ k }}</dt><dd>{{ v }}</dd></template></dl>

            <form class="form-grid" @submit.prevent="save">
                <!-- Karier -->
                <template v-if="ed.jenis === 'karier'">
                    <div class="field wide"><label for="ed-t">Judul tampil</label><input id="ed-t" v-model="f.title" maxlength="255" required><small v-if="errors.title" class="error">{{ errors.title }}</small></div>
                    <div class="field wide"><label for="ed-d">Uraian tugas</label><textarea id="ed-d" v-model="f.description" rows="5" maxlength="5000"></textarea></div>
                    <label class="field kat-check"><input v-model="f.is_active" type="checkbox"> Aktif (boleh direkomendasikan & tampil di katalog)</label>
                </template>
                <!-- Prodi -->
                <template v-if="ed.jenis === 'prodi'">
                    <div class="field wide"><label for="ed-d">Deskripsi prodi (tampil di katalog jurusan)</label><textarea id="ed-d" v-model="f.description" rows="4" maxlength="5000"></textarea></div>
                    <label class="field kat-check"><input v-model="f.is_recommendable" type="checkbox"> Boleh direkomendasikan</label>
                    <div class="relation">
                        <h3>Mapel pendukung SNBP</h3>
                        <small>Otomatis dari nama prodi: <b>{{ ed.support_auto.join(', ') || 'tidak dikenali' }}</b>. Centang mapel untuk menimpa. Menentukan arah SMA di jalur SMP (sekarang: {{ ed.tracks.join(', ') || '-' }}).</small>
                        <div class="selected"><button v-for="m in ed.subjectOptions" :key="m" type="button" :class="{ primary: f.support_override.includes(m) }" @click="toggleIn(f.support_override, m)">{{ m }}</button></div>
                    </div>
                    <div class="relation">
                        <h3>Karier terkait (urutan = peringkat)</h3>
                        <small>Karier pertama adalah karier utama prodi. Dipakai untuk rekomendasi karier dan jalur SMP.</small>
                        <div class="selected"><button v-for="(c, i) in f.careers" :key="c.id" type="button" title="Klik untuk menghapus" @click="f.careers.splice(i, 1)">{{ i + 1 }}. {{ c.label }} ✕</button></div>
                        <div v-if="f.careers.length > 1" class="selected"><button v-for="(c, i) in f.careers.slice(1)" :key="c.id" type="button" class="quiet" @click="moveUp(i + 1)">↑ {{ c.label.split(' · ')[1] }}</button></div>
                        <input v-model="picker.q" placeholder="Cari karier (judul atau kode KBJI)…" aria-label="Cari karier" @input="findCareers">
                        <div class="picker-results"><button v-for="c in picker.found" :key="c.id" type="button" @click="addCareer(c)">+ {{ c.label }}</button></div>
                    </div>
                </template>
                <!-- Program SMK -->
                <template v-if="ed.jenis === 'smk'">
                    <div class="field"><label for="ed-k">Kata kunci prodi lanjutan (satu per baris)</label><textarea id="ed-k" v-model="f.next_keywords" rows="7" maxlength="2000"></textarea>
                        <small>{{ ed.using_default ? 'Masih memakai peta bawaan (draf). Simpan untuk memakai kata kunci ini.' : 'Memakai kata kunci suntingan admin.' }} Prodi cocok bila namanya mengandung salah satu kata.</small></div>
                    <div class="field"><label for="ed-x">Kecualikan bila nama mengandung</label><textarea id="ed-x" v-model="f.next_exclude" rows="7" maxlength="2000"></textarea><small>Mis. “hasil pertanian” agar Teknologi Hasil Pertanian tidak dianggap lanjutan Agribisnis Tanaman.</small></div>
                    <div class="relation"><h3>Prodi yang cocok saat ini ({{ ed.preview.length }}{{ ed.preview.length === 40 ? '+' : '' }})</h3><small>{{ ed.preview.join(' · ') || 'Belum ada yang cocok.' }}</small></div>
                    <div v-if="ed.jobs.length" class="relation"><h3>Jabatan lulusan (KBJI)</h3><small>{{ ed.jobs.join(' · ') }}</small></div>
                </template>
                <!-- Arah SMA -->
                <template v-if="ed.jenis === 'sma'">
                    <div class="field"><label for="ed-n">Nama arah</label><input id="ed-n" v-model="f.name" maxlength="40" required></div>
                    <div class="field wide"><label for="ed-s">Mata pelajaran pilihan (satu per baris)</label><textarea id="ed-s" v-model="f.subjects" rows="5"></textarea><small>Penjurusan SMA direncanakan kembali tahun 2027; sesuaikan setelah aturan terbit.</small></div>
                </template>

                <!-- Profil RIASEC -->
                <div class="relation">
                    <h3>Profil minat (skala O*NET 1–7) · kode {{ holland }}</h3>
                    <div class="riasec"><label v-for="[d, l] in DIMS" :key="d">{{ l }}<input v-model.number="f.riasec[d]" type="number" min="1" max="7" step="0.01" required></label></div>
                    <small>Perubahan profil memengaruhi peringkat rekomendasi. Gunakan data referensi yang dapat dipertanggungjawabkan, lalu cek hasilnya di simulator.</small>
                    <small v-if="errors['riasec.r'] || errors['riasec.i']" class="error">Nilai RIASEC harus 1–7.</small>
                </div>

                <!-- Relasi baca-saja -->
                <div v-if="ed.jenis === 'karier'" class="relation">
                    <h3>Skill terkait</h3>
                    <small v-if="!ed.skillOptions.length">Belum ada skill. Tambahkan di menu Konten → Skill.</small>
                    <div class="selected"><button v-for="s in ed.skillOptions" :key="s.id" type="button" :class="{ primary: f.skills.includes(s.id) }" @click="toggleIn(f.skills, s.id)">{{ s.label }}</button></div>
                    <h3>Prodi terkait</h3><small>{{ ed.programs.map((p) => p.label).join(' · ') || '-' }}</small>
                    <template v-if="ed.smk.length"><h3>Program SMK terkait</h3><small>{{ ed.smk.join(' · ') }}</small></template>
                </div>

                <div class="form-actions">
                    <button v-if="ed.edited" type="button" class="quiet" @click="release">Lepas kunci impor</button>
                    <button type="button" @click="dlg.close()">Batal</button>
                    <button class="primary" type="submit" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan' }}</button>
                </div>
            </form>

            <div v-if="ed.history.length" class="relation">
                <h3>Riwayat perubahan</h3>
                <small v-for="(h, i) in ed.history" :key="i" style="display:block">{{ when(h.at) }} · {{ h.by || 'sistem' }} · {{ h.action === 'catalog_released' ? 'melepas kunci' : h.action === 'catalog_toggled' ? 'mengubah tampil' : 'mengubah ' + (h.fields.join(', ') || '-') }}</small>
            </div>
        </template>
    </dialog>
</template>

<style scoped>
.kat-check { display: flex; align-items: center; gap: 8px; font-weight: 600; }
.kat-check input { width: auto; }
.kat-edited { background: #fff4d6; color: #8a5a00; }
.kat-info { display: grid; grid-template-columns: max-content 1fr; gap: 4px 16px; margin: 0 0 16px; font-size: 13px; }
.kat-info dt { color: var(--muted); }
.kat-info dd { margin: 0; }
.riasec label { display: grid; gap: 4px; font-weight: 700; font-size: 13px; }
</style>
