<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: String, default: '' },
});

const form = useForm({});

const submit = () => {
    form.post(route('influencer.verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verification email" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-cyan-50 px-4 py-12">
        <div class="w-full max-w-md space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-xl">
            <!-- Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-teal-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
            </div>

            <div class="text-center">
                <h1 class="text-xl font-bold text-slate-900">Verifiez votre adresse email</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Merci pour votre inscription ! Veuillez verifier votre adresse email en
                    cliquant sur le lien que nous venons de vous envoyer. Si vous n'avez pas
                    recu l'email, nous pouvons vous en renvoyer un.
                </p>
            </div>

            <div v-if="verificationLinkSent" class="rounded-lg bg-teal-50 px-4 py-3 text-center text-sm font-medium text-teal-700">
                Un nouveau lien de verification a ete envoye a votre adresse email.
            </div>

            <form @submit.prevent="submit">
                <div class="flex items-center justify-between gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50"
                    >
                        Renvoyer le lien
                    </button>

                    <Link
                        :href="route('influencer.logout')"
                        method="post"
                        as="button"
                        class="text-sm font-medium text-slate-500 transition hover:text-slate-700"
                    >
                        Se deconnecter
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
