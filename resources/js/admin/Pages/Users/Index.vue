<script setup>
import { ref, watch, reactive, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import Layout from '../Layout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

defineOptions({ layout: Layout });

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    users: Object,
    countries: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();

/* ── Onglets ── */
const tabs = [
    { key: 'staff', label: 'Mon Equipe', icon: 'shield' },
    { key: 'vendor', label: 'Vendeurs', icon: 'storefront' },
    { key: 'influencer', label: 'Createurs de Contenu', icon: 'megaphone' },
];

const activeTab = ref(props.filters.tab ?? 'vendor');
const search = ref(props.filters.search ?? '');
const country = ref(props.filters.country ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(route('admin.users.index'), {
        tab: activeTab.value || undefined,
        search: search.value || undefined,
        country: country.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
}

let debounce;
watch([search], () => {
    clearTimeout(debounce);
    debounce = setTimeout(applyFilters, 350);
});

watch([activeTab, country, status], () => applyFilters());

function switchTab(key) {
    activeTab.value = key;
    search.value = '';
    country.value = '';
    status.value = '';
}

/* ── Formatage ── */
function formatCurrency(value) {
    if (value === null || value === undefined) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0 }).format(value) + ' FCFA';
}

function formatDate(d) {
    if (!d) return '\u2014';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function initials(name) {
    if (!name) return '?';
    return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
}

function whatsappLink(phone) {
    if (!phone) return null;
    const clean = phone.replace(/[^0-9+]/g, '');
    return 'https://wa.me/' + clean.replace('+', '');
}

/* ── Badges ── */
function kycBadge(st) {
    const m = {
        approved: { label: 'Approuve', cls: 'bg-emerald-100 text-emerald-700' },
        pending: { label: 'En attente', cls: 'bg-amber-100 text-amber-700' },
        rejected: { label: 'Rejete', cls: 'bg-red-100 text-red-700' },
        not_submitted: { label: 'Non soumis', cls: 'bg-slate-100 text-slate-500' },
    };
    return m[st] ?? m.not_submitted;
}

function roleBadge(r) {
    const map = {
        admin: { label: 'Admin', cls: 'bg-red-100 text-red-700' },
        vendor: { label: 'Vendeur', cls: 'bg-teal-100 text-teal-700' },
        influencer: { label: 'Createur de Contenu', cls: 'bg-purple-100 text-purple-700' },
    };
    return map[r] ?? { label: r, cls: 'bg-slate-100 text-slate-600' };
}

function tierLabel(t) {
    const m = { bronze: 'Bronze', argent: 'Argent', or: 'Or' };
    return m[t] ?? '\u2014';
}

function permissionLabel(p) {
    const m = {
        super_admin: 'Super Admin',
        manage_users: 'Utilisateurs',
        manage_finance: 'Finance',
        manage_kyc: 'KYC',
        manage_disputes: 'Litiges',
        manage_campaigns: 'Campagnes',
        manage_settings: 'Parametres',
    };
    return m[p] ?? p;
}

/* ── Actions dropdown ── */
const openDropdown = ref(null);

function toggleDropdown(id) {
    openDropdown.value = openDropdown.value === id ? null : id;
}

function closeDropdowns() {
    openDropdown.value = null;
}

function toggleBan(user) {
    closeDropdowns();
    router.patch(route('admin.users.toggle-ban', user.id), {}, { preserveScroll: true });
}

function impersonate(user) {
    closeDropdowns();
    router.post(route('admin.impersonate.start', user.id));
}

async function deleteAdmin(user) {
    closeDropdowns();
    if (await ask({ title: 'Supprimer l\'administrateur', message: `Supprimer l'administrateur ${user.name} ?`, variant: 'danger', confirmLabel: 'Supprimer' })) {
        router.delete(route('admin.users.destroy-admin', user.id), { preserveScroll: true });
    }
}

/* ── Modal creation admin ── */
const showCreateAdmin = ref(false);
const createAdminForm = reactive({
    name: '',
    email: '',
    password: '',
    permissions: [],
});
const createAdminErrors = ref({});

const allPermissions = [
    { key: 'super_admin', label: 'Super Admin (acces total)' },
    { key: 'manage_users', label: 'Gestion des utilisateurs' },
    { key: 'manage_finance', label: 'Finance & Retraits' },
    { key: 'manage_kyc', label: 'Verification KYC & VIP' },
    { key: 'manage_disputes', label: 'Gestion des litiges' },
    { key: 'manage_campaigns', label: 'Campagnes systeme' },
    { key: 'manage_settings', label: 'Parametres globaux' },
];

function openCreateAdmin() {
    createAdminForm.name = '';
    createAdminForm.email = '';
    createAdminForm.password = '';
    createAdminForm.permissions = [];
    createAdminErrors.value = {};
    showCreateAdmin.value = true;
}

function submitCreateAdmin() {
    router.post(route('admin.users.store-admin'), {
        name: createAdminForm.name,
        email: createAdminForm.email,
        password: createAdminForm.password,
        permissions: createAdminForm.permissions,
    }, {
        preserveScroll: true,
        onSuccess: () => { showCreateAdmin.value = false; },
        onError: (errors) => { createAdminErrors.value = errors; },
    });
}

/* ── Modal audit social ── */
const showAudit = ref(false);
const auditLoading = ref(false);
const auditSaving = ref(false);
const auditUser = ref(null);

const auditForm = reactive({
    tiktok_followers: 0,
    instagram_followers: 0,
    facebook_followers: 0,
    youtube_followers: 0,
    snapchat_followers: 0,
    tier: 'bronze',
});

function openAudit(user) {
    closeDropdowns();
    showAudit.value = true;
    auditUser.value = user;
    auditForm.tiktok_followers = user.tiktok_followers ?? 0;
    auditForm.instagram_followers = user.instagram_followers ?? 0;
    auditForm.facebook_followers = user.facebook_followers ?? 0;
    auditForm.youtube_followers = user.youtube_followers ?? 0;
    auditForm.snapchat_followers = user.snapchat_followers ?? 0;
    auditForm.tier = user.tier ?? 'bronze';
    auditLoading.value = false;
}

function closeAudit() {
    showAudit.value = false;
    auditUser.value = null;
}

function saveAudit() {
    if (!auditUser.value) return;
    auditSaving.value = true;
    router.patch(route('admin.users.update-socials', auditUser.value.id), {
        tiktok_followers: auditForm.tiktok_followers,
        instagram_followers: auditForm.instagram_followers,
        facebook_followers: auditForm.facebook_followers,
        youtube_followers: auditForm.youtube_followers,
        snapchat_followers: auditForm.snapchat_followers,
        tier: auditForm.tier,
    }, {
        preserveScroll: true,
        onSuccess: () => { auditSaving.value = false; closeAudit(); },
        onError: () => { auditSaving.value = false; },
    });
}

function approveKyc() {
    if (!auditUser.value) return;
    router.patch(route('admin.kyc.approve', auditUser.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { if (auditUser.value) auditUser.value.kyc_status = 'approved'; },
    });
}

function rejectKyc() {
    if (!auditUser.value) return;
    router.patch(route('admin.kyc.reject', auditUser.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { if (auditUser.value) auditUser.value.kyc_status = 'rejected'; },
    });
}

const platforms = [
    { key: 'tiktok', label: 'TikTok', field: 'tiktok_followers', url: 'tiktok_url' },
    { key: 'instagram', label: 'Instagram', field: 'instagram_followers', url: 'instagram_url' },
    { key: 'facebook', label: 'Facebook', field: 'facebook_followers', url: 'facebook_url' },
    { key: 'youtube', label: 'YouTube', field: 'youtube_followers', url: 'youtube_url' },
    { key: 'snapchat', label: 'Snapchat', field: 'snapchat_followers', url: 'snapchat_url' },
];
</script>

<template>
    <div class="space-y-6" @click="closeDropdowns">

        <!-- Flash success -->
        <div v-if="page.props.flash?.success" class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            <span>{{ page.props.flash.success }}</span>
        </div>

        <!-- Flash error -->
        <div v-if="page.props.flash?.error" class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
            <span>{{ page.props.flash.error }}</span>
        </div>

        <!-- ═══════ En-tete ═══════ -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Utilisateurs</h1>
                <p class="mt-1 text-sm text-slate-500">CRM -- Gestion des comptes MANTOTA.</p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    :href="route('admin.users.export')"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    Exporter CSV
                </a>
                <button
                    v-if="activeTab === 'staff'"
                    @click="openCreateAdmin"
                    class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Nouvel Admin
                </button>
            </div>
        </div>

        <!-- ═══════ Onglets ═══════ -->
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6" aria-label="Onglets">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="switchTab(tab.key)"
                    class="relative flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition"
                    :class="activeTab === tab.key
                        ? 'border-teal-600 text-teal-600'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                >
                    <svg v-if="tab.icon === 'shield'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                    <svg v-else-if="tab.icon === 'storefront'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" /></svg>
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- ═══════ Filtres ═══════ -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="activeTab === 'staff' ? 'Rechercher par nom ou email...' : 'Rechercher par nom, email ou telephone...'"
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
            </div>

            <select
                v-if="activeTab !== 'staff'"
                v-model="country"
                class="rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
                <option value="">Tous les pays</option>
                <option v-for="c in countries" :key="c" :value="c">{{ c }}</option>
            </select>

            <select
                v-if="activeTab !== 'staff'"
                v-model="status"
                class="rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
                <option value="">Tous les statuts</option>
                <option value="active">Actif</option>
                <option value="banned">Banni</option>
                <option value="vip">VIP</option>
            </select>

            <span class="ml-auto text-sm text-slate-500">{{ users.total }} resultat(s)</span>
        </div>

        <!-- ══════════════════════════════════════════════════ -->
        <!--  ONGLET 1 : MON EQUIPE (Administrateurs)         -->
        <!-- ══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'staff'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div v-if="users.data.length === 0" class="px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                <p class="mt-3 text-sm text-slate-500">Aucun administrateur dans l'equipe.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Administrateur</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Permissions</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Date d'ajout</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users.data" :key="u.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-700">
                                        {{ initials(u.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900 truncate">{{ u.name }}</p>
                                        <p class="text-xs text-slate-400 truncate">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="p in (u.admin_permissions || [])"
                                        :key="p"
                                        class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                        :class="p === 'super_admin' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'"
                                    >{{ permissionLabel(p) }}</span>
                                    <span v-if="!u.admin_permissions || u.admin_permissions.length === 0" class="text-xs text-slate-400">&mdash;</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(u.created_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="relative inline-block">
                                    <button
                                        @click.stop="toggleDropdown(u.id)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                    >
                                        Actions
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </button>
                                    <Transition
                                        enter-active-class="transition duration-100 ease-out"
                                        enter-from-class="opacity-0 scale-95"
                                        enter-to-class="opacity-100 scale-100"
                                        leave-active-class="transition duration-75 ease-in"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div v-if="openDropdown === u.id" class="absolute right-0 z-20 mt-1 w-44 origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                            <button @click="deleteAdmin(u)" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-red-700 transition hover:bg-red-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                Supprimer
                                            </button>
                                        </div>
                                    </Transition>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════ -->
        <!--  ONGLET 2 : VENDEURS                              -->
        <!-- ══════════════════════════════════════════════════ -->
        <div v-else-if="activeTab === 'vendor'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div v-if="users.data.length === 0" class="px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z" /></svg>
                <p class="mt-3 text-sm text-slate-500">Aucun vendeur trouve.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Vendeur</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Pays</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">KYC</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Balance</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Inscription</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users.data" :key="u.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">
                                        {{ initials(u.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-slate-900 truncate">{{ u.name }}</p>
                                            <a v-if="whatsappLink(u.phone)" :href="whatsappLink(u.phone)" target="_blank" rel="noopener noreferrer" class="shrink-0 text-emerald-500 transition hover:text-emerald-700" title="WhatsApp">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            </a>
                                        </div>
                                        <p class="text-xs text-slate-400 truncate">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                <span v-if="u.country" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3" /></svg>
                                    {{ u.country }}
                                </span>
                                <span v-else class="text-xs text-slate-400">&mdash;</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="kycBadge(u.kyc_status).cls">{{ kycBadge(u.kyc_status).label }}</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(u.wallet_balance) }}</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span v-if="u.deleted_at" class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    Supprime
                                </span>
                                <span v-else-if="u.is_banned" class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                    Banni
                                </span>
                                <span v-else class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Actif</span>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(u.created_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="relative inline-block">
                                    <button @click.stop="toggleDropdown(u.id)" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                        Actions
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </button>
                                    <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-75 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                        <div v-if="openDropdown === u.id" class="absolute right-0 z-20 mt-1 w-44 origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                            <Link :href="route('admin.users.show', u.id)" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                Dossier 360
                                            </Link>
                                            <button @click="impersonate(u)" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                Mode Ghost
                                            </button>
                                            <button @click="toggleBan(u)" class="flex w-full items-center gap-2 border-t border-slate-100 px-3 py-2 text-xs transition hover:bg-slate-50" :class="u.is_banned ? 'text-emerald-700' : 'text-red-700'">
                                                <svg v-if="u.is_banned" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                {{ u.is_banned ? 'Debannir' : 'Bannir' }}
                                            </button>
                                        </div>
                                    </Transition>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════ -->
        <!--  ONGLET 3 : INFLUENCEURS                          -->
        <!-- ══════════════════════════════════════════════════ -->
        <div v-else class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div v-if="users.data.length === 0" class="px-6 py-16 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59" /></svg>
                <p class="mt-3 text-sm text-slate-500">Aucun createur de contenu trouve.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-left">
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Createur de Contenu</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Pays</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">KYC</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Palier</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Balance</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Inscription</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users.data" :key="u.id" class="transition hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-purple-700">
                                        {{ initials(u.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-slate-900 truncate">{{ u.name }}</p>
                                            <a v-if="whatsappLink(u.phone)" :href="whatsappLink(u.phone)" target="_blank" rel="noopener noreferrer" class="shrink-0 text-emerald-500 transition hover:text-emerald-700" title="WhatsApp">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            </a>
                                        </div>
                                        <p class="text-xs text-slate-400 truncate">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                <span v-if="u.country" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3" /></svg>
                                    {{ u.country }}
                                </span>
                                <span v-else class="text-xs text-slate-400">&mdash;</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="kycBadge(u.kyc_status).cls">{{ kycBadge(u.kyc_status).label }}</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span v-if="u.tier" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="{ 'bg-amber-100 text-amber-700': u.tier === 'bronze', 'bg-slate-200 text-slate-700': u.tier === 'argent', 'bg-yellow-100 text-yellow-700': u.tier === 'or' }">{{ tierLabel(u.tier) }}</span>
                                <span v-else class="text-xs text-slate-400">&mdash;</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(u.wallet_balance) }}</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span v-if="u.deleted_at" class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    Supprime
                                </span>
                                <span v-else-if="u.is_banned" class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                    Banni
                                </span>
                                <span v-else class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Actif</span>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">{{ formatDate(u.created_at) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="relative inline-block">
                                    <button @click.stop="toggleDropdown(u.id)" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                        Actions
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </button>
                                    <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-75 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                        <div v-if="openDropdown === u.id" class="absolute right-0 z-20 mt-1 w-44 origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                            <Link :href="route('admin.users.show', u.id)" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                Dossier 360
                                            </Link>
                                            <button @click="openAudit(u)" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                Audit Social
                                            </button>
                                            <button @click="impersonate(u)" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-slate-700 transition hover:bg-slate-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                Mode Ghost
                                            </button>
                                            <button @click="toggleBan(u)" class="flex w-full items-center gap-2 border-t border-slate-100 px-3 py-2 text-xs transition hover:bg-slate-50" :class="u.is_banned ? 'text-emerald-700' : 'text-red-700'">
                                                <svg v-if="u.is_banned" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                {{ u.is_banned ? 'Debannir' : 'Bannir' }}
                                            </button>
                                        </div>
                                    </Transition>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════ Pagination (commune) ═══════ -->
        <div v-if="users.last_page > 1" class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-white px-6 py-3 shadow-sm">
            <p class="text-xs text-slate-500">{{ users.from }}-{{ users.to }} sur {{ users.total }}</p>
            <div class="flex gap-1">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="link.active
                        ? 'bg-teal-600 text-white'
                        : link.url
                            ? 'text-slate-600 hover:bg-slate-100'
                            : 'pointer-events-none text-slate-300'"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════ -->
        <!--  MODAL : Creer un Administrateur                  -->
        <!-- ══════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showCreateAdmin" class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4 pt-16" @click.self="showCreateAdmin = false">
                    <div class="relative w-full max-w-lg rounded-2xl border border-slate-200/80 bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Nouvel Administrateur</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Definir les acces et permissions.</p>
                            </div>
                            <button @click="showCreateAdmin = false" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Nom complet</label>
                                <input v-model="createAdminForm.name" type="text" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                <p v-if="createAdminErrors.name" class="mt-1 text-xs text-red-600">{{ createAdminErrors.name }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Adresse email</label>
                                <input v-model="createAdminForm.email" type="email" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                <p v-if="createAdminErrors.email" class="mt-1 text-xs text-red-600">{{ createAdminErrors.email }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Mot de passe</label>
                                <input v-model="createAdminForm.password" type="password" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                <p v-if="createAdminErrors.password" class="mt-1 text-xs text-red-600">{{ createAdminErrors.password }}</p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Permissions</label>
                                <div class="space-y-2">
                                    <label v-for="perm in allPermissions" :key="perm.key" class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50 cursor-pointer" :class="createAdminForm.permissions.includes(perm.key) ? 'border-teal-300 bg-teal-50/50' : ''">
                                        <input type="checkbox" :value="perm.key" v-model="createAdminForm.permissions" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                                        <span class="text-sm text-slate-700">{{ perm.label }}</span>
                                    </label>
                                </div>
                                <p v-if="createAdminErrors.permissions" class="mt-1 text-xs text-red-600">{{ createAdminErrors.permissions }}</p>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 px-6 py-4 flex justify-end gap-3">
                            <button @click="showCreateAdmin = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Annuler</button>
                            <button @click="submitCreateAdmin" class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700">Creer l'Admin</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ══════════════════════════════════════════════════ -->
        <!--  MODAL : Audit Profil (KYC & Social)              -->
        <!-- ══════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showAudit" class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4 pt-16" @click.self="closeAudit">
                    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="translate-y-4 opacity-0 scale-95" enter-to-class="translate-y-0 opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="translate-y-0 opacity-100 scale-100" leave-to-class="translate-y-4 opacity-0 scale-95" appear>
                        <div v-if="showAudit" class="relative w-full max-w-4xl rounded-2xl border border-slate-200/80 bg-white shadow-2xl">

                            <div v-if="auditLoading" class="flex items-center justify-center py-32">
                                <svg class="h-8 w-8 animate-spin text-teal-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            </div>

                            <template v-else-if="auditUser">
                                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                                    <div>
                                        <h2 class="text-lg font-bold text-slate-900">Audit Profil (KYC & Social)</h2>
                                        <p class="mt-0.5 text-sm text-slate-500">{{ auditUser.name }} -- {{ auditUser.email }}</p>
                                    </div>
                                    <button @click="closeAudit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
                                    <!-- Gauche : Identite & KYC -->
                                    <div class="p-6 space-y-5">
                                        <h3 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-slate-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                                            Identite (KYC)
                                        </h3>
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div><p class="text-xs text-slate-400">Role</p><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="roleBadge(auditUser.role).cls">{{ roleBadge(auditUser.role).label }}</span></div>
                                            <div><p class="text-xs text-slate-400">Pays</p><p class="font-medium text-slate-900">{{ auditUser.country ?? '\u2014' }}</p></div>
                                            <div><p class="text-xs text-slate-400">Telephone</p><p class="font-medium text-slate-900">{{ auditUser.phone ?? '\u2014' }}</p></div>
                                            <div><p class="text-xs text-slate-400">VIP</p><span v-if="auditUser.is_vip" class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700">VIP</span><span v-else class="text-xs text-slate-400">Non</span></div>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-slate-700">Statut KYC</span>
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="kycBadge(auditUser.kyc_status).cls">{{ kycBadge(auditUser.kyc_status).label }}</span>
                                            </div>
                                            <div v-if="auditUser.kyc_status === 'pending'" class="flex gap-2">
                                                <button @click="approveKyc" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                    Approuver KYC
                                                </button>
                                                <button @click="rejectKyc" class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    Rejeter KYC
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-3">
                                            <p class="text-xs font-medium text-slate-500">Documents d'identite</p>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <p class="mb-1 text-[10px] uppercase tracking-wider text-slate-400">Recto</p>
                                                    <div v-if="auditUser.kyc_document_front" class="overflow-hidden rounded-xl border border-slate-200"><img :src="auditUser.kyc_document_front" alt="Document KYC Recto" class="h-40 w-full object-cover" /></div>
                                                    <div v-else class="flex h-40 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50"><p class="text-xs text-slate-400">Non soumis</p></div>
                                                </div>
                                                <div>
                                                    <p class="mb-1 text-[10px] uppercase tracking-wider text-slate-400">Verso</p>
                                                    <div v-if="auditUser.kyc_document_back" class="overflow-hidden rounded-xl border border-slate-200"><img :src="auditUser.kyc_document_back" alt="Document KYC Verso" class="h-40 w-full object-cover" /></div>
                                                    <div v-else class="flex h-40 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50"><p class="text-xs text-slate-400">Non soumis</p></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Droite : Social & Tier -->
                                    <div class="p-6 space-y-5">
                                        <h3 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-slate-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535" /></svg>
                                            Influence & Reseaux Sociaux
                                        </h3>
                                        <div class="space-y-3">
                                            <div v-for="p in platforms" :key="p.key" class="rounded-xl border border-slate-200 bg-white p-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-sm font-semibold text-slate-700 w-24 shrink-0">{{ p.label }}</span>
                                                    <div class="flex-1 min-w-0">
                                                        <a v-if="auditUser[p.url]" :href="auditUser[p.url]" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-medium text-teal-600 transition hover:text-teal-800 truncate max-w-full">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                                            <span class="truncate">{{ auditUser[p.url] }}</span>
                                                        </a>
                                                        <span v-else class="text-xs text-slate-400">Aucun lien soumis</span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex items-center gap-2">
                                                    <label class="text-[10px] uppercase tracking-wider text-slate-400 shrink-0 w-24">Abonnes</label>
                                                    <input v-model.number="auditForm[p.field]" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-4 space-y-3">
                                            <label class="flex items-center gap-2 text-sm font-semibold text-purple-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                                                Palier MANTOTA (Tier)
                                            </label>
                                            <select v-model="auditForm.tier" class="w-full rounded-xl border-purple-300 bg-white text-sm font-medium text-purple-900 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                                <option value="bronze">Bronze -- Nano (1k - 10k)</option>
                                                <option value="argent">Argent -- Micro (10k - 100k)</option>
                                                <option value="or">Or -- Macro / Star (100k+)</option>
                                            </select>
                                        </div>
                                        <button @click="saveAudit" :disabled="auditSaving" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                            {{ auditSaving ? 'Enregistrement...' : "Valider l'Audit Social" }}
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </div>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
