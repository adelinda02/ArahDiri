<script setup>
// Konten yang dikelola admin (setara menu konten v14): teks, bidang, skill, aktivitas, sumber referensi.
import { ref, reactive, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ kind: String, kinds: Array, items: Array, activityCategories: Object });
const page = usePage();
const errors = computed(() => page.props.errors || {});
const current = computed(() => props.kinds.find((k) => k.key === props.kind));
const WHERE = { copy: 'Halaman awal & Referensi', scope: 'Halaman Referensi', skill: 'Detail karier & Referensi', activity: 'Halaman Langkah', reference: 'Halaman Referensi' };

const dlg = ref(null);
const f = reactive({});
const editing = ref(null);
function open(item = null) {
    editing.value = item;
    Object.keys(f).forEach((k) => delete f[k]);
    Object.assign(f, {
        id: item?.id ?? null, slug: item?.slug ?? null, version: item?.version ?? 0,
        title: item?.title ?? '', body: item?.body ?? '', status: item?.status ?? 'active', position: item?.position ?? 0,
        url: item?.meta?.url ?? '', category: item?.meta?.category ?? 'akademik', careers: [...(item?.careers ?? [])],
    });
    picker.q = ''; picker.found = [];
    dlg.value.showModal();
}
function save() {
    router.post('/admin/konten', {
        kind: props.kind, id: f.id, slug: f.slug, version: f.version, title: f.title, body: f.body, status: f.status, position: f.position,
        meta: { url: f.url || null, category: f.category, kbji: f.careers.map((c) => c.id) },
    }, { preserveScroll: true, onSuccess: () => { if (!Object.keys(page.props.errors || {}).length) dlg.value.close(); } });
}
const remove = (x) => confirm(`Hapus “${x.title}”?`) && router.delete(`/admin/konten/${x.id}`, { preserveScroll: true });
const toggleStatus = (x) => router.post('/admin/konten', { kind: props.kind, id: x.id, slug: x.slug, version: x.version, title: x.title, body: x.body, status: x.status === 'active' ? 'hidden' : 'active', position: x.position, meta: { url: x.meta?.url, category: x.meta?.category, kbji: x.meta?.kbji } }, { preserveScroll: true });

const picker = reactive({ q: '', found: [] });
let t;
function findCareers() {
    clearTimeout(t);
    t = setTimeout(async () => {
        const r = await fetch('/admin/katalog/cari-karier?q=' + encodeURIComponent(picker.q), { headers: { Accept: 'application/json' } });
        picker.found = r.ok ? (await r.json()).filter((c) => !f.careers.some((x) => x.id === c.id)) : [];
    }, 250);
}
</script>

