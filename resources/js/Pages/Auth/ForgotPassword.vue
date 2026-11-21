<script setup>
// Lupa kata sandi: 1) email → 2) kode 6 digit dari email → 3) kata sandi baru.
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Go from '../../Components/Go.vue';
import PasswordInput from '../../Components/PasswordInput.vue';
import { VIEW_URL } from '../../lib/routes';

const props = defineProps({
    step: { type: String, required: true }, // 'email' | 'code' | 'password'
    email: { type: String, default: '' },
    resend_in: { type: Number, default: 0 },
    ttl: { type: Number, default: 10 },
});

const STEPS = [
    { key: 'email', label: 'Email' },
    { key: 'code', label: 'Kode' },
    { key: 'password', label: 'Sandi baru' },
];
const current = computed(() => STEPS.findIndex((s) => s.key === props.step));
const flashError = computed(() => usePage().props.flash?.error);

// Langkah 1 & kirim ulang
const emailForm = useForm({ email: props.step === 'email' ? props.email : '' });
const sendCode = () => emailForm.post('/lupa-sandi', { preserveScroll: true });

// Langkah 2
const codeForm = useForm({ code: '' });
const onCodeInput = (e) => { codeForm.code = e.target.value.replace(/\D/g, '').slice(0, 6); };
const verify = () => codeForm.post('/lupa-sandi/kode', { preserveScroll: true, onError: () => codeForm.reset('code') });

// Hitung mundur tombol "Kirim ulang"
const wait = ref(props.resend_in);
let timer = null;
onMounted(() => { timer = setInterval(() => { if (wait.value > 0) wait.value--; }, 1000); });
onBeforeUnmount(() => clearInterval(timer));
const resendForm = useForm({ resend: true });
const resend = () => resendForm.post('/lupa-sandi', { preserveScroll: true, onSuccess: () => { wait.value = 60; codeForm.reset(); } });

// Langkah 3
const pwForm = useForm({ password: '', password_confirmation: '' });
const savePassword = () => pwForm.post('/lupa-sandi/baru', { onFinish: () => pwForm.reset('password', 'password_confirmation') });

const ICON = {
    mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    shield: '<path d="M12 3 5 6v5c0 4.5 3 8.3 7 9.5 4-1.2 7-5 7-9.5V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
    key: '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 9.2-9.2M17 6l3 3M15 8l2 2"/>',
};
const heroIcon = computed(() => ICON[{ email: 'mail', code: 'shield', password: 'key' }[props.step]]);
</script>

<template>
    <Head title="Lupa kata sandi" />
    <section class="login-wrap">
        <div class="login-card fp-card">
            <ol class="fp-steps" aria-label="Langkah reset kata sandi">
                <li v-for="(s, i) in STEPS" :key="s.key" :class="{ 'is-done': i < current, 'is-active': i === current }" :aria-current="i === current ? 'step' : null">
                    <span class="fp-step-dot">
                        <svg v-if="i < current" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                        <template v-else>{{ i + 1 }}</template>
                    </span>
                    <span class="fp-step-label">{{ s.label }}</span>
                </li>
            </ol>

            <span class="fp-hero" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" v-html="heroIcon"></svg>
            </span>

            <p v-if="flashError" class="fp-alert" role="alert">{{ flashError }}</p>

            <!-- Langkah 1: email -->
            <template v-if="step === 'email'">
                <h1>Lupa kata sandi?</h1>
                <p class="fp-lead">Masukkan email akunmu. Kami akan mengirim kode 6 digit untuk membuat kata sandi baru.</p>
                <form class="login-form" novalidate @submit.prevent="sendCode">
                    <label>Email
                        <input v-model="emailForm.email" class="login-input fp-input" type="email" name="email" required maxlength="254" autocomplete="email" inputmode="email" placeholder="nama@email.com" autofocus>
                        <span v-if="emailForm.errors.email" class="field-error">{{ emailForm.errors.email }}</span>
                    </label>
                    <button class="btn btn-primary fp-submit" type="submit" :disabled="emailForm.processing">{{ emailForm.processing ? 'Mengirim…' : 'Kirim kode' }}</button>
                </form>
            </template>

            <!-- Langkah 2: kode -->
            <template v-else-if="step === 'code'">
                <h1>Masukkan kode verifikasi</h1>
                <p class="fp-lead">Kode 6 digit dikirim ke <b>{{ email }}</b> jika email itu terdaftar. Kode berlaku {{ ttl }} menit. Cek juga folder spam.</p>
                <form class="login-form" novalidate @submit.prevent="verify">
                    <label>Kode verifikasi
                        <input :value="codeForm.code" class="login-input fp-code" type="text" name="code" required inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6" placeholder="••••••" autofocus @input="onCodeInput">
                        <span v-if="codeForm.errors.code" class="field-error">{{ codeForm.errors.code }}</span>
                    </label>
                    <button class="btn btn-primary fp-submit" type="submit" :disabled="codeForm.processing || codeForm.code.length !== 6">{{ codeForm.processing ? 'Memeriksa…' : 'Verifikasi kode' }}</button>
                </form>
                <p class="fp-resend">
                    Belum menerima kode?
                    <button type="button" class="login-link-btn" :disabled="wait > 0 || resendForm.processing" @click="resend">{{ wait > 0 ? `Kirim ulang dalam ${wait} dtk` : 'Kirim ulang kode' }}</button>
                </p>
                <p class="fp-alt"><Go href="/lupa-sandi" class="login-link-btn">Ganti email</Go></p>
            </template>

            <!-- Langkah 3: kata sandi baru -->
            <template v-else>
                <h1>Buat kata sandi baru</h1>
                <p class="fp-lead">Untuk akun <b>{{ email }}</b>. Gunakan minimal 10 karakter yang belum pernah kamu pakai. Setelah disimpan, semua perangkat lain otomatis keluar.</p>
                <form class="login-form" novalidate @submit.prevent="savePassword">
                    <label>Kata sandi baru<PasswordInput v-model="pwForm.password" name="password" required minlength="10" maxlength="128" autocomplete="new-password" placeholder="Minimal 10 karakter" autofocus />
                        <span v-if="pwForm.errors.password" class="field-error">{{ pwForm.errors.password }}</span></label>
                    <label>Konfirmasi kata sandi baru<PasswordInput v-model="pwForm.password_confirmation" name="password_confirmation" required minlength="10" maxlength="128" autocomplete="new-password" placeholder="Ketik ulang kata sandi baru" /></label>
                    <button class="btn btn-primary fp-submit" type="submit" :disabled="pwForm.processing">{{ pwForm.processing ? 'Menyimpan…' : 'Simpan kata sandi' }}</button>
                </form>
            </template>

            <p class="fp-back"><Go :href="VIEW_URL.login" class="login-link-btn">← Kembali ke halaman masuk</Go></p>
            <small class="fp-note">Tidak bisa mengakses email akunmu? Hubungi admin ArahDiri untuk meminta kata sandi sementara.</small>
        </div>
    </section>
</template>
