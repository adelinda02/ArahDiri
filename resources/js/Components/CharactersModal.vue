<script setup>
// Modal "6 Karakter Tipe Minat RIASEC" v14.
import { onMounted, onBeforeUnmount, ref } from 'vue';
import { ORDER, KARTU } from '../lib/riasec';

const props = defineProps({ norm: { type: Object, required: true }, top3: { type: Array, required: true } });
const emit = defineEmits(['close']);
const open = ref(false);
const pct = (t) => Math.round((props.norm[t] ?? 0.5) * 100);
function close() { open.value = false; setTimeout(() => emit('close'), 200); }
const onKey = (e) => e.key === 'Escape' && close();
onMounted(() => { setTimeout(() => (open.value = true), 10); window.addEventListener('keydown', onKey); });
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div class="career-modal-backdrop" :class="{ open }" id="characters-modal" role="dialog" aria-modal="true" aria-labelledby="char-modal-title" style="display:flex" @click.self="close">
            <div class="career-modal-dialog" style="max-width: 980px;">
                <button type="button" class="career-modal-close" aria-label="Tutup jendela karakter" @click="close">✕</button>
                <div id="characters-modal-content">
                    <div style="padding: clamp(16px, 2.5vw, 24px);">
                        <div style="margin-bottom: 20px; text-align: center;">
                            <h2 id="char-modal-title" style="margin: 0; font-size: 1.6rem; font-weight: 800; color: #0B3C5D;">6 Karakter Tipe Minat RIASEC</h2>
                            <p style="margin: 6px 0 0 0; color: #1D70B8; font-size: 0.95rem;">Pelajari seluruh karakter kepribadian untuk memahami potensi unik dan gaya eksplorasimu.</p>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                            <div v-for="t in ORDER" :key="t" class="bakat-card-item" :class="{ 'is-top-bakat': top3.includes(t) }">
                                <div class="bakat-card-top">
                                    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:8px">
                                        <div class="bakat-badge-pill"><span>{{ KARTU[t].label }}</span></div>
                                        <div style="display:flex;align-items:center;gap:6px">
                                            <span v-if="top3.includes(t)" style="background:#1D70B8;color:#ffffff;font-size:0.75rem;font-weight:750;padding:4px 10px;border-radius:999px">Bakat #{{ top3.indexOf(t) + 1 }}</span>
                                            <span class="bakat-pct-badge" style="padding:4px 10px;font-size:0.82rem">{{ pct(t) }}%</span>
                                        </div>
                                    </div>
                                    <div class="bakat-card-img-wrap"><img :src="KARTU[t].img" :alt="KARTU[t].name" class="bakat-card-img"></div>
                                </div>
                                <div class="bakat-card-bottom">{{ KARTU[t].desc }}</div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: center; margin-top: 24px;">
                            <button type="button" class="btn btn-primary" style="padding: 10px 28px; border-radius: 12px; font-weight: 750;" @click="close">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
