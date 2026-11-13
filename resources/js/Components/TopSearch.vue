<script setup>
// Kotak pencarian header v14. Saran diambil dari server (/cari/saran) dengan jeda ketik.
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const POPULAR = ['Informatika', 'Kedokteran', 'DKV', 'Teknik Komputer', 'UI', 'ITB', 'Psikologi', 'Software Engineer'];
const q = ref('');
const open = ref(false);
const loading = ref(false);
const res = ref({ programs: [], careers: [], campuses: [] });
const wrap = ref(null);
const input = ref(null);
let timer, ctrl;

function fetchSuggest() {
    clearTimeout(timer);
    const term = q.value.trim();
    if (!term) { res.value = { programs: [], careers: [], campuses: [] }; return; }
    timer = setTimeout(async () => {
        ctrl?.abort();
        ctrl = new AbortController();
        loading.value = true;
        try {
            const r = await fetch('/cari/saran?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json' }, signal: ctrl.signal });
            if (r.ok) res.value = await r.json();
        } catch (e) { /* dibatalkan */ }
        loading.value = false;
    }, 180);
}
function show() { open.value = true; fetchSuggest(); }
function fill(v) { q.value = v; input.value?.focus(); show(); }
function clear() { q.value = ''; input.value?.focus(); show(); }
function submit() {
    const term = q.value.trim();
    if (!term) { input.value?.focus(); show(); return; }
    open.value = false;
    router.visit('/cari?q=' + encodeURIComponent(term));
}
function go(url) { open.value = false; router.visit(url); }
function onDoc(e) { if (wrap.value && !wrap.value.contains(e.target)) open.value = false; }
onMounted(() => document.addEventListener('click', onDoc));
onBeforeUnmount(() => document.removeEventListener('click', onDoc));
const total = () => res.value.programs.length + res.value.careers.length + res.value.campuses.length;
</script>

<template>
    <div ref="wrap" class="top-search-wrap" id="top-search-wrap">
        <form class="search-input-box" id="search-box" role="search" @submit.prevent="submit">
            <button type="button" class="search-btn-icon" id="search-submit-btn" aria-label="Mulai pencarian" title="Klik atau tekan Enter untuk mencari" @click.stop="submit">
                <Icon name="search" />
            </button>
            <input ref="input" v-model="q" type="text" id="top-search" class="top-search-input" placeholder="Cari jurusan, karier, kampus..." autocomplete="off" aria-label="Pencarian cepat"
                @focus="show" @click.stop="show" @input="show" @keydown.esc="open = false; $event.target.blur()">
            <button type="button" class="search-clear-btn" id="search-clear" aria-label="Hapus pencarian" title="Hapus" :style="{ display: q.trim() ? 'flex' : 'none' }" @click.stop="clear">×</button>
        </form>
        <div class="search-dropdown" id="search-dropdown" v-show="open">
            <template v-if="!q.trim()">
                <div class="search-pop-header">Pencarian Populer</div>
                <div class="search-pop-chips">
                    <button v-for="p in POPULAR" :key="p" type="button" class="search-pop-chip" @click.stop="fill(p)">{{ p }}</button>
                </div>
            </template>
            <template v-else-if="!loading && total() === 0">
                <div class="search-empty">Tidak ada saran cepat untuk &ldquo;<b>{{ q.trim() }}</b>&rdquo;. Tekan Enter untuk mencari lebih luas di semua basis data.</div>
                <div style="padding-top:10px;border-top:1px solid var(--surface-2);margin-top:6px">
                    <button type="button" class="btn btn-primary" style="width:100%;font-size:.85rem;padding:9px;justify-content:center" @click="submit">
                        <span> Cari Semua Data untuk &ldquo;{{ q.trim() }}&rdquo; (Enter) →</span>
                    </button>
                </div>
            </template>
            <template v-else>
                <div v-if="res.programs.length" class="search-section">
                    <div class="search-sec-title"> Jurusan Kuliah ({{ res.programs.length }})</div>
                    <button v-for="m in res.programs" :key="m.key" type="button" class="search-item" @click="go(m.url)">
                        <div class="search-item-info"><span class="search-item-title">{{ m.name }}</span><span class="search-item-sub">{{ m.sub }}</span></div>
                        <span class="search-item-tag">{{ m.tag }}</span>
                    </button>
                </div>
                <div v-if="res.careers.length" class="search-section">
                    <div class="search-sec-title"> Karier &amp; Profesi ({{ res.careers.length }})</div>
                    <button v-for="c in res.careers" :key="c.code" type="button" class="search-item" @click="go(c.url)">
                        <div class="search-item-info"><span class="search-item-title">{{ c.name }}</span><span class="search-item-sub">{{ c.sub }}</span></div>
                        <span class="search-item-tag">Profesi</span>
                    </button>
                </div>
                <div v-if="res.campuses.length" class="search-section">
                    <div class="search-sec-title"> Kampus ({{ res.campuses.length }})</div>
                    <button v-for="c in res.campuses" :key="c.code" type="button" class="search-item" @click="go(c.url)">
                        <div class="search-item-info"><span class="search-item-title">{{ c.name }}</span><span class="search-item-sub">{{ c.sub }}</span></div>
                        <span class="search-item-tag">{{ c.tag }}</span>
                    </button>
                </div>
                <div style="padding-top:10px;border-top:1px solid var(--surface-2);margin-top:6px">
                    <button type="button" class="btn btn-primary" style="width:100%;padding:10px 14px;border-radius:10px;font-size:.85rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px" @click="submit">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
                        <span>Lihat Semua Hasil Pencarian untuk &ldquo;{{ q.trim() }}&rdquo; (Tekan Enter) →</span>
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>
