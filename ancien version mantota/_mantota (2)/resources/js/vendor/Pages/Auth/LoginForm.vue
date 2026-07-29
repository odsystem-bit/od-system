<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import PremiumButton from '@/Components/Button/PremiumButton.vue';

defineProps({
    status: { type: String, default: '' },
    allowRegister: { type: Boolean, default: false },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('vendor.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Connectez-vous</h2>
            <p class="text-slate-500">Accédez a votre tableau de bord vendeur</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

            <!-- Status message -->
            <div
                v-if="status"
                class="mb-6 flex items-center gap-2.5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ status }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Email -->
                <div>
                    <label for="vendor-email" class="block text-sm font-medium text-slate-700 mb-2">
                        Email
                    </label>
                    <input
                        id="vendor-email"
                        type="email"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="vous@example.com"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 focus:outline-none transition-all"
                        :class="{ 'border-red-500 ring-2 ring-red-500/10': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <label for="vendor-password" class="block text-sm font-medium text-slate-700 mb-2">
                        Mot de passe
                    </label>
                    <input
                        id="vendor-password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 focus:outline-none transition-all"
                        :class="{ 'border-red-500 ring-2 ring-red-500/10': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Remember -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="form.remember"
                            class="h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500"
                        />
                        <span class="text-slate-600">Se souvenir de moi</span>
                    </label>
                </div>

                <!-- Submit -->
                <PremiumButton
                    variant="primary"
                    type="submit"
                    size="md"
                    :disabled="form.processing"
                    class="w-full"
                >
                    <svg
                        v-if="!form.processing"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                    {{ form.processing ? 'Connexion...' : 'Se connecter' }}
                </PremiumButton>

            </form>

            <!-- Divider -->
            <div class="relative mt-8 mb-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200" />
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white px-2 text-slate-500">Nouvel utilisateur?</span>
                </div>
            </div>

            <!-- Register link -->
            <Link
                v-if="allowRegister"
                :href="route('vendor.register')"
                class="block w-full px-4 py-3 text-center rounded-xl border-2 border-purple-600 text-purple-600 font-semibold hover:bg-purple-50 transition-colors"
            >
                Creer un compte
            </Link>

            <!-- Home link (no register) -->
            <Link
                v-else
                :href="route('home')"
                class="flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900 text-sm font-medium transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Revenir a l'accueil
            </Link>

        </div>
    </div>
</template>
