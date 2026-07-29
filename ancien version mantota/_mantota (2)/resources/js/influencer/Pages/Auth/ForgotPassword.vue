<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('influencer.password.email'));
};
</script>

<template>
    <Head title="Mot de passe oublie - Createur de Contenu" />

    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-teal-50/30 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-slate-900">Mot de passe oublie</h1>
                <p class="mt-2 text-sm text-slate-500">
                    Indiquez votre adresse email et nous vous enverrons un lien de reinitialisation.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div
                    v-if="status"
                    class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-teal-600 hover:to-cyan-600 disabled:opacity-50"
                    >
                        Envoyer le lien de reinitialisation
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-slate-500">
                    <Link :href="route('influencer.login')" class="font-semibold text-teal-600 hover:text-teal-500">
                        Retour a la connexion
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
