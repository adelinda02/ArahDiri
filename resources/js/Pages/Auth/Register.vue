<script setup>
// Halaman daftar v14 (vLogin mode daftar). Jenjang: SMP, SMA/SMK, Mahasiswa.
import { Head, useForm } from '@inertiajs/vue3';
import Go from '../../Components/Go.vue';
import PasswordInput from '../../Components/PasswordInput.vue';
import { VIEW_URL } from '../../lib/routes';

defineProps({ levels: { type: Object, required: true } });
const form = useForm({ username: '', email: '', phone: '', level: '', password: '', password_confirmation: '' });
function submit() {
    form.post('/daftar', { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <Head title="Daftar" />
    <section class="login-wrap">
        <div class="login-card" style="max-width:460px;margin:32px auto;padding:32px">
            <img src="/brand-logo.png" alt="ArahDiri" style="width:64px;height:64px;border-radius:50%;display:block;margin:0 auto 16px">
            <h1 style="font-size:28px">Buat akun ArahDiri</h1>
            <p>Simpan hasil tes dan perkembanganmu di akun pribadi.</p>
            <form id="account-form" class="login-form" novalidate @submit.prevent="submit">
                <label>Nama pengguna<input v-model="form.username" class="login-input" name="username" required maxlength="40" autocomplete="username" placeholder="Nama pengguna unik">
                    <span v-if="form.errors.username" class="field-error">{{ form.errors.username }}</span></label>
                <label>Email<input v-model="form.email" class="login-input" type="email" name="email" required autocomplete="email" placeholder="nama@email.com">
                    <span v-if="form.errors.email" class="field-error">{{ form.errors.email }}</span></label>
                <label>Nomor HP<input v-model="form.phone" class="login-input" type="tel" name="phone" required autocomplete="tel" placeholder="08xxxxxxxxxx">
                    <span v-if="form.errors.phone" class="field-error">{{ form.errors.phone }}</span></label>
                <label>Jenjang pendidikan<select v-model="form.level" class="login-input" name="level" required>
                        <option value="">Pilih jenjang</option>
                        <option v-for="(label, key) in levels" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <span v-if="form.errors.level" class="field-error">{{ form.errors.level }}</span></label>
                <label>Kata sandi<PasswordInput v-model="form.password" name="password" required minlength="10" maxlength="128" autocomplete="new-password" placeholder="Minimal 10 karakter" />
                    <span v-if="form.errors.password" class="field-error">{{ form.errors.password }}</span></label>
                <label>Konfirmasi kata sandi<PasswordInput v-model="form.password_confirmation" name="password_confirmation" required minlength="10" autocomplete="new-password" /></label>
                <p v-if="form.level === 'mhs'" style="font-size:.9rem">Jenjang mahasiswa untuk mahasiswa aktif dari kampus mana pun. Setelah daftar, kamu akan memilih kampus dan prodi yang sedang kamu jalani.</p>
                <p id="account-error" role="alert" style="color:#b3261e"></p>
                <button class="btn btn-primary" type="submit" :disabled="form.processing">{{ form.processing ? 'Memproses…' : 'Daftar akun' }}</button>
            </form>
            <p><Go :href="VIEW_URL.login" class="btn">Sudah punya akun? Masuk</Go></p>
            <p><Go :href="VIEW_URL.jurusan" class="btn">Jelajahi sebagai tamu</Go></p>
            <small>Gunakan email dan nomor HP milik Anda. Dengan mendaftar, kamu menyetujui <Go href="/privasi" class="v2-link">kebijakan privasi</Go> ArahDiri.</small>
        </div>
    </section>
</template>