<template>
    <Head title="Admin · Konten" />
    <Topline title="Konten" :desc="current.desc">
        <button type="button" class="primary" @click="open()">Tambah {{ current.label.toLowerCase() }}</button>
    </Topline>
    <div class="toolbar">
        <Link v-for="k in kinds" :key="k.key" as="button" :href="`/admin/konten?jenis=${k.key}`" :class="{ primary: k.key === kind }">{{ k.label }} ({{ k.count }})</Link>
    </div>
    <p class="notice" style="margin-bottom:16px">Tampil di: <b>{{ WHERE[kind] }}</b>. Item berstatus “Disembunyikan” tidak tampil di website.</p>

    <div class="table-wrap"><table>
        <thead><tr><th>Judul</th><th>Isi</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody><tr v-for="x in items" :key="x.id ?? x.slug">
            <td><strong>{{ x.title }}</strong><small>{{ x.fixed ? 'Teks tetap · ' + x.slug : x.slug }}<template v-if="x.meta?.url"> · {{ x.meta.url }}</template><template v-if="x.meta?.category"> · {{ activityCategories[x.meta.category] }}</template><template v-if="x.careers?.length"> · {{ x.careers.length }} karier</template></small></td>
            <td><small>{{ (x.body || (x.fixed ? 'Bawaan: ' + x.default : '-')).slice(0, 140) }}{{ (x.body || '').length > 140 ? '…' : '' }}</small></td>
            <td><span class="badge" :class="{ hidden: x.status !== 'active' }">{{ x.status === 'active' ? 'Tampil' : 'Disembunyikan' }}</span></td>
            <td class="actions">
                <button type="button" class="primary" @click="open(x)">Sunting</button>
                <button v-if="x.id && !x.fixed" type="button" @click="toggleStatus(x)">{{ x.status === 'active' ? 'Sembunyikan' : 'Tampilkan' }}</button>
                <button v-if="x.id && !x.fixed" type="button" class="danger" @click="remove(x)">Hapus</button>
            </td>
        </tr></tbody>
    </table><p v-if="!items.length" style="padding:20px">Belum ada {{ current.label.toLowerCase() }}. Klik “Tambah” untuk membuat.</p></div>

    <dialog ref="dlg" aria-labelledby="kt-title">
        <div class="dialog-head">
            <div><span class="eyebrow">{{ current.label }}</span><h2 id="kt-title">{{ editing ? 'Ubah' : 'Tambah' }} {{ current.label.toLowerCase() }}</h2></div>
            <button type="button" class="quiet" aria-label="Tutup" @click="dlg.close()">✕</button>
        </div>
        <p v-if="errors.version" class="error">{{ errors.version }}</p>
        <form class="form-grid" @submit.prevent="save">
            <div class="field wide"><label for="kt-t">Judul</label><input id="kt-t" v-model="f.title" maxlength="255" required :readonly="editing?.fixed"><small v-if="errors.title" class="error">{{ errors.title }}</small></div>
            <div class="field wide">
                <label for="kt-b">{{ kind === 'copy' ? 'Teks' : 'Deskripsi / penjelasan' }}</label>
                <textarea id="kt-b" v-model="f.body" :rows="kind === 'copy' ? 6 : 4" maxlength="6000" :placeholder="editing?.default || ''"></textarea>
                <small v-if="editing?.fixed">Kosongkan untuk kembali ke teks bawaan.</small>
                <small v-else-if="kind === 'copy'">Teks tambahan tampil sebagai bagian baru di halaman Referensi.</small>
                <small v-if="errors.body" class="error">{{ errors.body }}</small>
            </div>
            <div v-if="kind === 'reference'" class="field wide"><label for="kt-u">URL sumber</label><input id="kt-u" v-model="f.url" type="url" placeholder="https://…"><small v-if="errors['meta.url']" class="error">{{ errors['meta.url'] }}</small></div>
            <div v-if="kind === 'activity'" class="field"><label for="kt-c">Kategori target</label><select id="kt-c" v-model="f.category"><option v-for="(l, k) in activityCategories" :key="k" :value="k">{{ l }}</option></select></div>
            <div v-if="!editing?.fixed" class="field"><label for="kt-s">Status</label><select id="kt-s" v-model="f.status"><option value="active">Tampil</option><option value="hidden">Disembunyikan</option></select></div>
            <div v-if="!editing?.fixed" class="field"><label for="kt-p">Urutan</label><input id="kt-p" v-model.number="f.position" type="number" min="0" max="999"><small>Angka kecil tampil lebih dulu.</small></div>
            <div v-if="kind === 'skill'" class="relation">
                <h3>Karier terkait</h3>
                <small>Skill ini tampil di detail karier yang dipilih.</small>
                <div class="selected"><button v-for="(c, i) in f.careers" :key="c.id" type="button" @click="f.careers.splice(i, 1)">{{ c.label }} ✕</button></div>
                <input v-model="picker.q" placeholder="Cari karier (judul atau kode KBJI)…" aria-label="Cari karier" @input="findCareers">
                <div class="picker-results"><button v-for="c in picker.found" :key="c.id" type="button" @click="f.careers.push(c); picker.found = picker.found.filter((x) => x.id !== c.id)">+ {{ c.label }}</button></div>
            </div>
            <div class="form-actions"><button type="button" @click="dlg.close()">Batal</button><button class="primary" type="submit">Simpan</button></div>
        </form>
    </dialog>
</template>
