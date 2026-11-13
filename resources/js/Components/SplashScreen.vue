<script setup>
// Layar pembuka v14: progres 0→100% (±1,4 detik), bisa dilewati dengan klik.
import { ref, onMounted, onBeforeUnmount } from 'vue';

const progress = ref(0);
const status = ref('Menyiapkan kompas masa depan...');
const hidden = ref(false);
const gone = ref(false);
let timer;

function dismiss() {
    clearInterval(timer);
    if (hidden.value) return;
    hidden.value = true;
    setTimeout(() => (gone.value = true), 450);
}
onMounted(() => {
    timer = setInterval(() => {
        progress.value += 10;
        if (progress.value >= 30 && progress.value < 70) status.value = 'Memuat basis data jurusan dan kampus...';
        else if (progress.value >= 70) status.value = 'Menghubungkan kompas RIASEC...';
        if (progress.value >= 100) { clearInterval(timer); setTimeout(dismiss, 260); }
    }, 110);
});
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <div v-if="!gone" id="splash-screen" class="splash-screen" :class="{ 'splash-hidden': hidden }" role="dialog" aria-modal="true" aria-label="Layar Pembuka ArahDiri" @click="dismiss">
        <div class="splash-inner">
            <div class="splash-compass-ring"><img src="/brand-logo.png" alt="Logo ArahDiri" style="width:100%;border-radius:50%"></div>
            <div class="splash-brand-block">
                <h1 class="splash-title">ArahDiri</h1>
                <p class="splash-tagline">"Masa Depanmu, Pilihanmu, <em>Arahmu!</em>"</p>
                <span class="splash-badge">PANDUAN PENJURUSAN &amp; KARIER MASA DEPAN</span>
            </div>
            <div class="splash-loader-wrap">
                <div class="splash-progress-track"><div class="splash-progress-fill" id="splash-prog-bar" :style="{ width: Math.min(progress, 100) + '%' }"></div></div>
                <p class="splash-loading-text" id="splash-status-text">{{ status }}</p>
            </div>
        </div>
        <button type="button" class="splash-skip-btn" title="Lewati layar pembuka" @click.stop="dismiss">Lewati &#8250;</button>
    </div>
</template>
