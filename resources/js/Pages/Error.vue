<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const title = computed(() => ({
    503: 'Maintenance en cours',
    500: 'Erreur serveur',
    404: 'Page introuvable',
    403: 'Acces interdit',
}[props.status] || 'Erreur'));

const description = computed(() => ({
    503: 'Notre plateforme est temporairement en maintenance. Veuillez revenir dans quelques instants.',
    500: 'Une erreur interne est survenue. Notre equipe a ete notifiee.',
    404: 'La page que vous cherchez n\'existe pas ou a ete deplacee.',
    403: 'Vous n\'avez pas les droits necessaires pour acceder a cette page.',
}[props.status] || 'Une erreur inattendue est survenue.'));
</script>

<template>
    <Head :title="`${status} — ${title}`" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4">
        <div class="text-center">
            <p class="text-8xl font-black text-teal-400">{{ status }}</p>
            <h1 class="mt-4 text-3xl font-bold text-white sm:text-4xl">{{ title }}</h1>
            <p class="mt-4 max-w-md text-lg text-slate-400">{{ description }}</p>
            <a
                href="/"
                class="mt-8 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-3 text-sm font-semibold text-white shadow transition hover:bg-teal-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Retour a l'accueil
            </a>
        </div>
    </div>
</template>
