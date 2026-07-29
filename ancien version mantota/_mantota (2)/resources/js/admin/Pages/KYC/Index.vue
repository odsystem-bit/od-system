<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    tab: String,
    pendingKyc: Object,
    pendingVip: Object,
    kycHistory: Object,
});

function formatDate(d) {
    if (!d) return '\u2014';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const expandedUser = ref(null);
function toggleHistory(userId) {
    expandedUser.value = expandedUser.value === userId ? null : userId;
}
function userHistory(userId) {
    return props.kycHistory?.[userId] ?? [];
}

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const activeTab = ref(props.tab ?? 'kyc');

function switchTab(tab) {
    activeTab.value = tab;
    router.get(route('admin.kyc.index'), { tab }, { preserveState: true, replace: true });
}

function approveKyc(userId) {
    router.patch(route('admin.kyc.approve', userId), {}, { preserveScroll: true });
}

function rejectKyc(userId) {
    router.patch(route('admin.kyc.reject', userId), {}, { preserveScroll: true });
}

function approveVip(userId) {
    router.patch(route('admin.vip.approve', userId), {}, { preserveScroll: true });
}

function rejectVip(userId) {
    router.patch(route('admin.vip.reject', userId), {}, { preserveScroll: true });
}

const tabs = [
    { key: 'kyc', label: 'Dossiers KYC' },
    { key: 'vip', label: 'Demandes VIP' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900">KYC & VIP</h1>
            <p class="mt-1 text-sm text-slate-500">Verification des documents d'identite et audit des reseaux sociaux.</p>
        </div>

        <!-- Flash -->
        <div v-if="flash.success" class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">
            {{ flash.success }}
        </div>

        <!-- Tabs -->
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    @click="switchTab(t.key)"
                    class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                    :class="activeTab === t.key
                        ? 'border-teal-600 text-teal-600'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                >
                    {{ t.label }}
                </button>
            </nav>
        </div>

        <!-- TAB: KYC -->
        <div v-if="activeTab === 'kyc'">
            <div v-if="pendingKyc.data.length === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center">
                <p class="text-sm text-slate-500">Aucun dossier KYC en attente.</p>
            </div>
            <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 font-medium text-slate-600">Nom</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Email</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Role</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Pays</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Documents</th>
                            <th class="px-4 py-3 font-medium text-slate-600">Date</th>
                            <th class="px-4 py-3 font-medium text-slate-600 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template v-for="u in pendingKyc.data" :key="u.id">
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ u.name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ u.email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                          :class="u.role === 'vendor' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'">
                                        {{ u.role === 'vendor' ? 'Vendeur' : 'Createur de Contenu' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ u.country ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a v-if="u.kyc_front_url" :href="u.kyc_front_url" target="_blank" rel="noopener noreferrer" class="group">
                                            <img :src="u.kyc_front_url" alt="Recto" class="h-10 w-14 rounded border border-slate-200 object-cover transition group-hover:ring-2 group-hover:ring-teal-500" />
                                        </a>
                                        <a v-if="u.kyc_back_url" :href="u.kyc_back_url" target="_blank" rel="noopener noreferrer" class="group">
                                            <img :src="u.kyc_back_url" alt="Verso" class="h-10 w-14 rounded border border-slate-200 object-cover transition group-hover:ring-2 group-hover:ring-teal-500" />
                                        </a>
                                        <span v-if="!u.kyc_front_url && !u.kyc_back_url" class="text-xs text-slate-400">--</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ new Date(u.created_at).toLocaleDateString('fr-FR') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button v-if="userHistory(u.id).length" @click="toggleHistory(u.id)" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100" :title="expandedUser === u.id ? 'Masquer historique' : 'Voir historique'">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                        <button @click="approveKyc(u.id)" class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-500">
                                            Approuver
                                        </button>
                                        <button @click="rejectKyc(u.id)" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-red-50 hover:border-red-300 hover:text-red-700">
                                            Rejeter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Contextual KYC History Panel -->
                            <tr v-if="expandedUser === u.id && userHistory(u.id).length">
                                <td colspan="7" class="bg-slate-50/50 px-4 py-3">
                                    <div class="rounded-lg border border-amber-200 bg-amber-50/60 px-4 py-3">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                            <p class="text-xs font-semibold text-amber-800">Historique des verifications ({{ userHistory(u.id).length }})</p>
                                        </div>
                                        <div class="space-y-2">
                                            <div v-for="log in userHistory(u.id)" :key="log.id" class="flex items-start gap-3 rounded-md bg-white/70 px-3 py-2 text-xs">
                                                <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" :class="log.action === 'approved' ? 'bg-teal-500' : 'bg-red-500'"></span>
                                                <div class="flex-1">
                                                    <span class="font-semibold" :class="log.action === 'approved' ? 'text-teal-700' : 'text-red-700'">
                                                        {{ log.action === 'approved' ? 'Approuve' : 'Rejete' }}
                                                    </span>
                                                    <span class="text-slate-500"> par {{ log.admin?.name ?? 'Systeme' }} -- {{ formatDate(log.created_at) }}</span>
                                                    <p v-if="log.reason" class="mt-0.5 text-slate-600 italic">{{ log.reason }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: VIP -->
        <div v-if="activeTab === 'vip'">
            <div v-if="pendingVip.data.length === 0" class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center">
                <p class="text-sm text-slate-500">Aucune demande VIP en attente.</p>
            </div>
            <div v-else class="space-y-4">
                <div v-for="u in pendingVip.data" :key="u.id" class="rounded-xl border border-slate-200 bg-white p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ u.name }}</h3>
                            <p class="text-xs text-slate-500">{{ u.email }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="approveVip(u.id)" class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-purple-500">
                                Accorder VIP
                            </button>
                            <button @click="rejectVip(u.id)" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-red-50 hover:border-red-300 hover:text-red-700">
                                Refuser
                            </button>
                        </div>
                    </div>
                    <!-- Social audit grid -->
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        <div v-if="u.tiktok_url" class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500">TikTok</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ (u.tiktok_followers ?? 0).toLocaleString() }}</p>
                            <a :href="u.tiktok_url" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-xs text-teal-600 hover:underline">Voir profil</a>
                        </div>
                        <div v-if="u.instagram_url" class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500">Instagram</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ (u.instagram_followers ?? 0).toLocaleString() }}</p>
                            <a :href="u.instagram_url" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-xs text-teal-600 hover:underline">Voir profil</a>
                        </div>
                        <div v-if="u.facebook_url" class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500">Facebook</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ (u.facebook_followers ?? 0).toLocaleString() }}</p>
                            <a :href="u.facebook_url" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-xs text-teal-600 hover:underline">Voir profil</a>
                        </div>
                        <div v-if="u.youtube_url" class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500">YouTube</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ (u.youtube_followers ?? 0).toLocaleString() }}</p>
                            <a :href="u.youtube_url" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-xs text-teal-600 hover:underline">Voir profil</a>
                        </div>
                        <div v-if="u.snapchat_url" class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <p class="text-xs font-medium text-slate-500">Snapchat</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">{{ (u.snapchat_followers ?? 0).toLocaleString() }}</p>
                            <a :href="u.snapchat_url" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-xs text-teal-600 hover:underline">Voir profil</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
