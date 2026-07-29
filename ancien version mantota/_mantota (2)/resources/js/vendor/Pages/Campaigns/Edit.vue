<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    campaign:         { type: Object, required: true },
    available_niches: { type: Array, default: () => [] },
    countries:        { type: Array, default: () => [] },
    country_names:    { type: Object, default: () => ({}) },
    platforms:        { type: Array, default: () => [] },
    min_cpc:          { type: Number, default: 25 },
});

const form = useForm({
    _method: 'put',
    title: props.campaign.title ?? '',
    target_url: props.campaign.target_url ?? '',
    niche: props.campaign.niche ?? '',
    instructions: props.campaign.instructions ?? '',
    target_country: props.campaign.target_country ?? [],
    click_price: props.campaign.click_price ?? 50,
    platforms: props.campaign.platforms ?? [],
    media: null,
});

const mediaPreview = ref(null);

function onMediaChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.media = file;
    if (file.type.startsWith('image/')) {
        mediaPreview.value = URL.createObjectURL(file);
    } else {
        mediaPreview.value = null;
    }
}

function toggleCountry(c) {
    const idx = form.target_country.indexOf(c);
    if (idx === -1) {
        form.target_country.push(c);
    } else {
        form.target_country.splice(idx, 1);
    }
}

function togglePlatform(p) {
    const idx = form.platforms.indexOf(p);
    if (idx === -1) {
        form.platforms.push(p);
    } else {
        form.platforms.splice(idx, 1);
    }
}

function submit() {
    form.post(route('vendor.campaigns.update', props.campaign.id), {
        forceFormData: true,
    });
}

function formatCurrency(v) {
    return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' FCFA';
}

const existingMediaUrl = computed(() => {
    if (!props.campaign.media_path) return null;
    return '/storage/' + props.campaign.media_path;
});

const platformLabels = {
    tiktok: 'TikTok',
    facebook: 'Facebook',
    instagram: 'Instagram',
    youtube: 'YouTube',
    snapchat: 'Snapchat',
};
</script>

