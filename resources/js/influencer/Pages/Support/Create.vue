<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';

defineOptions({ layout: InfluencerLayout });

const form = useForm({
    subject: '',
    category: 'question',
    message: '',
});

function submit() {
    form.post(route('influencer.support.store'));
}
</script>

<template>
    <Head title="Nouveau ticket" />

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <!-- Back -->
            <a :href="route('influencer.support.index')" class="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Retour aux tickets
            </a>

            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796l-4.138-3.448m4.138 3.448a3.736 3.736 0 011.388-.88m-5.526-.568a9.014 9.014 0 00-9.424 0m9.424 0a3.737 3.737 0 00-1.388.88M4.33 7.288l4.138 3.448M4.33 7.288a9.014 9.014 0 000 9.424m4.138-5.976a3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 2.528m0-2.528l-4.138-3.448m4.138 5.976l-4.138 3.448m4.138-3.448a3.737 3.737 0 00.88 1.388m-.88-1.388a3.737 3.737 0 01.88 1.388m0 0a9.027 9.027 0 001.306 1.652c.51.51 1.064.944 1.652 1.306m-2.958-2.958l-4.138 3.448m2.958 2.958a9.014 9.014 0 009.424 0m-9.424 0a3.737 3.737 0 001.388.88m5.078-.88a3.737 3.737 0 01-1.388.88m0 0l3.448 4.138m-3.448-4.138a3.765 3.765 0 01-2.528 0m5.976 4.138a9.014 9.014 0 000-9.424m0 9.424l-3.448-4.138" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Nouveau ticket</h1>
                    <p class="text-sm text-slate-500">Decrivez votre probleme, notre equipe vous repondra rapidement.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="space-y-5 p-6">
                    <!-- Subject -->
                    <div>
                        <label for="subject" class="block text-sm font-medium text-slate-700">Sujet</label>
                        <input id="subject" v-model="form.subject" type="text" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" required />
                        <p v-if="form.errors.subject" class="mt-1 text-xs text-red-600">{{ form.errors.subject }}</p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-slate-700">Categorie</label>
                        <select id="category" v-model="form.category" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                            <option value="question">Question generale</option>
                            <option value="bug">Signaler un bug</option>
                            <option value="paiement">Probleme de paiement</option>
                        </select>
                    </div>

                    <!-- Message -->
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700">Message</label>
                        <textarea id="message" v-model="form.message" rows="5" class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" required></textarea>
                        <p v-if="form.errors.message" class="mt-1 text-xs text-red-600">{{ form.errors.message }}</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
