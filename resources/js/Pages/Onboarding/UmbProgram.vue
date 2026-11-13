<script setup>
// Langkah awal khusus mahasiswa UMB: pilih prodi UMB yang sedang dijalani (pengakuan sendiri, tanpa verifikasi).
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    programs: { type: Array, required: true }, // [{ id, name, level, field }]
    current: { type: Object, default: null },
});
const filter = ref('');
const form = useForm({
    program_offering_id: props.current?.program_offering_id ?? null,
    semester: props.current?.semester ?? '',
    self_declared: false,
});
const shown = computed(() => {
    const f = filter.value.trim().toLowerCase();
    return f ? props.programs.filter((p) => (p.name + ' ' + p.level).toLowerCase().includes(f)) : props.programs;
});
function submit() { form.post('/mulai/prodi-umb'); }
</script>

<template>
    <Head title="Pilih prodi UMB" />
    <section class="login-wrap">
        <div class="login-card" style="max-width:520px;margin:32px auto;padding:32px">
            <img src="/brand-logo.png" alt="ArahDiri" style="width:64px;height:64px;border-radius:50%;display:block;margin:0 auto 16px">
            <h1 style="font-size:28px">Prodi kamu saat ini</h1>
            <p>Jenjang mahasiswa ArahDiri khusus untuk mahasiswa Universitas Mercu Buana yang ingin mengecek kecocokan prodinya. Pilih prodi D3 atau S1 yang sedang kamu jalani.</p>
            <form class="login-form" novalidate @submit.prevent="submit">
                <label>Cari prodi<input v-model="filter" class="login-input" type="search" placeholder="Contoh: Teknik Informatika" autocomplete="off"></label>
                <div class="umb-prodi-list" role="radiogroup" aria-label="Prodi UMB">
                    <label v-for="p in shown" :key="p.id" class="umb-prodi-option" :class="{ selected: form.program_offering_id === p.id }">
                        <input v-model="form.program_offering_id" type="radio" name="program_offering_id" :value="p.id">
                        <span>{{ p.name }} <small>{{ p.level }}<template v-if="p.field"> · {{ p.field }}</template></small></span>
                    </label>
                    <p v-if="!shown.length" style="opacity:.7">Prodi tidak ditemukan.</p>
                </div>
                <span v-if="form.errors.program_offering_id" class="field-error">{{ form.errors.program_offering_id }}</span>
                <label>Semester saat ini<select v-model="form.semester" class="login-input" name="semester">
                        <option value="">Pilih semester</option>
                        <option v-for="s in 14" :key="s" :value="s">Semester {{ s }}</option>
                    </select>
                    <span v-if="form.errors.semester" class="field-error">{{ form.errors.semester }}</span></label>
                <label class="login-remember-wrap"><input v-model="form.self_declared" type="checkbox"> Saya menyatakan sedang kuliah di prodi ini di Universitas Mercu Buana.</label>
                <span v-if="form.errors.self_declared" class="field-error">{{ form.errors.self_declared }}</span>
                <button class="btn btn-primary" type="submit" :disabled="form.processing">Simpan dan lanjut</button>
            </form>
        </div>
    </section>
</template>