<template>
    <Head title="Modifier la campagne" />

    <VendorLayout>
        <div class="mx-auto max-w-2xl space-y-6">

            <!-- Breadcrumb -->
            <Link :href="route('vendor.campaigns.show', campaign.id)" class="inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                Retour a la campagne
            </Link>

            <!-- Header -->
            <div>
                <h1 class="text-xl font-bold text-slate-900">Modifier la campagne</h1>
                <p class="mt-1 text-sm text-slate-500">Modifiez les parametres de votre campagne. Le budget ne peut pas etre modifie ici.</p>
            </div>

            <!-- Banniere Rejet -->
            <div v-if="campaign.status === 'rejected'" class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    <div>
                        <p class="text-sm font-bold text-red-800">Campagne rejetee</p>
                        <p v-if="campaign.rejection_reason" class="mt-1 text-sm text-red-700">{{ campaign.rejection_reason }}</p>
                        <p class="mt-1 text-xs text-red-600">Corrigez le contenu interdit ci-dessous, la campagne sera automatiquement reactvee a la sauvegarde.</p>
                    </div>
                </div>
            </div>

            <!-- Budget Info Banner (read-only) -->
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                <div class="text-sm text-slate-600">
                    <span class="font-medium">Budget total :</span> {{ formatCurrency(campaign.total_budget) }} --
                    <span class="font-medium">Restant :</span> {{ formatCurrency(campaign.remaining_budget) }}
                    <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                        :class="campaign.status === 'active' ? 'bg-purple-50 text-purple-700 ring-purple-600/20' : campaign.status === 'paused' ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/20'"
                    >{{ campaign.status }}</span>
                </div>
                <Link :href="route('vendor.campaigns.show', campaign.id)" class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700 transition hover:bg-purple-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Ajouter des fonds
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-5">

                <!-- Titre -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <label for="title" class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                        Titre de la campagne
                    </label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                        placeholder="Ex : Promotion Creme Hydratante"
                    />
                    <p v-if="form.errors.title" class="mt-1.5 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <!-- URL de destination -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <label for="target_url" class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                        URL de destination
                    </label>
                    <input
                        id="target_url"
                        v-model="form.target_url"
                        type="url"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                        placeholder="https://votre-boutique.com/produit"
                    />
                    <p v-if="form.errors.target_url" class="mt-1.5 text-xs text-red-600">{{ form.errors.target_url }}</p>
                </div>

                <!-- Pays cible + Niche -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-3">
                            <!-- Heroicon: GlobeAlt -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                            Pays cibles
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="c in countries"
                                :key="c"
                                type="button"
                                @click="toggleCountry(c)"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-inset transition"
                                :class="form.target_country.includes(c)
                                    ? 'bg-purple-50 text-purple-700 ring-purple-600/30'
                                    : 'bg-slate-50 text-slate-500 ring-slate-300 hover:bg-slate-100'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="form.target_country.includes(c) ? 'bg-purple-500' : 'bg-slate-300'"></span>
                                {{ props.country_names[c] || c }}
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">Selectionnez un ou plusieurs pays.</p>
                        <p v-if="form.errors.target_country" class="mt-1.5 text-xs text-red-600">{{ form.errors.target_country }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                        <label for="niche" class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
                            Niche ciblee
                        </label>
                        <select
                            id="niche"
                            v-model="form.niche"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                        >
                            <option value="" disabled>Selectionnez une niche</option>
                            <option v-for="n in available_niches" :key="n.value" :value="n.value">{{ n.label }}</option>
                        </select>
                        <p v-if="form.errors.niche" class="mt-1.5 text-xs text-red-600">{{ form.errors.niche }}</p>
                    </div>

                    <!-- Consignes -->
                    <div class="mt-5">
                        <label for="instructions" class="block text-sm font-semibold text-slate-700 mb-1.5">Consignes pour les createurs de contenu</label>
                        <p class="text-xs text-slate-500 mb-2">Instructions visibles par les createurs de contenu (ton, hashtags, mentions...).</p>
                        <textarea
                            id="instructions"
                            v-model="form.instructions"
                            rows="4"
                            maxlength="2000"
                            placeholder="Ex : Mentionnez @mantota. Utilisez #MantotaDeal."
                            class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                        ></textarea>
                        <p v-if="form.errors.instructions" class="mt-1.5 text-xs text-red-600">{{ form.errors.instructions }}</p>
                    </div>
                </div>

                <!-- CPC -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <label for="click_price" class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Cout par clic (CPC) -- FCFA
                    </label>
                    <input
                        id="click_price"
                        v-model.number="form.click_price"
                        type="number"
                        :min="min_cpc"
                        step="5"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-purple-500 focus:ring-purple-500"
                    />
                    <p class="mt-1 text-xs text-slate-500">Minimum {{ min_cpc }} FCFA par clic. Plus le CPC est eleve, plus votre campagne est attractive.</p>
                    <p v-if="form.errors.click_price" class="mt-1.5 text-xs text-red-600">{{ form.errors.click_price }}</p>
                </div>

                <!-- Plateformes -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" /></svg>
                        Reseaux sociaux cibles
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="p in platforms"
                            :key="p"
                            type="button"
                            @click="togglePlatform(p)"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-inset transition"
                            :class="form.platforms.includes(p)
                                ? 'bg-purple-50 text-purple-700 ring-purple-600/30'
                                : 'bg-slate-50 text-slate-500 ring-slate-300 hover:bg-slate-100'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="form.platforms.includes(p) ? 'bg-purple-500' : 'bg-slate-300'"></span>
                            {{ platformLabels[p] || p }}
                        </button>
                    </div>
                    <p v-if="form.errors.platforms" class="mt-1.5 text-xs text-red-600">{{ form.errors.platforms }}</p>
                </div>

                <!-- Media (optionnel) -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" /></svg>
                        Media promotionnel (optionnel)
                    </label>
                    <p class="text-xs text-slate-500 mb-3">Laissez vide pour conserver le media actuel. Formats : JPG, PNG, MP4, MOV (max 50 Mo).</p>

                    <!-- Apercu media existant -->
                    <div v-if="existingMediaUrl && !mediaPreview && !form.media" class="mb-3 rounded-lg overflow-hidden border border-slate-200">
                        <img v-if="campaign.media_type === 'image'" :src="existingMediaUrl" alt="Media actuel" class="max-h-40 w-full object-cover" />
                        <video v-else :src="existingMediaUrl" class="max-h-40 w-full object-cover" controls />
                    </div>

                    <!-- Apercu nouveau media -->
                    <div v-if="mediaPreview" class="mb-3 rounded-lg overflow-hidden border border-purple-200">
                        <img :src="mediaPreview" alt="Nouveau media" class="max-h-40 w-full object-cover" />
                    </div>

                    <input
                        type="file"
                        accept="image/jpeg,image/png,video/mp4,video/quicktime"
                        @change="onMediaChange"
                        class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-purple-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-purple-700 hover:file:bg-purple-100"
                    />
                    <p v-if="form.errors.media" class="mt-1.5 text-xs text-red-600">{{ form.errors.media }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 disabled:opacity-50"
                    >
                        <svg v-if="!form.processing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                        <svg v-else class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                    </button>
                    <Link :href="route('vendor.campaigns.show', campaign.id)" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    </VendorLayout>
</template>
