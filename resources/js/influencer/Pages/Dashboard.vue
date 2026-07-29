<script setup>
import InfluencerLayout from '../Layouts/InfluencerLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';

const props = defineProps({
    campaigns: {
        type: Object,
        required: true,
    },
    kyc_status: {
        type: String,
        required: true,
    },
    kyc_rejection_reason: {
        type: String,
        default: null,
    },
    has_niches: {
        type: Boolean,
        default: false,
    },
    ambassadors: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashErrors = computed(() => page.props.errors ?? {});
const smartLinkUrl = computed(() => page.props.flash?.smart_link_url ?? null);
const smartLinkExpires = computed(() => page.props.flash?.smart_link_expires ?? null);

const isKycApproved = computed(() => props.kyc_status === 'approved');

// ── Popup rejet KYC ──
const showKycRejectedPopup = ref(false);
onMounted(() => {
    if (props.kyc_status === 'rejected') {
        showKycRejectedPopup.value = true;
    }
});
const copied = ref(false);

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount) + ' FCFA';
}

function mediaUrl(campaign) {
    if (!campaign.media_path) return null;
    return '/storage/' + campaign.media_path;
}

function generateLink(campaignId) {
    router.post(
        route('influencer.campaigns.generate-link', campaignId),
        {},
        { preserveScroll: true },
    );
}

function tierLabel(tier) {
    const map = { bronze: 'Bronze', argent: 'Argent', or: 'Or' };
    return map[tier] ?? tier;
}

function tierClasses(tier) {
    const map = {
        bronze: 'bg-amber-100 text-amber-700 ring-amber-500/30',
        argent: 'bg-slate-200 text-slate-700 ring-slate-400/30',
        or: 'bg-yellow-200 text-yellow-800 ring-yellow-500/30',
    };
    return map[tier] ?? 'bg-slate-100 text-slate-600 ring-slate-400/30';
}

async function copySmartLink() {
    if (!smartLinkUrl.value) return;
    try {
        await navigator.clipboard.writeText(smartLinkUrl.value);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2500);
    } catch {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = smartLinkUrl.value;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2500);
    }
}
</script>

