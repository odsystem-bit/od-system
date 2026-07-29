<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Offre indisponible" />

    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
        <div class="w-full max-w-md text-center">
            <!-- Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>

            <h1 class="mt-6 text-xl font-bold text-slate-900">Offre indisponible</h1>
            <p class="mt-3 text-sm text-slate-500">
                Cette offre MANTOTA est actuellement indisponible ou terminee.
            </p>

            <a
                href="/"
                class="mt-8 inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
            >
                Retour a l'accueil
            </a>
        </div>
    </div>
</template>
