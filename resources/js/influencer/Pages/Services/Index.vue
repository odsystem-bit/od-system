<script setup>
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    services: { type: Array, default: () => [] },
    is_vip:   { type: Boolean, default: false },
});

// ──────────────────────────────────────────────
//  Formulaire de creation
// ──────────────────────────────────────────────

const showForm = ref(false);

const form = useForm({
    title: '',
    type: '',
    price: '',
    duration: '',
    description: '',
    included_revisions: 1,
    image: null,
});

const types = [
    { value: 'ugc_humain',    label: 'UGC Humain',     desc: 'Contenu tourne par vous-meme (face camera, unboxing, temoignage...).' },
    { value: 'video_pub_ia',  label: 'Video Pub IA',   desc: 'Video publicitaire generee avec assistance IA (avatar, voix off...).' },
];

const durations = [
    { value: '15s',  label: '15 secondes' },
    { value: '30s',  label: '30 secondes' },
    { value: '60s',  label: '60 secondes' },
    { value: 'long', label: 'Long format'  },
];

function submit() {
    form.post(route('influencer.services.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            imagePreview.value = null;
            showForm.value = false;
        },
    });
}

const imagePreview = ref(null);

function handleImageChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target.result; };
    reader.readAsDataURL(file);
}

function removeImage() {
    form.image = null;
    imagePreview.value = null;
}

// ──────────────────────────────────────────────
//  Suppression
// ──────────────────────────────────────────────

const deletingId = ref(null);

async function confirmDelete(service) {
    if (!await ask({ title: 'Supprimer le service', message: `Supprimer le service "${service.title}" ?`, variant: 'danger', confirmLabel: 'Supprimer' })) return;
    deletingId.value = service.id;
    router.delete(route('influencer.services.destroy', service.id), {
        preserveScroll: true,
        onFinish: () => (deletingId.value = null),
    });
}

// ──────────────────────────────────────────────
//  Helpers
// ──────────────────────────────────────────────

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

function typeLabel(type) {
    return types.find(t => t.value === type)?.label ?? type;
}

function durationLabel(dur) {
    return durations.find(d => d.value === dur)?.label ?? dur;
}
</script>

