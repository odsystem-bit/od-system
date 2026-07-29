<script setup>
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    kyc_status:         { type: String, required: true },
    kyc_document_front: { type: String, default: null },
    kyc_document_back:  { type: String, default: null },
});

const form = useForm({
    id_card_front: null,
    id_card_back:  null,
});

// ──────────────────────────────────────────────
//  Status helpers
// ──────────────────────────────────────────────

const canSubmit  = computed(() => ['not_submitted', 'rejected'].includes(props.kyc_status));
const isPending  = computed(() => props.kyc_status === 'pending');
const isApproved = computed(() => props.kyc_status === 'approved');

const statusConfig = {
    not_submitted: {
        label: 'Non soumis',
        desc: 'Completez le formulaire ci-dessous pour soumettre votre piece d\'identite.',
        bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-700', icon: 'text-slate-400',
    },
    pending: {
        label: 'En cours de verification',
        desc: 'Votre dossier est en cours d\'examen par notre equipe. Vous serez notifie du resultat.',
        bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-800', icon: 'text-amber-500',
    },
    approved: {
        label: 'Verifie',
        desc: 'Votre identite a ete verifiee. Vous pouvez participer aux campagnes.',
        bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-800', icon: 'text-emerald-500',
    },
    rejected: {
        label: 'Refuse',
        desc: 'Votre dossier a ete refuse. Veuillez corriger et soumettre a nouveau.',
        bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-800', icon: 'text-red-500',
    },
};

const currentStatus = computed(() => statusConfig[props.kyc_status] || statusConfig.not_submitted);

// ──────────────────────────────────────────────
//  File previews
// ──────────────────────────────────────────────

const frontPreview = ref(null);
const backPreview  = ref(null);

function handleFrontFile(event) {
    const file = event.target.files?.[0];
    if (file) {
        form.id_card_front = file;
        if (frontPreview.value) URL.revokeObjectURL(frontPreview.value);
        frontPreview.value = URL.createObjectURL(file);
    }
}

function handleBackFile(event) {
    const file = event.target.files?.[0];
    if (file) {
        form.id_card_back = file;
        if (backPreview.value) URL.revokeObjectURL(backPreview.value);
        backPreview.value = URL.createObjectURL(file);
    }
}

// ──────────────────────────────────────────────
//  Submit
// ──────────────────────────────────────────────

function submit() {
    form.post(route('influencer.kyc.store'), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Verification d'identite" />

    <InfluencerLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Verification d'identite</h1>
                    <p class="mt-1 text-sm text-slate-500">Soumettez votre piece d'identite pour pouvoir participer aux campagnes.</p>
                </div>
                <Link
                    :href="route('influencer.dashboard')"
                    class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-all duration-300 hover:bg-teal-50 hover:border-teal-300 hover:-translate-y-0.5"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour
                </Link>
            </div>

            <!-- ═══════════════════════════════════════════
                 Bandeau de statut
            ═══════════════════════════════════════════ -->
            <div
                class="flex items-start gap-4 rounded-2xl border px-5 py-4"
                :class="[currentStatus.bg, currentStatus.border]"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/60">
                    <!-- not_submitted -->
                    <svg v-if="kyc_status === 'not_submitted'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="currentStatus.icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <!-- pending -->
                    <svg v-else-if="kyc_status === 'pending'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="currentStatus.icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <!-- approved -->
                    <svg v-else-if="kyc_status === 'approved'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="currentStatus.icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <!-- rejected -->
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="currentStatus.icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold" :class="currentStatus.text">{{ currentStatus.label }}</h4>
                    <p class="mt-1 text-sm" :class="currentStatus.text" style="opacity: 0.85">{{ currentStatus.desc }}</p>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════
                 Documents deja soumis (si pending/approved)
            ═══════════════════════════════════════════ -->
            <div
                v-if="(isPending || isApproved) && (kyc_document_front || kyc_document_back)"
                class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm"
            >
                <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                    </svg>
                    Documents soumis
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-if="kyc_document_front" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-medium text-slate-500 mb-2">Recto</p>
                        <img :src="kyc_document_front" alt="Recto" class="max-h-40 rounded-lg object-contain" />
                    </div>
                    <div v-if="kyc_document_back" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-medium text-slate-500 mb-2">Verso</p>
                        <img :src="kyc_document_back" alt="Verso" class="max-h-40 rounded-lg object-contain" />
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════
                 Formulaire de soumission (si not_submitted ou rejected)
            ═══════════════════════════════════════════ -->
            <form
                v-if="canSubmit"
                @submit.prevent="submit"
                class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6"
            >
                <div>
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                        </svg>
                        Piece d'identite
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Uploadez le recto et le verso de votre carte d'identite ou passeport.
                    </p>
                </div>

                <!-- Recto -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Recto de la piece</label>
                    <div
                        class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-slate-400"
                    >
                        <div v-if="frontPreview" class="mb-3">
                            <img :src="frontPreview" alt="Recto" class="max-h-32 rounded-lg object-contain" />
                        </div>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                        </svg>
                        <p class="mt-2 text-sm text-slate-600">
                            <span class="font-semibold text-teal-600">Cliquez pour choisir</span> ou glissez ici
                        </p>
                        <p class="mt-1 text-xs text-slate-400">JPEG, PNG, WebP ou PDF — 5 Mo max</p>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="absolute inset-0 cursor-pointer opacity-0"
                            @change="handleFrontFile"
                        />
                    </div>
                    <p v-if="form.errors.id_card_front" class="mt-1.5 text-sm text-red-600">{{ form.errors.id_card_front }}</p>
                </div>

                <!-- Verso -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Verso de la piece</label>
                    <div
                        class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-slate-400"
                    >
                        <div v-if="backPreview" class="mb-3">
                            <img :src="backPreview" alt="Verso" class="max-h-32 rounded-lg object-contain" />
                        </div>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                        </svg>
                        <p class="mt-2 text-sm text-slate-600">
                            <span class="font-semibold text-teal-600">Cliquez pour choisir</span> ou glissez ici
                        </p>
                        <p class="mt-1 text-xs text-slate-400">JPEG, PNG, WebP ou PDF — 5 Mo max</p>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="absolute inset-0 cursor-pointer opacity-0"
                            @change="handleBackFile"
                        />
                    </div>
                    <p v-if="form.errors.id_card_back" class="mt-1.5 text-sm text-red-600">{{ form.errors.id_card_back }}</p>
                </div>

                <!-- Erreurs serveur -->
                <p v-if="form.errors.kyc" class="text-sm text-red-600">{{ form.errors.kyc }}</p>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('influencer.dashboard')"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ form.processing ? 'Envoi en cours...' : 'Soumettre mes documents' }}
                    </button>
                </div>
            </form>

        </div>
    </InfluencerLayout>
</template>
