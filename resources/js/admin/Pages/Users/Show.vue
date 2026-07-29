<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Layout from '../Layout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

defineOptions({ layout: Layout });

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    profileUser: Object,
    kycLogs: { type: Array, default: () => [] },
    withdrawals: { type: Array, default: () => [] },
    orderDisputes: { type: Array, default: () => [] },
    serviceDisputes: { type: Array, default: () => [] },
});

const activeTab = ref('kyc');

const tabs = [
    { key: 'kyc', label: 'Historique KYC' },
    { key: 'finances', label: 'Finances' },
    { key: 'litiges', label: 'Litiges' },
    { key: 'audit', label: 'Audit Social' },
];

/* ── Audit Social ── */
const auditSaving = ref(false);
const socialPlatforms = [
    { key: 'tiktok', label: 'TikTok', field: 'tiktok_followers', url: 'tiktok_url' },
    { key: 'instagram', label: 'Instagram', field: 'instagram_followers', url: 'instagram_url' },
    { key: 'facebook', label: 'Facebook', field: 'facebook_followers', url: 'facebook_url' },
    { key: 'youtube', label: 'YouTube', field: 'youtube_followers', url: 'youtube_url' },
    { key: 'snapchat', label: 'Snapchat', field: 'snapchat_followers', url: 'snapchat_url' },
];

const auditForm = reactive({
    tiktok_followers: props.profileUser.tiktok_followers ?? 0,
    instagram_followers: props.profileUser.instagram_followers ?? 0,
    facebook_followers: props.profileUser.facebook_followers ?? 0,
    youtube_followers: props.profileUser.youtube_followers ?? 0,
    snapchat_followers: props.profileUser.snapchat_followers ?? 0,
    tier: props.profileUser.tier ?? 'bronze',
});

function saveAudit() {
    auditSaving.value = true;
    router.patch(route('admin.users.update-socials', props.profileUser.id), {
        tiktok_followers: auditForm.tiktok_followers,
        instagram_followers: auditForm.instagram_followers,
        facebook_followers: auditForm.facebook_followers,
        youtube_followers: auditForm.youtube_followers,
        snapchat_followers: auditForm.snapchat_followers,
        tier: auditForm.tier,
    }, {
        preserveScroll: true,
        onFinish: () => { auditSaving.value = false; },
    });
}

function formatCurrency(v) {
    return Number(v ?? 0).toLocaleString('fr-FR') + ' FCFA';
}

