<script setup>
// Halaman masuk v14 (vLogin): satu formulir untuk pengguna dan admin.
import { Head, useForm } from '@inertiajs/vue3';
import Go from '../../Components/Go.vue';
import { VIEW_URL } from '../../lib/routes';

const form = useForm({ identifier: '', password: '', remember: false });
function submit() {
    form.post('/masuk', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Masuk" />
    <section class="login-wrap">
        <div class="login-card" style="max-width:460px;margin:32px auto;padding:32px">
            <img src="/brand-logo.png" alt="ArahDiri" style="width:64px;height:64px;border-radius:50%;display:block;margin:0 auto 16px">
            <h1 style="font-size:28px">Masuk ke ArahDiri</h1>
            <p>Satu halaman masuk untuk pengguna dan admin.</p>
            <form id="account-form" class="login-form" novalidate @submit.prevent="submit">
                <label>Nama pengguna, email, atau nomor HP<input v-model="form.identifier" class="login-input" name="identifier" required maxlength="254" autocomplete="username" placeholder="Masukkan akun Anda"></label>
                <label>Kata sandi<input v-model="form.password" class="login-input" type="password" name="password" required minlength="10" maxlength="128" autocomplete="current-password" placeholder="Minimal 10 karakter"></label>
                <label class="login-remember-wrap"><input v-model="form.remember" type="checkbox" name="remember"> Ingat saya di perangkat ini (30 hari)</label>
                <p id="account-error" role="alert" style="color:#b3261e">{{ form.errors.identifier || form.errors.password }}</p>
                <button class="btn btn-primary" type="submit" :disabled="form.processing">{{ form.processing ? 'Memproses…' : 'Masuk' }}</button>
            </form>
            <p><Go :href="VIEW_URL.daftar" class="btn">Belum punya akun? Daftar</Go></p>
            <p><Go :href="VIEW_URL.jurusan" class="btn">Jelajahi sebagai tamu</Go></p>
            <small>Kata sandi tidak pernah diisi otomatis oleh aplikasi. Fitur lupa kata sandi melalui email belum tersedia.</small>
        </div>
    </section>
</template>
