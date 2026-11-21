<script setup>
// Input kata sandi dengan tombol mata untuk menampilkan/menyembunyikan isinya.
// Atribut lain (name, autocomplete, required, minlength, placeholder, id, dll.) diteruskan ke <input>.
import { ref } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: String, default: '' });
const visible = ref(false);
</script>

<template>
    <span class="login-input-wrap">
        <input v-bind="$attrs" v-model="model" :type="visible ? 'text' : 'password'" class="login-input has-eye">
        <button
            type="button"
            class="login-eye-btn"
            :aria-label="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
            :title="visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
            :aria-pressed="visible ? 'true' : 'false'"
            @click="visible = !visible"
        >
            <!-- eye-off: kata sandi sedang terlihat -->
            <svg v-if="visible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M10.7 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-2.2 3.1" />
                <path d="M6.6 6.6A17.4 17.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6" />
                <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                <path d="m2 2 20 20" />
            </svg>
            <!-- eye: kata sandi sedang disembunyikan -->
            <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        </button>
    </span>
</template>
