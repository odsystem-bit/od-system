<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    available_niches: Array,
    tiers: Array,
    tier_thresholds: Object,
    countries: { type: Array, default: () => [] },
    country_names: { type: Object, default: () => ({}) },
});

const form = useForm({
    title: '',
    target_url: '',
    media: null,
    target_country: [],
    niche: '',
    click_price: 50,
    platforms: [],
    total_budget: 10000,
    open_sea: false,
});

const countryList = computed(() => props.countries.length ? props.countries : ['BJ', 'CI', 'SN', 'TG', 'CM']);

function toggleCountry(c) {
    const idx = form.target_country.indexOf(c);
    if (idx === -1) {
        form.target_country.push(c);
    } else {
        form.target_country.splice(idx, 1);
    }
}

const platforms = [
    { value: 'tiktok', label: 'TikTok' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'instagram', label: 'Instagram' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'snapchat', label: 'Snapchat' },
];

function onMediaChange(e) {
    form.media = e.target.files[0] ?? null;
}

function togglePlatform(value) {
    if (form.platforms.includes(value)) {
        form.platforms = form.platforms.filter(p => p !== value);
    } else {
        form.platforms.push(value);
    }
}

const tierLabel = computed(() => {
    const b = form.total_budget;
    if (b >= props.tier_thresholds.or) return 'Or';
    if (b >= props.tier_thresholds.argent) return 'Argent';
    return 'Bronze';
});

function formatCurrency(v) {
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0 }).format(v) + ' FCFA';
}

