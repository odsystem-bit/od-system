<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const ready = ref(false);
onMounted(() => { setTimeout(() => { ready.value = true; }, 80); });

const form = useForm({
    name: '',
    email: '',
    country: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('influencer.register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-950">
        <Head title="Inscription — Espace Createur de Contenu" />

        <div class="absolute inset-0">
            <div class="auth-blob-1 absolute -top-1/3 -left-1/4 h-[700px] w-[700px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.18),transparent_70%)] blur-3xl" />
            <div class="auth-blob-2 absolute -bottom-1/4 -right-1/4 h-[600px] w-[600px] rounded-full bg-[radial-gradient(circle,rgba(147,51,234,0.12),transparent_70%)] blur-3xl" />
            <div class="auth-blob-3 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[500px] w-[500px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.08),transparent_70%)] blur-3xl" />
        </div>
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 80px 80px;" />
        <div class="absolute inset-0 overflow-hidden">
            <div class="auth-particle absolute left-[12%] top-[18%] h-1 w-1 rounded-full bg-teal-400/50" style="animation-delay:0s" />
            <div class="auth-particle absolute left-[75%] top-[22%] h-1.5 w-1.5 rounded-full bg-purple-400/40" style="animation-delay:1.2s" />
            <div class="auth-particle absolute left-[88%] top-[55%] h-1 w-1 rounded-full bg-teal-300/30" style="animation-delay:2.4s" />
            <div class="auth-particle absolute left-[20%] top-[72%] h-1.5 w-1.5 rounded-full bg-purple-300/40" style="animation-delay:0.8s" />
            <div class="auth-particle absolute left-[55%] top-[10%] h-1 w-1 rounded-full bg-teal-400/40" style="animation-delay:3s" />
        </div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-teal-500/30 to-transparent" />

        <div class="relative z-10 flex w-full max-w-[1020px] items-stretch gap-0 px-4 py-8 sm:px-6">
            <!-- LEFT PANEL -->
            <div :class="['hidden overflow-hidden rounded-l-3xl border border-r-0 border-slate-800/60 bg-gradient-to-br from-teal-600/10 via-slate-900/80 to-slate-900/90 p-10 backdrop-blur-xl transition-all duration-700 md:flex md:w-[50%] md:flex-col md:justify-center lg:p-14', ready ? 'translate-x-0 opacity-100' : '-translate-x-8 opacity-0']">
                <div class="mb-10 flex items-center gap-3">
                    <img src="/images/logo-white.png" alt="MANTOTA" class="h-10 w-auto object-contain" style="max-width:160px" />
                </div>
                <h1 class="mb-4 bg-gradient-to-br from-white via-white to-slate-400 bg-clip-text text-3xl font-extrabold leading-tight tracking-tight text-transparent lg:text-4xl">Monetisez votre influence, gagnez a chaque clic</h1>
                <p class="mb-10 text-base leading-relaxed text-slate-400">Rejoignez des milliers de createurs africains. Partagez, generez des clics, et soyez paye en temps reel.</p>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-500/10 ring-1 ring-teal-500/20"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" /></svg></div>
                        <div><h3 class="text-sm font-semibold text-white">Liens Trackables 48h</h3><p class="text-sm text-slate-500">Generez un lien unique pour chaque campagne et suivez vos clics en temps reel.</p></div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 ring-1 ring-emerald-500/20"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg></div>
                        <div><h3 class="text-sm font-semibold text-white">Gains au CPC + CPA</h3><p class="text-sm text-slate-500">Gagnez a chaque clic ET a chaque vente generee par vos liens.</p></div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 ring-1 ring-purple-500/20"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" /></svg></div>
                        <div><h3 class="text-sm font-semibold text-white">MANTOTA Studios</h3><p class="text-sm text-slate-500">Proposez vos services UGC aux annonceurs et facturez vos talents.</p></div>
                    </div>
                </div>
                <div class="mt-10 flex items-center gap-8">
                    <div><p class="text-2xl font-bold text-white">5</p><p class="text-xs text-slate-600">Plateformes</p></div>
                    <div class="h-8 w-px bg-slate-800" />
                    <div><p class="text-2xl font-bold text-teal-400">Bronze-Or</p><p class="text-xs text-slate-600">Paliers de gains</p></div>
                    <div class="h-8 w-px bg-slate-800" />
                    <div><p class="text-2xl font-bold text-white">100%</p><p class="text-xs text-slate-600">Performance</p></div>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div :class="['flex w-full flex-col items-center justify-center rounded-3xl border border-slate-800/60 bg-slate-900/80 px-6 py-8 backdrop-blur-xl transition-all duration-700 md:w-[50%] md:rounded-l-none sm:px-8 lg:px-10', ready ? 'translate-x-0 opacity-100' : 'translate-x-8 opacity-0']" style="transition-delay:150ms">
                <div class="w-full max-w-[420px]">
                    <div class="mb-6 flex items-center gap-3 md:hidden">
                        <img src="/images/logo-white.png" alt="MANTOTA" class="h-9 w-auto object-contain" style="max-width:140px" />
                    </div>
                    <div class="mb-6">
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-500/20 bg-teal-500/10 px-3.5 py-1.5 text-xs font-semibold text-teal-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                            ESPACE CREATEUR DE CONTENU
                        </div>
                        <h1 class="bg-gradient-to-br from-white to-slate-300 bg-clip-text text-2xl font-bold tracking-tight text-transparent">Creez votre compte createur de contenu</h1>
                        <p class="mt-2 text-sm text-slate-500">Partagez, generez des clics, soyez paye</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="inf-name" class="mb-1.5 block text-sm font-medium text-slate-300">Nom complet / Pseudo</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>
                                <input id="inf-name" type="text" v-model="form.name" required autofocus autocomplete="name" class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-2.5 pl-11 pr-4 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-teal-500 focus:bg-slate-800 focus:ring-2 focus:ring-teal-500/20 focus:outline-none" placeholder="Votre nom ou pseudo" />
                            </div>
                            <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-400">{{ form.errors.name }}</p>
                        </div>
                        <!-- Email -->
                        <div>
                            <label for="inf-email" class="mb-1.5 block text-sm font-medium text-slate-300">Adresse email</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg></div>
                                <input id="inf-email" type="email" v-model="form.email" required autocomplete="username" class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-2.5 pl-11 pr-4 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-teal-500 focus:bg-slate-800 focus:ring-2 focus:ring-teal-500/20 focus:outline-none" placeholder="createur@email.com" />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-400">{{ form.errors.email }}</p>
                        </div>
                        <!-- Country + Phone -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="inf-country" class="mb-1.5 block text-sm font-medium text-slate-300">Pays</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg></div>
                                    <select id="inf-country" v-model="form.country" required class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-2.5 pl-11 pr-8 text-sm text-white shadow-sm transition-all focus:border-teal-500 focus:bg-slate-800 focus:ring-2 focus:ring-teal-500/20 focus:outline-none appearance-none">
                                        <option value="" disabled>Votre pays</option>
                                        <optgroup label="Afrique de l'Ouest">
                                            <option value="Benin">Benin</option><option value="Burkina Faso">Burkina Faso</option><option value="Cap-Vert">Cap-Vert</option><option value="Cote d'Ivoire">Cote d'Ivoire</option><option value="Gambie">Gambie</option><option value="Ghana">Ghana</option><option value="Guinee">Guinee</option><option value="Guinee-Bissau">Guinee-Bissau</option><option value="Liberia">Liberia</option><option value="Mali">Mali</option><option value="Mauritanie">Mauritanie</option><option value="Niger">Niger</option><option value="Nigeria">Nigeria</option><option value="Senegal">Senegal</option><option value="Sierra Leone">Sierra Leone</option><option value="Togo">Togo</option>
                                        </optgroup>
                                        <optgroup label="Afrique Centrale">
                                            <option value="Cameroun">Cameroun</option><option value="Centrafrique">Centrafrique</option><option value="Congo">Congo</option><option value="RD Congo">RD Congo</option><option value="Gabon">Gabon</option><option value="Guinee equatoriale">Guinee equatoriale</option><option value="Tchad">Tchad</option><option value="Sao Tome-et-Principe">Sao Tome-et-Principe</option>
                                        </optgroup>
                                        <optgroup label="Afrique de l'Est">
                                            <option value="Burundi">Burundi</option><option value="Comores">Comores</option><option value="Djibouti">Djibouti</option><option value="Erythree">Erythree</option><option value="Ethiopie">Ethiopie</option><option value="Kenya">Kenya</option><option value="Madagascar">Madagascar</option><option value="Maurice">Maurice</option><option value="Mozambique">Mozambique</option><option value="Ouganda">Ouganda</option><option value="Rwanda">Rwanda</option><option value="Seychelles">Seychelles</option><option value="Somalie">Somalie</option><option value="Soudan">Soudan</option><option value="Soudan du Sud">Soudan du Sud</option><option value="Tanzanie">Tanzanie</option>
                                        </optgroup>
                                        <optgroup label="Afrique du Nord">
                                            <option value="Algerie">Algerie</option><option value="Egypte">Egypte</option><option value="Libye">Libye</option><option value="Maroc">Maroc</option><option value="Tunisie">Tunisie</option>
                                        </optgroup>
                                        <optgroup label="Afrique Australe">
                                            <option value="Afrique du Sud">Afrique du Sud</option><option value="Angola">Angola</option><option value="Botswana">Botswana</option><option value="Eswatini">Eswatini</option><option value="Lesotho">Lesotho</option><option value="Malawi">Malawi</option><option value="Namibie">Namibie</option><option value="Zambie">Zambie</option><option value="Zimbabwe">Zimbabwe</option>
                                        </optgroup>
                                        <optgroup label="Autres">
                                            <option value="France">France</option><option value="Belgique">Belgique</option><option value="Canada">Canada</option><option value="Suisse">Suisse</option><option value="Haiti">Haiti</option><option value="Etats-Unis">Etats-Unis</option>
                                        </optgroup>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg></div>
                                </div>
                                <p v-if="form.errors.country" class="mt-1.5 text-sm text-red-400">{{ form.errors.country }}</p>
                            </div>
                            <div>
                                <label for="inf-phone" class="mb-1.5 block text-sm font-medium text-slate-300">Telephone</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg></div>
                                    <input id="inf-phone" type="tel" v-model="form.phone" required autocomplete="tel" class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-2.5 pl-11 pr-4 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-teal-500 focus:bg-slate-800 focus:ring-2 focus:ring-teal-500/20 focus:outline-none" placeholder="+229 97 00 00 00" />
                                </div>
                                <p v-if="form.errors.phone" class="mt-1.5 text-sm text-red-400">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                        <!-- Password -->
                        <div>
                            <label for="inf-password" class="mb-1.5 block text-sm font-medium text-slate-300">Mot de passe</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg></div>
                                <input id="inf-password" type="password" v-model="form.password" required autocomplete="new-password" class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-2.5 pl-11 pr-4 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-teal-500 focus:bg-slate-800 focus:ring-2 focus:ring-teal-500/20 focus:outline-none" placeholder="Minimum 8 caracteres" />
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-400">{{ form.errors.password }}</p>
                        </div>
                        <!-- Password Confirmation -->
                        <div>
                            <label for="inf-password-confirm" class="mb-1.5 block text-sm font-medium text-slate-300">Confirmer le mot de passe</label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg></div>
                                <input id="inf-password-confirm" type="password" v-model="form.password_confirmation" required autocomplete="new-password" class="block w-full rounded-xl border border-slate-700/80 bg-slate-800/60 py-2.5 pl-11 pr-4 text-sm text-white shadow-sm placeholder:text-slate-500 transition-all focus:border-teal-500 focus:bg-slate-800 focus:ring-2 focus:ring-teal-500/20 focus:outline-none" placeholder="Confirmez votre mot de passe" />
                            </div>
                            <p v-if="form.errors.password_confirmation" class="mt-1.5 text-sm text-red-400">{{ form.errors.password_confirmation }}</p>
                        </div>
                        <!-- Submit -->
                        <button type="submit" :disabled="form.processing" class="group relative flex w-full items-center justify-center gap-2.5 overflow-hidden rounded-2xl bg-gradient-to-r from-teal-500 to-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-2xl shadow-teal-500/20 transition-all hover:shadow-teal-500/40 active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50">
                            <div class="absolute inset-0 bg-gradient-to-r from-teal-400 to-emerald-400 opacity-0 transition-opacity group-hover:opacity-100" />
                            <svg v-if="!form.processing" xmlns="http://www.w3.org/2000/svg" class="relative h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" /></svg>
                            <svg v-else class="relative h-[18px] w-[18px] animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            <span v-if="!form.processing" class="relative">Creer mon compte createur de contenu</span>
                            <span v-else class="relative">Creation en cours...</span>
                        </button>
                    </form>

                    <div class="mt-5 text-center"><p class="text-sm text-slate-500">Deja un compte ? <Link :href="route('influencer.login')" class="font-semibold text-teal-400 transition-colors hover:text-teal-300">Se connecter</Link></p></div>
                    <div class="mt-4 rounded-2xl border border-slate-800/60 bg-slate-800/30 p-4 text-center"><p class="text-sm text-slate-500">Vous etes vendeur ? <Link :href="route('vendor.register')" class="font-semibold text-purple-400 transition-colors hover:text-purple-300">Inscription vendeur</Link></p></div>
                    <div class="mt-4 flex items-center justify-center gap-2 text-xs text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        <span>Inscription securisee -- MANTOTA</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-blob-1 { animation: auth-blob-a 18s ease-in-out infinite; }
.auth-blob-2 { animation: auth-blob-b 22s ease-in-out infinite; }
.auth-blob-3 { animation: auth-blob-c 20s ease-in-out infinite; }
@keyframes auth-blob-a { 0%,100%{transform:translate(0,0) scale(1)} 33%{transform:translate(50px,-30px) scale(1.06)} 66%{transform:translate(-30px,20px) scale(0.96)} }
@keyframes auth-blob-b { 0%,100%{transform:translate(0,0) scale(1)} 33%{transform:translate(-40px,40px) scale(1.1)} 66%{transform:translate(30px,-15px) scale(0.93)} }
@keyframes auth-blob-c { 0%,100%{transform:translate(-50%,-50%) scale(1)} 50%{transform:translate(-50%,-50%) scale(1.12)} }
.auth-particle { animation: auth-float 6s ease-in-out infinite; }
@keyframes auth-float { 0%,100%{transform:translateY(0) scale(1);opacity:0.5} 50%{transform:translateY(-25px) scale(1.4);opacity:1} }
</style>
