<script setup>
// Jendela detail katalog v14 (dialog#star-modal).
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({ open: Boolean, title: String });
const emit = defineEmits(['close']);
const el = ref(null);
function sync() {
    if (!el.value) return;
    if (props.open && !el.value.open) { el.value.showModal(); el.value.scrollTop = 0; }
    if (!props.open && el.value.open) el.value.close();
}
watch(() => [props.open, props.title], () => setTimeout(sync));
onMounted(sync);
onBeforeUnmount(() => el.value?.open && el.value.close());
</script>

<template>
    <dialog ref="el" id="star-modal" class="star-modal" aria-labelledby="star-dialog-title" @close="emit('close')" @click.self="emit('close')">
        <div class="star-modal-head"><h2 id="star-dialog-title">{{ title }}</h2><button type="button" class="star-close" aria-label="Tutup" @click="emit('close')">×</button></div>
        <div><slot /></div>
    </dialog>
</template>
