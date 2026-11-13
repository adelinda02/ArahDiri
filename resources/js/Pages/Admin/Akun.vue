<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Topline from '../../Components/Admin/Topline.vue';

defineOptions({ layout: AdminLayout });
defineProps({ admins: Array, isOwner: Boolean });
const form = useForm({ identifier: '', role: 'admin' });
const add = () => form.post('/admin/akun', { preserveScroll: true, onSuccess: () => form.reset('identifier') });
const act = (u, action) => (action !== 'cabut' || confirm(`Cabut akses panel admin ${u.username}?`)) && router.post(`/admin/akun/${u.id}`, { action }, { preserveScroll: true });
const ROLE = { owner: 'Pemilik', admin: 'Admin', editor: 'Editor konten' };
</script>

<template>
    <Head title="Admin · Akun admin" />
    <Topline title="Akun admin" desc="Calon admin atau editor mendaftar sebagai pengguna dulu, lalu pemilik memberinya akses. Admin pertama dibuat lewat perintah php artisan arahdiri:make-admin di server." />
    <div class="notice" style="margin-bottom:16px"><b>Admin</b>: semua menu. <b>Editor konten</b> (mis. guru BK): Ringkasan, Simulator, Laporan siswa, Katalog, dan Konten. Editor tidak bisa melihat data hasil, mengekspor, mengubah aturan, akreditasi, atau mengelola akun.</div>
    <form v-if="isOwner" class="toolbar" @submit.prevent="add">
        <input v-model="form.identifier" placeholder="Nama pengguna atau email akun yang sudah terdaftar" aria-label="Akun" required>
        <select v-model="form.role" aria-label="Peran"><option value="admin">Admin</option><option value="editor">Editor konten</option></select>
        <button class="primary" type="submit" :disabled="form.processing">Beri akses</button>
    </form>
    <p v-if="form.errors.identifier" class="error">{{ form.errors.identifier }}</p>
    <div class="table-wrap"><table>
        <thead><tr><th>Akun</th><th>Peran</th><th>Status</th><th>Tindakan</th></tr></thead>
        <tbody><tr v-for="u in admins" :key="u.id">
            <td><strong>{{ u.name }}</strong><small>{{ u.username }} · {{ u.email }}</small></td>
            <td><span class="badge">{{ ROLE[u.role] }}</span></td>
            <td><span class="badge" :class="{ hidden: !u.is_active }">{{ u.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="actions">
                <template v-if="isOwner && u.role !== 'owner'">
                    <button type="button" @click="act(u, u.role === 'admin' ? 'jadi_editor' : 'jadi_admin')">{{ u.role === 'admin' ? 'Jadikan editor' : 'Jadikan admin' }}</button>
                    <button type="button" @click="act(u, u.is_active ? 'nonaktif' : 'aktif')">{{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    <button type="button" class="danger" @click="act(u, 'cabut')">Cabut akses</button>
                </template>
                <span v-else>{{ u.role === 'owner' ? 'Dilindungi' : '-' }}</span>
            </td>
        </tr></tbody>
    </table></div>
</template>
