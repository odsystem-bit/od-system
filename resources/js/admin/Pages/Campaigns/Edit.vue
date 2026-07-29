<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    campaign: { type: Object, required: true },
    available_niches: { type: Array, required: true },
    countries: { type: Array, required: true },
});

const form = useForm({
    title: props.campaign.title,
    target_url: props.campaign.target_url,
    click_price: props.campaign.click_price,
    budget_change: 0,
    _method: 'put',
});

function fmt(n) {
    return new Intl.NumberFormat('fr-FR').format(n);
}

const projectedBudget = computed(() => {
    return Math.max(0, props.campaign.total_budget + (Number(form.budget_change) || 0));
});

const projectedRemaining = computed(() => {
    return Math.max(0, props.campaign.remaining_budget + (Number(form.budget_change) || 0));
});

function submit() {
    form.post(route('admin.campaigns.update', props.campaign.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Editer campagne systeme" />

    <div class="mx-auto max-w-3xl space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link
                :href="route('admin.campaigns.index')"
                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </Link>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                    <!-- Heroicon: Pencil -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-800">Editer la campagne</h1>
                    <p class="text-xs text-slate-500">{{ campaign.title }}</p>
                </div>
            </div>
        </div>

        <!-- Budget Banner (read-only) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <!-- Heroicon: ShieldExclamation -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                <span class="text-sm font-semibold text-slate-700">Budget virtuel (monnaie marketing)</span>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Budget total</p>
                    <p class="text-lg font-bold text-slate-800">{{ fmt(projectedBudget) }} F</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Restant</p>
                    <p class="text-lg font-bold" :class="projectedRemaining > 0 ? 'text-teal-700' : 'text-red-600'">{{ fmt(projectedRemaining) }} F</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Statut</p>
                    <p class="text-lg font-bold text-purple-700 capitalize">{{ campaign.status }}</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
            <!-- Titre -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Titre</label>
                <input
                    v-model="form.title"
                    type="text"
                    class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                />
                <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
            </div>

            <!-- Lien de destination -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Lien de destination</label>
                <input
                    v-model="form.target_url"
                    type="url"
                    class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                />
                <p v-if="form.errors.target_url" class="mt-1 text-xs text-red-600">{{ form.errors.target_url }}</p>
            </div>

            <!-- CPC -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">CPC (Cout Par Clic) -- FCFA</label>
                <input
                    v-model.number="form.click_price"
                    type="number"
                    min="25"
                    step="5"
                    class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                />
                <p v-if="form.errors.click_price" class="mt-1 text-xs text-red-600">{{ form.errors.click_price }}</p>
            </div>

            <!-- Ajustement budget -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Ajustement budget virtuel (FCFA)</label>
                <p class="text-xs text-slate-500 mb-2">Positif pour ajouter, negatif pour retirer. Laisser 0 pour ne pas modifier.</p>
                <input
                    v-model.number="form.budget_change"
                    type="number"
                    step="500"
                    class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                />
                <p v-if="form.errors.budget_change" class="mt-1 text-xs text-red-600">{{ form.errors.budget_change }}</p>
            </div>

            <!-- Info read-only -->
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Pays cible</p>
                    <p class="text-sm font-semibold text-slate-700">{{ Array.isArray(campaign.target_country) ? campaign.target_country.join(', ') : campaign.target_country }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Niche</p>
                    <p class="text-sm font-semibold text-slate-700">{{ campaign.niche }}</p>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link
                    :href="route('admin.campaigns.index')"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                >
                    Annuler
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</template>