function submit() {
    form.post(route('admin.campaigns.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="space-y-6">

        <!-- God Mode Banner -->
        <div class="relative overflow-hidden rounded-2xl border border-purple-300/60 bg-gradient-to-r from-purple-600 via-purple-700 to-teal-600 px-6 py-5 shadow-lg">
            <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/5 blur-2xl"></div>
            <div class="relative flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Mode God -- Campagne Officielle MANTOTA</h2>
                    <p class="mt-0.5 text-sm text-purple-200/90">Le budget alloue ne sera pas debite de votre Wallet. Budget virtuel illimite.</p>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Creer une campagne systeme</h1>
            <p class="mt-1 text-sm text-slate-500">Cette campagne apparaitra avec le badge "Campagne Officielle MANTOTA" sur le dashboard createur de contenu.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">

            <!-- Section 1 : Informations -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-5">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Informations de base</h3>

                <!-- Titre -->
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Titre de la campagne</label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                        placeholder="Ex : Promotion Officielle MANTOTA -- Ete 2026"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <!-- URL cible -->
                <div>
                    <label for="target_url" class="block text-sm font-medium text-slate-700">Lien cible (URL)</label>
                    <input
                        id="target_url"
                        v-model="form.target_url"
                        type="url"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                        placeholder="https://mantota.com/promo"
                    />
                    <p v-if="form.errors.target_url" class="mt-1 text-xs text-red-600">{{ form.errors.target_url }}</p>
                </div>

                <!-- Media -->
                <div>
                    <label class="block text-sm font-medium text-slate-700">Media promotionnel</label>
                    <div class="mt-1 flex items-center gap-4">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-600 transition hover:border-teal-400 hover:bg-teal-50/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                            </svg>
                            <span>{{ form.media ? form.media.name : 'Choisir un fichier' }}</span>
                            <input type="file" class="hidden" accept="image/jpeg,image/png,video/mp4,video/quicktime" @change="onMediaChange" />
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">JPG, PNG, MP4 ou MOV -- max 50 Mo</p>
                    <p v-if="form.errors.media" class="mt-1 text-xs text-red-600">{{ form.errors.media }}</p>
                </div>

                <!-- Pays cibles (multi-select) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Pays cibles</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="c in countryList"
                            :key="c"
                            type="button"
                            @click="toggleCountry(c)"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-inset transition"
                            :class="form.target_country.includes(c)
                                ? 'bg-teal-50 text-teal-700 ring-teal-600/30'
                                : 'bg-slate-50 text-slate-500 ring-slate-300 hover:bg-slate-100'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="form.target_country.includes(c) ? 'bg-teal-500' : 'bg-slate-300'"></span>
                            {{ props.country_names[c] || c }}
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">Selectionnez un ou plusieurs pays.</p>
                    <p v-if="form.errors.target_country" class="mt-1 text-xs text-red-600">{{ form.errors.target_country }}</p>
                </div>

                <!-- Niche -->
                <div>
                    <label for="niche" class="block text-sm font-medium text-slate-700">Niche ciblee</label>
                    <select
                        id="niche"
                        v-model="form.niche"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                    >
                        <option value="" disabled>Selectionner une niche</option>
                        <option v-for="n in available_niches" :key="n.value" :value="n.value">{{ n.label }}</option>
                    </select>
                    <p v-if="form.errors.niche" class="mt-1 text-xs text-red-600">{{ form.errors.niche }}</p>
                </div>
            </div>

            <!-- Section 2 : Remuneration -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-5">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Remuneration CPC</h3>

                <div>
                    <label for="click_price" class="block text-sm font-medium text-slate-700">Prix par clic (FCFA)</label>
                    <input
                        id="click_price"
                        v-model.number="form.click_price"
                        type="number"
                        min="25"
                        step="5"
                        class="mt-1 block w-48 rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                    />
                    <p class="mt-1 text-xs text-slate-400">Minimum : 25 FCFA par clic</p>
                    <p v-if="form.errors.click_price" class="mt-1 text-xs text-red-600">{{ form.errors.click_price }}</p>
                </div>
            </div>

            <!-- Section 3 : Plateformes -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-5">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Reseaux sociaux cibles</h3>

                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="p in platforms"
                        :key="p.value"
                        type="button"
                        @click="togglePlatform(p.value)"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium transition"
                        :class="form.platforms.includes(p.value)
                            ? 'border-teal-500 bg-teal-50 text-teal-700 ring-1 ring-teal-500/30'
                            : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400'"
                    >
                        <svg v-if="form.platforms.includes(p.value)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        {{ p.label }}
                    </button>
                </div>
                <p v-if="form.errors.platforms" class="text-xs text-red-600">{{ form.errors.platforms }}</p>
            </div>

            <!-- Section 4 : Budget -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-5">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Budget virtuel</h3>

                <div>
                    <label for="total_budget" class="block text-sm font-medium text-slate-700">Budget total (FCFA)</label>
                    <input
                        id="total_budget"
                        v-model.number="form.total_budget"
                        type="number"
                        min="1000"
                        step="500"
                        class="mt-1 block w-48 rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                    />
                    <p class="mt-1 text-xs text-slate-400">Ce montant est virtuel et ne sera pas debite. Minimum : 1 000 FCFA</p>
                    <p v-if="form.errors.total_budget" class="mt-1 text-xs text-red-600">{{ form.errors.total_budget }}</p>
                </div>

                <div class="flex items-center gap-4">
                    <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                        Palier auto : <span class="font-semibold text-slate-900">{{ tierLabel }}</span>
                    </div>
                </div>

                <!-- Open Sea -->
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" v-model="form.open_sea" class="rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500" />
                    <div>
                        <span class="text-sm font-medium text-slate-700">Activer Open-Sea</span>
                        <p class="text-xs text-slate-400">Rend la campagne visible a tous les paliers de createurs de contenu.</p>
                    </div>
                </label>
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-teal-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-purple-500/20 transition hover:shadow-xl disabled:opacity-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                    {{ form.processing ? 'Creation en cours...' : 'Lancer la campagne officielle' }}
                </button>
                <p v-if="form.recentlySuccessful" class="text-sm text-teal-600 font-medium">Campagne creee avec succes.</p>
            </div>
        </form>
    </div>
</template>