function formatDate(d) {
    if (!d) return '--';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function kycBadge(status) {
    if (status === 'approved') return { class: 'bg-teal-50 text-teal-700', label: 'Approuve' };
    if (status === 'pending') return { class: 'bg-amber-50 text-amber-700', label: 'En attente' };
    return { class: 'bg-red-50 text-red-700', label: 'Rejete' };
}

function roleBadge(role) {
    if (role === 'vendor') return { class: 'bg-blue-50 text-blue-700', label: 'Vendeur' };
    if (role === 'influencer') return { class: 'bg-purple-50 text-purple-700', label: 'Createur de Contenu' };
    return { class: 'bg-slate-100 text-slate-700', label: 'Admin' };
}

const withdrawalStats = computed(() => {
    const completed = props.withdrawals.filter(w => w.status === 'completed');
    return {
        count: completed.length,
        total: completed.reduce((sum, w) => sum + Number(w.amount_total ?? 0), 0),
    };
});

const page = usePage();
const banProcessing = ref(false);

async function toggleBan() {
    if (!await ask({ title: props.profileUser.is_banned ? 'Debannir l\'utilisateur' : 'Bannir l\'utilisateur', message: props.profileUser.is_banned ? 'Debannir cet utilisateur ?' : 'Bannir cet utilisateur ?', variant: 'danger', confirmLabel: props.profileUser.is_banned ? 'Debannir' : 'Bannir' })) return;
    banProcessing.value = true;
    router.patch(route('admin.users.toggle-ban', props.profileUser.id), {}, {
        preserveScroll: true,
        onFinish: () => { banProcessing.value = false; },
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Flash success -->
        <div v-if="page.props.flash?.success" class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            <span>{{ page.props.flash.success }}</span>
        </div>

        <!-- Header + Back -->
        <div class="flex items-center gap-4">
            <Link :href="route('admin.users.index')" class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            </Link>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Dossier 360</h1>
                <p class="text-sm text-slate-500">Profil complet et historique de {{ profileUser.name }}</p>
            </div>
            <button
                v-if="profileUser.role !== 'admin'"
                @click="toggleBan"
                :disabled="banProcessing"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition disabled:opacity-50"
                :class="profileUser.is_banned
                    ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                    : 'bg-red-600 text-white hover:bg-red-700'"
            >
                <svg v-if="profileUser.is_banned" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                {{ profileUser.is_banned ? 'Debannir' : 'Bannir' }}
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- ─── Colonne Gauche : Infos ─── -->
            <div class="space-y-4">
                <!-- Identity card -->
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-lg font-bold text-slate-600">
                            {{ profileUser.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-lg font-bold text-slate-900">{{ profileUser.name }}</h2>
                            <p class="truncate text-sm text-slate-500">{{ profileUser.email }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="roleBadge(profileUser.role).class">{{ roleBadge(profileUser.role).label }}</span>
                        <span v-if="profileUser.is_vip" class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">VIP</span>
                        <span v-if="profileUser.deleted_at" class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-600">Supprime</span>
                        <span v-if="profileUser.is_banned" class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">Banni</span>
                        <span v-if="profileUser.tier" class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">{{ profileUser.tier }}</span>
                    </div>
                </div>

                <!-- Wallet -->
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" /></svg>
                        Portefeuille
                    </h3>
                    <div class="mt-3 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Solde disponible</span>
                            <span class="font-semibold text-slate-900">{{ formatCurrency(profileUser.wallet?.balance) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">En attente</span>
                            <span class="font-medium text-amber-600">{{ formatCurrency(profileUser.wallet?.pending_balance) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Escrow</span>
                            <span class="font-medium text-blue-600">{{ formatCurrency(profileUser.wallet?.escrow_balance) }}</span>
                        </div>
                    </div>
                </div>

                <!-- KYC Status -->
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                        KYC
                    </h3>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Statut actuel</span>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="kycBadge(profileUser.kyc_status).class">{{ kycBadge(profileUser.kyc_status).label }}</span>
                        </div>
                        <div v-if="profileUser.kyc_document_front" class="flex gap-2 pt-2">
                            <a :href="'/storage/' + profileUser.kyc_document_front" target="_blank" class="flex-1 overflow-hidden rounded-lg border border-slate-200">
                                <img :src="'/storage/' + profileUser.kyc_document_front" class="h-20 w-full object-cover" alt="Recto" />
                                <p class="px-2 py-1 text-center text-[10px] text-slate-500">Recto</p>
                            </a>
                            <a v-if="profileUser.kyc_document_back" :href="'/storage/' + profileUser.kyc_document_back" target="_blank" class="flex-1 overflow-hidden rounded-lg border border-slate-200">
                                <img :src="'/storage/' + profileUser.kyc_document_back" class="h-20 w-full object-cover" alt="Verso" />
                                <p class="px-2 py-1 text-center text-[10px] text-slate-500">Verso</p>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                        Contact
                    </h3>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Pays</span>
                            <span class="text-slate-900">{{ profileUser.country ?? '--' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Telephone</span>
                            <span class="text-slate-900">{{ profileUser.phone ?? '--' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Inscrit le</span>
                            <span class="text-slate-900">{{ formatDate(profileUser.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── Colonne Droite : Onglets Historique ─── -->
            <div class="lg:col-span-2">
                <!-- Tabs -->
                <div class="border-b border-slate-200">
                    <nav class="-mb-px flex gap-6">
                        <button
                            v-for="t in tabs"
                            :key="t.key"
                            @click="activeTab = t.key"
                            class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                            :class="activeTab === t.key
                                ? 'border-teal-600 text-teal-600'
                                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        >{{ t.label }}</button>
                    </nav>
                </div>

                <!-- ─── Tab : Historique KYC ─── -->
                <div v-if="activeTab === 'kyc'" class="mt-4 space-y-3">
                    <div v-if="kycLogs.length === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-10 text-center">
                        <p class="text-sm text-slate-500">Aucun historique KYC enregistre.</p>
                    </div>
                    <!-- Timeline -->
                    <div v-else class="relative pl-6">
                        <div class="absolute left-2.5 top-2 bottom-2 w-px bg-slate-200"></div>
                        <div v-for="log in kycLogs" :key="log.id" class="relative mb-4 pl-4">
                            <div class="absolute -left-[5px] top-1.5 h-3 w-3 rounded-full border-2 border-white"
                                 :class="log.action === 'approved' ? 'bg-teal-500' : 'bg-red-500'"></div>
                            <div class="rounded-lg border bg-white p-4" :class="log.action === 'rejected' ? 'border-red-200' : 'border-slate-200'">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold" :class="log.action === 'approved' ? 'text-teal-700' : 'text-red-700'">
                                        <svg v-if="log.action === 'approved'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ log.action === 'approved' ? 'KYC Approuve' : 'KYC Rejete' }}
                                    </span>
                                    <span class="text-xs text-slate-400">{{ formatDate(log.created_at) }}</span>
                                </div>
                                <p v-if="log.reason" class="mt-2 text-sm text-slate-600">{{ log.reason }}</p>
                                <p class="mt-1 text-xs text-slate-400">Par {{ log.admin?.name ?? 'Systeme' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── Tab : Finances ─── -->
                <div v-if="activeTab === 'finances'" class="mt-4 space-y-4">
                    <!-- Summary -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
                            <p class="text-2xl font-bold text-slate-900">{{ withdrawalStats.count }}</p>
                            <p class="mt-1 text-xs text-slate-500">Retraits effectues</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-4 text-center">
                            <p class="text-2xl font-bold text-teal-700">{{ formatCurrency(withdrawalStats.total) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Total retire</p>
                        </div>
                    </div>

                    <div v-if="withdrawals.length === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-10 text-center">
                        <p class="text-sm text-slate-500">Aucun retrait enregistre.</p>
                    </div>
                    <div v-else class="relative pl-6">
                        <div class="absolute left-2.5 top-2 bottom-2 w-px bg-slate-200"></div>
                        <div v-for="w in withdrawals" :key="w.id" class="relative mb-4 pl-4">
                            <div class="absolute -left-[5px] top-1.5 h-3 w-3 rounded-full border-2 border-white"
                                 :class="w.status === 'completed' ? 'bg-teal-500' : w.status === 'pending' ? 'bg-amber-500' : 'bg-red-500'"></div>
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-900">{{ formatCurrency(w.amount_total) }}</span>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                          :class="w.status === 'completed' ? 'bg-teal-50 text-teal-700' : w.status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'">
                                        {{ w.status === 'completed' ? 'Approuve' : w.status === 'pending' ? 'En attente' : 'Rejete' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-slate-400">{{ formatDate(w.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── Tab : Litiges ─── -->
                <div v-if="activeTab === 'litiges'" class="mt-4 space-y-4">
                    <h3 v-if="orderDisputes.length > 0" class="text-sm font-semibold text-slate-700">Litiges E-commerce</h3>
                    <div v-for="d in orderDisputes" :key="'o'+d.id" class="rounded-lg border border-red-200 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-slate-900">{{ d.reference }}</span>
                            <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">{{ d.status }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ d.product?.name ?? '--' }} -- {{ formatCurrency(d.amount_paid) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ formatDate(d.updated_at) }}</p>
                    </div>

                    <h3 v-if="serviceDisputes.length > 0" class="pt-2 text-sm font-semibold text-slate-700">Litiges UGC / Studios</h3>
                    <div v-for="d in serviceDisputes" :key="'s'+d.id" class="rounded-lg border border-red-200 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold text-slate-900">#{{ d.id }}</span>
                            <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">{{ d.status }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ d.service?.title ?? '--' }} -- {{ formatCurrency(d.amount) }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ formatDate(d.updated_at) }}</p>
                    </div>

                    <div v-if="orderDisputes.length === 0 && serviceDisputes.length === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-10 text-center">
                        <p class="text-sm text-slate-500">Aucun litige enregistre pour cet utilisateur.</p>
                    </div>
                </div>

                <!-- ─── Tab : Audit Social ─── -->
                <div v-if="activeTab === 'audit'" class="mt-4 space-y-4">
                    <!-- Liens sociaux cliquables + inputs abonnes -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Reseaux Sociaux -- Verification des abonnes
                        </h3>
                        <div class="space-y-3">
                            <div v-for="p in socialPlatforms" :key="p.key" class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold text-slate-800">{{ p.label }}</span>
                                    <a v-if="profileUser[p.url]" :href="profileUser[p.url]" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-700 transition hover:bg-teal-100 hover:text-teal-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                        Ouvrir le profil
                                    </a>
                                    <span v-else class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-medium text-slate-500">Aucun lien</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <label class="text-xs text-slate-500 w-28 shrink-0">Abonnes declares</label>
                                    <span class="text-sm font-medium text-slate-600 w-24">{{ (profileUser[p.field] ?? 0).toLocaleString('fr-FR') }}</span>
                                    <label class="text-xs text-slate-500 shrink-0">Correction</label>
                                    <input v-model.number="auditForm[p.field]" type="number" min="0" class="flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Forcer le Tier -->
                    <div class="rounded-xl border border-purple-200 bg-purple-50/30 p-6">
                        <label class="flex items-center gap-2 text-sm font-semibold text-purple-800 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                            Forcer le Palier (Tier)
                        </label>
                        <p class="text-xs text-slate-500 mb-3">Ecrasez le palier si le createur de contenu a menti sur ses abonnes.</p>
                        <select v-model="auditForm.tier" class="w-full rounded-xl border-purple-300 bg-white text-sm font-medium text-purple-900 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            <option value="bronze">Bronze -- Nano (1k - 10k abonnes)</option>
                            <option value="argent">Argent -- Micro (10k - 100k abonnes)</option>
                            <option value="or">Or -- Macro / Star (100k+ abonnes)</option>
                        </select>
                    </div>

                    <!-- Bouton sauvegarder -->
                    <button @click="saveAudit" :disabled="auditSaving" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        {{ auditSaving ? 'Enregistrement...' : 'Valider l\'Audit Social' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
