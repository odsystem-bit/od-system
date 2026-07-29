<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const gs = computed(() => page.props.global_settings || {});

const form = useForm({
    reference_code: '',
});

function submit() {
    form.post(route('support.lookup'));
}
</script>

<template>
    <Head title="Suivre un ticket" />

    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-4 sm:px-6">
                <a :href="route('home')" class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-700 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                    </div>
                    <span class="text-lg font-bold text-slate-900">{{ gs.company_name || 'MANTOTA' }}</span>
                </a>
                <a :href="route('support.create')" class="text-sm font-medium text-teal-600 hover:text-teal-700">Nouveau ticket</a>
            </div>
        </header>

        <main class="mx-auto max-w-xl px-4 py-16 sm:px-6">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50">
                    <!-- MagnifyingGlass icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900">Suivre un ticket</h1>
                <p class="mt-1 text-sm text-slate-500">Entrez votre code de reference pour consulter votre ticket.</p>
            </div>

            <form @submit.prevent="submit" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6 sm:p-8">
                    <label for="reference_code" class="block text-sm font-medium text-slate-700">Code de reference</label>
                    <input
                        id="reference_code"
                        v-model="form.reference_code"
                        type="text"
                        placeholder="TKT-XXXX"
                        class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-3 text-center text-lg font-mono tracking-wider text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        required
                    />
                    <p v-if="form.errors.reference_code" class="mt-2 text-center text-sm text-red-600">{{ form.errors.reference_code }}</p>
                </div>

                <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 sm:px-8">
                    <button type="submit" :disabled="form.processing" class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        Rechercher
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
