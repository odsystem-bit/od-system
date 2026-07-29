<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const showPassword = ref(false);
const showConfirm = ref(false);
const ready = ref(false);
onMounted(() => { setTimeout(() => { ready.value = true; }, 80); });

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('vendor.password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950">
        <Head title="Reinitialiser le mot de passe — Espace Vendeur" />

        <!-- Animated gradient mesh -->
        <div class="absolute inset-0">
            <div class="auth-blob-1 absolute -top-1/3 -left-1/4 h-[700px] w-[700px] rounded-full bg-[radial-gradient(circle,rgba(147,51,234,0.18),transparent_70%)] blur-3xl" />
            <div class="auth-blob-2 absolute -bottom-1/4 -right-1/4 h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle,rgba(168,85,247,0.12),transparent_70%)] blur-3xl" />
            <div class="auth-blob-3 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[500px] w-[500px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.08),transparent_70%)] blur-3xl" />
        </div>
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 80px 80px;" />
        <div class="absolute inset-0 overflow-hidden">
            <div class="auth-particle absolute left-[12%] top-[18%] h-1 w-1 rounded-full bg-purple-400/50" style="animation-delay:0s" />
            <div class="auth-particle absolute left-[75%] top-[22%] h-1.5 w-1.5 rounded-full bg-teal-400/40" style="animation-delay:1.2s" />
            <div class="auth-particle absolute left-[88%] top-[65%] h-1 w-1 rounded-full bg-purple-300/30" style="animation-delay:2.4s" />
            <div class="auth-particle absolute left-[25%] top-[78%] h-1.5 w-1.5 rounded-full bg-teal-300/40" style="animation-delay:0.8s" />
        </div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-purple-500/30 to-transparent" />

        <!-- Card -->
        <div :class="['relative z-10 w-full max-w-[440px] px-4 transition-all duration-700', ready ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0']">
            <div class="rounded-3xl border border-slate-800/60 bg-slate-900/80 px-6 py-10 backdrop-blur-xl sm:px-10">
                <div class="mb-8 flex items-center gap-3">
                    <img src="/images/logo-white.png" alt="MANTOTA" class="h-9 w-auto object-contain" style="max-width:140px" />
                </div>

                <div class="mb-6">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-purple-500/20 bg-purple-500/10 px-3.5 py-1.5 text-xs font-semibold text-purple-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                        NOUVEAU MOT DE PASSE
                    </div>
                    <h1 class="bg-gradient-to-br from-white to-slate-300 bg-clip-text text-2xl font-bold tracking-tight text-transparent">Choisissez un nouveau mot de passe</h1>
                    <p class="mt-2 text-sm text-slate-500">Entrez votre nouveau mot de passe securise.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="vendor-email" class="mb-2 block text-sm font-medium text-slate-300">Adresse email</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                            </div>
                            <input
                                id="vendor-email"
                                type="email"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-3 pl-11 pr-4 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-purple-500 focus:bg-slate-800 focus:ring-2 focus:ring-purple-500/20 focus:outline-none"
                                placeholder="vendeur@email.com"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-400">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="vendor-password" class="mb-2 block text-sm font-medium text-slate-300">Nouveau mot de passe</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            </div>
                            <input
                                id="vendor-password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-3 pl-11 pr-11 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-purple-500 focus:bg-slate-800 focus:ring-2 focus:ring-purple-500/20 focus:outline-none"
                                placeholder="Nouveau mot de passe"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300 transition-colors" tabindex="-1">
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="vendor-password-confirm" class="mb-2 block text-sm font-medium text-slate-300">Confirmer le mot de passe</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <input
                                id="vendor-password-confirm"
                                :type="showConfirm ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-3 pl-11 pr-11 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-purple-500 focus:bg-slate-800 focus:ring-2 focus:ring-purple-500/20 focus:outline-none"
                                placeholder="Confirmer le mot de passe"
                            />
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300 transition-colors" tabindex="-1">
                                <svg v-if="!showConfirm" xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-400">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-purple-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-purple-500/20 transition-all hover:from-purple-500 hover:to-purple-400 hover:shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                        <span>Reinitialiser le mot de passe</span>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    <Link :href="route('vendor.login')" class="font-semibold text-purple-400 transition-colors hover:text-purple-300">
                        Retour a la connexion
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes blob-float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.05); }
    66% { transform: translate(-20px, 30px) scale(0.95); }
}
@keyframes particle-drift {
    0%, 100% { opacity: 0; transform: translateY(0); }
    50% { opacity: 1; transform: translateY(-30px); }
}
.auth-blob-1 { animation: blob-float 20s ease-in-out infinite; }
.auth-blob-2 { animation: blob-float 25s ease-in-out infinite reverse; }
.auth-blob-3 { animation: blob-float 22s ease-in-out infinite 3s; }
.auth-particle { animation: particle-drift 5s ease-in-out infinite; }
</style>