<template>
    <Head title="Mes services" />

    <InfluencerLayout>
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 space-y-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">MANTOTA Studios</h1>
                    <p class="mt-1 text-sm text-slate-500">Gerez vos packages de services (UGC, Video Pub IA).</p>
                </div>
                <button
                    v-if="is_vip && !showForm"
                    @click="showForm = true"
                    class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-teal-500/30"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nouveau service
                </button>
            </div>

            <!-- VIP Gate Banner -->
            <div v-if="!is_vip" class="rounded-2xl border border-amber-200/60 bg-gradient-to-r from-amber-50 to-orange-50/50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-amber-800">Statut VIP requis</h3>
                        <p class="mt-1 text-sm text-amber-700">
                            Seuls les createurs de contenu VIP peuvent creer et proposer des services sur MANTOTA Studios.
                            Demandez votre statut VIP depuis votre profil pour debloquer cette fonctionnalite.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────
                 Formulaire de creation
            ───────────────────────────────────── -->
            <div v-if="showForm && is_vip" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Creer un service
                    </h3>
                    <button @click="showForm = false; form.reset()" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Type — Cartes -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Type de service</label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="t in types"
                                :key="t.value"
                                type="button"
                                class="relative flex flex-col items-start gap-2 rounded-xl border-2 p-4 text-left transition"
                                :class="form.type === t.value
                                    ? 'border-violet-500 bg-violet-50 ring-1 ring-violet-500/20'
                                    : 'border-slate-200 bg-white hover:border-slate-300'"
                                @click="form.type = t.value"
                            >
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="form.type === t.value ? 'bg-violet-100' : 'bg-slate-100'">
                                        <svg v-if="t.value === 'ugc_humain'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="form.type === t.value ? 'text-violet-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="form.type === t.value ? 'text-violet-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-semibold" :class="form.type === t.value ? 'text-violet-700' : 'text-slate-900'">{{ t.label }}</span>
                                </div>
                                <p class="text-xs leading-relaxed" :class="form.type === t.value ? 'text-violet-600/80' : 'text-slate-500'">{{ t.desc }}</p>
                                <div class="absolute right-3 top-3 flex h-4 w-4 items-center justify-center rounded-full border-2 transition"
                                    :class="form.type === t.value ? 'border-violet-500' : 'border-slate-300'">
                                    <div v-if="form.type === t.value" class="h-2 w-2 rounded-full bg-violet-500" />
                                </div>
                            </button>
                        </div>
                        <p v-if="form.errors.type" class="mt-1.5 text-sm text-red-600">{{ form.errors.type }}</p>
                    </div>

                    <!-- Titre -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-700 mb-1">Titre du service</label>
                        <input id="title" v-model="form.title" type="text" maxlength="255" placeholder="Ex : Video UGC TikTok avec unboxing"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm" />
                        <p v-if="form.errors.title" class="mt-1.5 text-sm text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <!-- Prix + Duree -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Prix (FCFA)</label>
                            <input id="price" v-model="form.price" type="number" min="500" step="100" placeholder="5000"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm" />
                            <p v-if="form.errors.price" class="mt-1.5 text-sm text-red-600">{{ form.errors.price }}</p>
                        </div>
                        <div>
                            <label for="duration" class="block text-sm font-medium text-slate-700 mb-1">Duree de la video</label>
                            <select id="duration" v-model="form.duration"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm">
                                <option value="" disabled>Selectionnez</option>
                                <option v-for="d in durations" :key="d.value" :value="d.value">{{ d.label }}</option>
                            </select>
                            <p v-if="form.errors.duration" class="mt-1.5 text-sm text-red-600">{{ form.errors.duration }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea id="description" v-model="form.description" rows="4" maxlength="3000"
                            placeholder="Decrivez ce que le vendeur obtiendra : style de video, delai de livraison..."
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm" />
                        <p v-if="form.errors.description" class="mt-1.5 text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Nombre de retouches incluses -->
                    <div>
                        <label for="included_revisions" class="block text-sm font-medium text-slate-700 mb-1">Nombre de retouches incluses</label>
                        <select id="included_revisions" v-model="form.included_revisions"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm">
                            <option :value="0">0 -- Aucune retouche</option>
                            <option :value="1">1 retouche</option>
                            <option :value="2">2 retouches</option>
                            <option :value="3">3 retouches</option>
                            <option :value="4">4 retouches</option>
                            <option :value="5">5 retouches</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Combien de fois le vendeur peut-il demander une modification sans payer de supplement ?</p>
                        <p v-if="form.errors.included_revisions" class="mt-1.5 text-sm text-red-600">{{ form.errors.included_revisions }}</p>
                    </div>

                    <!-- Image du service -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Image du service (optionnelle)</label>
                        <div v-if="imagePreview" class="relative mb-3 inline-block">
                            <img :src="imagePreview" class="h-32 w-32 rounded-lg object-cover border border-slate-200" alt="Apercu" />
                            <button type="button" @click="removeImage"
                                class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-sm transition hover:bg-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <label v-if="!imagePreview"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            Choisir une image
                            <input type="file" accept="image/*" class="hidden" @change="handleImageChange" />
                        </label>
                        <p v-if="form.errors.image" class="mt-1.5 text-sm text-red-600">{{ form.errors.image }}</p>
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG ou WebP. Taille max : 2 Mo.</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showForm = false; form.reset()"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Annuler
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/30 disabled:opacity-50">
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ form.processing ? 'Creation...' : 'Creer le service' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ─────────────────────────────────────
                 Liste des services
            ───────────────────────────────────── -->
            <div v-if="services.length" class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="service in services"
                    :key="service.id"
                    class="group rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-teal-500/5 overflow-hidden"
                >
                    <!-- Image du service -->
                    <div v-if="service.image_path" class="aspect-video w-full overflow-hidden bg-slate-100">
                        <img :src="`/storage/${service.image_path}`" :alt="service.title" class="h-full w-full object-cover" />
                    </div>

                    <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg"
                                :class="service.type === 'ugc_humain' ? 'bg-violet-100' : 'bg-indigo-100'">
                                <svg v-if="service.type === 'ugc_humain'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ service.title }}</h3>
                                <span class="text-xs font-medium" :class="service.type === 'ugc_humain' ? 'text-violet-600' : 'text-indigo-600'">
                                    {{ typeLabel(service.type) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <Link :href="route('influencer.services.edit', service.id)"
                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-teal-50 hover:text-teal-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </Link>
                            <button
                            @click="confirmDelete(service)"
                            :disabled="deletingId === service.id"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-50"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-slate-500 line-clamp-2">{{ service.description }}</p>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-slate-900">{{ formatCurrency(service.price) }}</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ durationLabel(service.duration) }}
                            </span>
                        </div>
                        <span v-if="service.orders_count > 0" class="text-xs text-slate-400">
                            {{ service.orders_count }} commande{{ service.orders_count > 1 ? 's' : '' }}
                        </span>
                    </div>
                    </div>
                </div>
            </div>

            <!-- Etat vide -->
            <div v-else-if="is_vip" class="rounded-2xl border-2 border-dashed border-teal-200 bg-gradient-to-br from-teal-50/50 to-cyan-50/30 p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-teal-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Aucun service</h3>
                <p class="mt-1.5 text-sm text-slate-500">
                    Creez votre premier package de service pour que les vendeurs puissent vous commander du contenu.
                </p>
                <button
                    @click="showForm = true"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-teal-500/30"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Creer un service
                </button>
            </div>

        </div>
    </InfluencerLayout>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