<template>
    <Head title="Campagnes disponibles" />

    <!-- ── Popup Rejet KYC ── -->
    <Teleport to="body">
        <div v-if="showKycRejectedPopup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center gap-3 rounded-t-2xl bg-red-600 px-6 py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <h3 class="text-base font-bold text-white">Verification KYC rejetee</h3>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-slate-700">
                        Votre dossier de verification d'identite a ete examine et <strong>refuse</strong> par notre equipe de moderation.
                    </p>
                    <div v-if="kyc_rejection_reason" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                        <p class="text-xs font-semibold text-red-700 mb-1">Raison du refus :</p>
                        <p class="text-sm text-red-800">{{ kyc_rejection_reason }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-xs font-semibold text-amber-700 mb-1">Que faire ?</p>
                        <ul class="text-xs text-amber-800 space-y-1 list-disc list-inside">
                            <li>Verifiez que vos documents sont lisibles et au format correct.</li>
                            <li>Assurez-vous que la photo d'identite correspond a vos informations.</li>
                            <li>Re-soumettez un nouveau dossier en cliquant sur le bouton ci-dessous.</li>
                        </ul>
                    </div>
                    <p class="text-xs text-slate-400">Un email detaillant ces informations vous a ete envoye.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button @click="showKycRejectedPopup = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Fermer
                    </button>
                    <Link :href="route('influencer.kyc.index')" @click="showKycRejectedPopup = false" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                        Soumettre a nouveau
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>

    <InfluencerLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-slate-800">
                    Campagnes disponibles
                </h2>
                <Link
                    :href="route('influencer.links')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700 ring-1 ring-teal-200/60 transition-all duration-200 hover:bg-teal-100 hover:shadow-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                    </svg>
                    Mes liens generes
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Bandeau KYC -->
                <div
                    v-if="!isKycApproved"
                    class="flex items-start gap-4 rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 px-5 py-4"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-amber-800">Verification d'identite requise</h4>
                        <p class="mt-1 text-sm text-amber-700">
                            Vous devez completer la verification KYC pour pouvoir generer des liens de campagne.
                        </p>
                        <Link
                            :href="route('influencer.kyc.index')"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-amber-500/20 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                            </svg>
                            Completer mon KYC
                        </Link>
                    </div>
                </div>

                <!-- Bandeau Niches -->
                <div
                    v-if="!has_niches"
                    class="flex items-start gap-4 rounded-2xl border border-teal-200 bg-gradient-to-r from-teal-50 to-cyan-50 px-5 py-4"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-teal-800">Selectionnez vos niches de contenu</h4>
                        <p class="mt-1 text-sm text-teal-700">
                            Pour voir les campagnes qui correspondent a votre profil, selectionnez jusqu'a 3 niches dans votre profil.
                        </p>
                        <Link
                            :href="route('influencer.profile.edit')"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-teal-500/20 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                            </svg>
                            Mettre a jour mon profil
                        </Link>
                    </div>
                </div>

                <!-- SmartLink genere : bandeau de copie -->
                <div
                    v-if="smartLinkUrl"
                    class="rounded-2xl border border-teal-200 bg-gradient-to-r from-teal-50 via-cyan-50 to-teal-50 px-5 py-4"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-teal-800">Votre lien unique a ete genere</h4>
                            <p class="mt-1 text-xs text-teal-600">Expire le {{ smartLinkExpires }}. Partagez-le sur vos reseaux pour gagner a chaque clic.</p>
                            <div class="mt-3 flex items-center gap-2">
                                <div class="flex-1 min-w-0 rounded-xl border border-teal-300 bg-white px-3 py-2">
                                    <p class="truncate text-sm font-mono text-teal-800">{{ smartLinkUrl }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm transition"
                                    :class="copied ? 'bg-teal-700' : 'bg-teal-600 hover:bg-teal-700'"
                                    @click="copySmartLink"
                                >
                                    <!-- Heroicon: clipboard-document-check (copied) / clipboard (default) -->
                                    <svg v-if="copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25H10.5a2.25 2.25 0 00-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                    </svg>
                                    {{ copied ? 'Copie !' : 'Copier le lien' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flash success (sans lien) -->
                <div
                    v-if="flashSuccess && !smartLinkUrl"
                    class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ flashSuccess }}</span>
                </div>

                <!-- Flash errors -->
                <div
                    v-if="Object.keys(flashErrors).length"
                    class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <ul class="list-inside list-disc">
                        <li v-for="(msg, field) in flashErrors" :key="field">{{ msg }}</li>
                    </ul>
                </div>

                <!-- Empty state -->
                <div
                    v-if="!campaigns.data.length"
                    class="rounded-2xl border-2 border-dashed border-teal-200 bg-teal-50/30 p-16 text-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-14 w-14 text-teal-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">Aucune campagne disponible</h3>
                    <p class="mt-1 text-sm text-slate-500">Revenez bientot, de nouvelles opportunites seront publiees par les vendeurs.</p>
                </div>

                <!-- Grille de campagnes -->
                <div
                    v-else
                    class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="campaign in campaigns.data"
                        :key="campaign.id"
                        class="group flex flex-col rounded-2xl border bg-white shadow-sm transition-all duration-500 hover:-translate-y-2 hover:shadow-xl hover:shadow-teal-500/5 overflow-hidden"
                        :class="campaign.is_system_campaign
                            ? 'border-teal-300 ring-1 ring-teal-200/50'
                            : 'border-slate-200/80'"
                    >
                        <!-- Media -->
                        <div
                            v-if="campaign.media_path"
                            class="relative bg-slate-100"
                        >
                            <img
                                v-if="campaign.media_type === 'image'"
                                :src="mediaUrl(campaign)"
                                :alt="campaign.title"
                                class="h-44 w-full object-cover"
                            />
                            <video
                                v-else
                                :src="mediaUrl(campaign)"
                                controls
                                preload="metadata"
                                class="h-44 w-full object-cover"
                            ></video>
                            <!-- Badge media type -->
                            <div class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-black/50 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm">
                                <svg v-if="campaign.media_type === 'image'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-2.625 0V5.625m0 0A1.125 1.125 0 014.5 4.5h15a1.125 1.125 0 011.125 1.125m-17.25 0h17.25m0 0v12.75M20.625 5.625v12.75m0 0a1.125 1.125 0 01-1.125 1.125m1.125-1.125h-1.5C18.504 18.375 18 18.996 18 19.5m2.625-1.125H18" />
                                </svg>
                                {{ campaign.media_type === 'image' ? 'Image' : 'Video' }}
                            </div>
                        </div>

                        <!-- Placeholder si pas de media -->
                        <div
                            v-else
                            class="flex h-44 items-center justify-center bg-slate-100"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                            </svg>
                        </div>

                        <!-- Corps -->
                        <div class="flex-1 p-5 space-y-3">
                            <!-- Tier + Open Sea + System badges -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <span v-if="campaign.is_system_campaign" class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-teal-700 ring-1 ring-inset ring-teal-500/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
                                    </svg>
                                    Campagne Officielle MANTOTA
                                </span>
                                <span v-if="campaign.tier" class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ring-1 ring-inset"
                                      :class="tierClasses(campaign.tier)"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                    </svg>
                                    {{ tierLabel(campaign.tier) }}
                                </span>
                                <span v-if="campaign.open_sea" class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-teal-700 ring-1 ring-inset ring-teal-500/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 3.03v.568c0 .334.148.65.405.864l1.068.89c.442.369.535 1.01.216 1.49l-.51.766a2.25 2.25 0 01-1.161.886l-.143.048a1.107 1.107 0 00-.57 1.664c.369.555.169 1.307-.427 1.605L9 13.125l.423 1.059a.956.956 0 01-1.652.928l-.679-.906a1.125 1.125 0 00-1.906.172L4.5 15.75l-.612.153M12.75 3.031a9 9 0 10-8.862 12.872M12.75 3.031a9 9 0 016.69 14.036m0 0l-.177-.529A2.25 2.25 0 0017.128 15H16.5l-.324-.324a1.453 1.453 0 00-2.328.377l-.036.073a1.586 1.586 0 01-.982.816l-.99.282c-.55.157-.894.702-.8 1.267l.073.438c.08.474.49.821.97.821.846 0 1.598.542 1.865 1.345l.215.643m5.276-3.67a9.012 9.012 0 01-5.276 3.67" />
                                    </svg>
                                    Open-Sea
                                </span>
                            </div>

                            <!-- Titre + Vendeur -->
                            <h3 class="text-base font-semibold text-slate-900 leading-snug">
                                {{ campaign.title }}
                            </h3>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <img v-if="campaign.vendor?.shop_logo_path" :src="`/storage/${campaign.vendor.shop_logo_path}`" :alt="campaign.vendor?.shop_name || campaign.vendor?.name" class="h-5 w-5 rounded-full object-cover ring-1 ring-slate-200" />
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <span>{{ campaign.vendor?.shop_name || campaign.vendor?.business_name || campaign.vendor?.name || 'Vendeur' }}</span>
                            </div>

                            <!-- Remuneration : CPC + Commission (CPA) -->
                            <div class="grid grid-cols-2 gap-2 pt-2">
                                <!-- CPC -->
                                <div class="flex items-center gap-2.5 rounded-xl border border-teal-200/60 bg-gradient-to-br from-teal-50 to-cyan-50 px-3 py-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-bold text-teal-800">{{ campaign.click_price }} FCFA</p>
                                        <p class="text-[10px] font-medium text-teal-600 uppercase tracking-wide">par Clic (CPC)</p>
                                    </div>
                                </div>
                                <!-- CPA -->
                                <div v-if="campaign.commission_percent" class="flex items-center gap-2.5 rounded-xl border border-purple-200/60 bg-gradient-to-br from-purple-50 to-violet-50 px-3 py-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-bold text-purple-800">{{ campaign.commission_percent }}%</p>
                                        <p class="text-[10px] font-medium text-purple-600 uppercase tracking-wide">par Vente (CPA)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Budget restant + liens -->
                            <div class="flex items-center gap-4 pt-1">
                                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                    </svg>
                                    Budget restant : {{ formatCurrency(campaign.remaining_budget) }}
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                    </svg>
                                    {{ campaign.smart_links_count }} liens
                                </div>
                            </div>
                        </div>

                            <!-- Consignes du vendeur -->
                            <div v-if="campaign.instructions" class="rounded-xl border border-amber-200/60 bg-gradient-to-br from-amber-50 to-yellow-50 px-3 py-2.5 mt-2">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <p class="text-[10px] font-semibold text-amber-700 uppercase tracking-wide">Consignes</p>
                                </div>
                                <p class="text-xs text-amber-900 leading-relaxed line-clamp-3">{{ campaign.instructions }}</p>
                            </div>

                        <!-- Footer -->
                        <div class="border-t border-slate-100 px-5 py-3">
                            <!-- MISSION 1 : Bouton conditionnel selon has_generated_link -->
                            
                            <!-- Bouton Turquoise : Generer mon lien (si pas encore genere) -->
                            <button
                                v-if="isKycApproved && !campaign.has_generated_link"
                                type="button"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-teal-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/30 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600"
                                @click="generateLink(campaign.id)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                </svg>
                                Generer mon lien
                            </button>

                            <!-- Bouton Gris : Lien deja genere (redirige vers Mes Liens) -->
                            <Link
                                v-else-if="isKycApproved && campaign.has_generated_link"
                                :href="route('influencer.links')"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-300 hover:bg-slate-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                </svg>
                                Lien deja genere
                            </Link>

                            <button
                                v-else
                                type="button"
                                disabled
                                class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-500 cursor-not-allowed"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                KYC requis
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="campaigns.data.length && campaigns.last_page > 1"
                    class="flex items-center justify-between"
                >
                    <p class="text-sm text-slate-500">
                        Page {{ campaigns.current_page }} sur {{ campaigns.last_page }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="campaigns.prev_page_url"
                            :href="campaigns.prev_page_url"
                            class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                            Precedent
                        </Link>
                        <Link
                            v-if="campaigns.next_page_url"
                            :href="campaigns.next_page_url"
                            class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            Suivant
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </Link>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══════ AMBASSADEURS CAROUSEL ══════ -->
        <div v-if="ambassadors && ambassadors.length" class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-600" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Ambassadeurs MANTOTA</h3>
            </div>
            <div class="flex gap-4 overflow-x-auto px-6 py-4 scrollbar-thin scrollbar-thumb-slate-200">
                <div v-for="amb in ambassadors" :key="amb.id" class="flex shrink-0 items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white">
                        <img v-if="amb.profile_photo" :src="'/storage/' + amb.profile_photo" :alt="amb.name" class="h-full w-full object-cover" />
                        <span v-else>{{ amb.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ amb.shop_name || amb.business_name || amb.name }}</p>
                        <span class="flex items-center gap-1 text-xs text-cyan-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                            Ambassadeur
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </InfluencerLayout>
</template>
