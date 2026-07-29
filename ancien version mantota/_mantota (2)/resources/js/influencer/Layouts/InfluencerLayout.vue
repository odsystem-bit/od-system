<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '../../Components/NotificationBell.vue';
import WelcomePopup from '../../Components/WelcomePopup.vue';

const page = usePage();
const gs = computed(() => page.props.global_settings || {});
const showMobileMenu = ref(false);
const isImpersonating = computed(() => page.props.admin_impersonating ?? false);
const announcements = computed(() => page.props.announcements ?? []);
const dismissedAnnouncements = ref(JSON.parse(localStorage.getItem('dismissed_announcements') || '[]'));
const visibleAnnouncements = computed(() => (Array.isArray(announcements.value) ? announcements.value : []).filter(a => !dismissedAnnouncements.value.includes(a.id)));

function dismissAnnouncement(id) {
    dismissedAnnouncements.value.push(id);
    localStorage.setItem('dismissed_announcements', JSON.stringify(dismissedAnnouncements.value));
}

function stopImpersonating() {
    router.post(route('admin.impersonate.stop'));
}

function logout() {
    router.post(route('influencer.logout'));
}
</script>

<template>
    <div class="flex min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/30 to-cyan-50/20">

        <!-- Impersonation Banner -->
        <div v-if="isImpersonating" class="fixed inset-x-0 top-0 z-[100] flex items-center justify-center gap-3 bg-gradient-to-r from-teal-600 to-cyan-600 px-4 py-2 text-sm font-medium text-white shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span>Mode Ghost actif</span>
            <button @click="stopImpersonating" class="rounded-lg bg-white/20 px-3 py-1 text-xs font-bold transition hover:bg-white/30">Revenir Admin</button>
        </div>

        <!-- Announcement Banner -->
        <div v-for="ann in visibleAnnouncements" :key="ann.id" class="fixed inset-x-0 z-[90] flex items-center justify-center gap-3 bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow" :style="{ top: (isImpersonating ? 40 : 0) + 'px' }">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" /></svg>
            <span class="truncate">{{ ann.message }}</span>
            <button @click="dismissAnnouncement(ann.id)" class="ml-auto shrink-0 rounded-lg bg-white/20 px-2 py-0.5 text-xs transition hover:bg-white/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- ═══════════════════════════════════════════
             Sidebar — Desktop
        ═══════════════════════════════════════════ -->
        <aside class="hidden lg:flex lg:w-72 lg:flex-col lg:fixed lg:inset-y-0 z-30" :class="{ 'top-10': isImpersonating }">
            <div class="flex flex-col flex-1 min-h-0 bg-gradient-to-b from-slate-950 via-slate-900 to-teal-950 border-r border-white/5">

                <!-- Logo -->
                <div class="flex h-20 items-center gap-3 px-6 border-b border-white/10">
                    <img :src="gs.site_logo_light || '/images/logo-white.png'" alt="MANTOTA" class="h-10 w-auto object-contain" :style="{ maxWidth: (gs.logo_width || 140) + 'px', maxHeight: (gs.logo_height || 40) + 'px' }" />
                    <div class="mt-0.5">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-teal-500/20 text-teal-400 ring-1 ring-teal-400/30">Createur de Contenu</span>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 min-h-0 overflow-y-auto px-4 py-6 space-y-1.5">

                    <p class="px-3 pb-3 text-[10px] font-bold uppercase tracking-[0.2em] text-teal-500/60">Principal</p>

                    <Link
                        :href="route('influencer.dashboard')"
                        :class="route().current('influencer.dashboard')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.dashboard') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        Campagnes
                    </Link>

                    <Link
                        :href="route('influencer.links')"
                        :class="route().current('influencer.links')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.links') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                            </svg>
                        </div>
                        Mes liens
                    </Link>

                    <p class="px-3 pb-3 pt-6 text-[10px] font-bold uppercase tracking-[0.2em] text-teal-500/60">Finances</p>

                    <Link
                        :href="route('influencer.wallet.index')"
                        :class="route().current('influencer.wallet.index')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.wallet.index') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                            </svg>
                        </div>
                        Portefeuille
                    </Link>

                    <p class="px-3 pb-3 pt-6 text-[10px] font-bold uppercase tracking-[0.2em] text-teal-500/60">MANTOTA Studios</p>

                    <Link
                        :href="route('influencer.services.index')"
                        :class="route().current('influencer.services.*')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.services.*') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                            </svg>
                        </div>
                        Mes services
                    </Link>

                    <Link
                        :href="route('influencer.service-orders.index')"
                        :class="route().current('influencer.service-orders.*')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.service-orders.*') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        Commandes recues
                    </Link>

                    <p class="px-3 pb-3 pt-6 text-[10px] font-bold uppercase tracking-[0.2em] text-teal-500/60">Compte</p>

                    <Link
                        :href="route('influencer.profile.edit')"
                        :class="route().current('influencer.profile.edit')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.profile.edit') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        Mon profil
                    </Link>

                    <Link
                        :href="route('influencer.kyc.index')"
                        :class="route().current('influencer.kyc.index')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.kyc.index') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        Verification KYC
                    </Link>

                    <Link
                        :href="route('influencer.support.index')"
                        :class="route().current('influencer.support.*')
                            ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300 border-teal-400/40 shadow-sm shadow-teal-500/10'
                            : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="route().current('influencer.support.*') ? 'bg-teal-500/20' : 'bg-white/5 group-hover:bg-teal-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796l-4.138-3.448m4.138 3.448a3.736 3.736 0 011.388-.88m-5.526-.568a9.014 9.014 0 00-9.424 0m9.424 0a3.737 3.737 0 00-1.388.88M4.33 7.288l4.138 3.448M4.33 7.288a9.014 9.014 0 000 9.424m4.138-5.976a3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 2.528m0-2.528l-4.138-3.448m4.138 5.976l-4.138 3.448m4.138-3.448a3.737 3.737 0 00.88 1.388m-.88-1.388a3.737 3.737 0 01.88 1.388m0 0a9.027 9.027 0 001.306 1.652c.51.51 1.064.944 1.652 1.306m-2.958-2.958l-4.138 3.448m2.958 2.958a9.014 9.014 0 009.424 0m-9.424 0a3.737 3.737 0 001.388.88m5.078-.88a3.737 3.737 0 01-1.388.88m0 0l3.448 4.138m-3.448-4.138a3.765 3.765 0 01-2.528 0m5.976 4.138a9.014 9.014 0 000-9.424m0 9.424l-3.448-4.138" />
                            </svg>
                        </div>
                        Support
                    </Link>
                </nav>

                <!-- User section -->
                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                        <div class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-400 to-cyan-500 text-sm font-bold text-white shadow-lg shadow-teal-500/20">
                            {{ page.props.auth.user.name?.charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="truncate text-sm font-semibold text-white">{{ page.props.auth.user.name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ page.props.auth.user.email }}</p>
                        </div>
                        <button
                            @click="logout"
                            class="rounded-lg p-2 text-slate-500 transition-all duration-200 hover:bg-red-500/10 hover:text-red-400"
                            title="Deconnexion"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ═══════════════════════════════════════════
             Main content area
        ═══════════════════════════════════════════ -->
        <div class="flex flex-1 flex-col lg:pl-72">

            <!-- Top bar -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-white/10 bg-white/70 px-4 backdrop-blur-2xl sm:px-6 lg:px-8">

                <!-- Mobile menu button -->
                <button
                    @click="showMobileMenu = !showMobileMenu"
                    class="rounded-xl p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-600 lg:hidden"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Page header slot -->
                <div class="flex-1">
                    <slot name="header" />
                </div>

                <!-- Desktop role badge -->
                <div class="hidden sm:flex items-center gap-3">
                    <NotificationBell route-prefix="influencer" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-50 to-cyan-50 px-4 py-1.5 text-xs font-bold text-teal-700 ring-1 ring-teal-200/60 shadow-sm">
                        <span class="flex h-2 w-2 rounded-full bg-teal-500 shadow-sm shadow-teal-500/50"></span>
                        Espace Createur de Contenu
                    </span>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 pb-20 lg:pb-0">
                <slot />
            </main>
        </div>

        <!-- ═══════════════════════════════════════════
             Bottom Navigation Bar — Mobile
        ═══════════════════════════════════════════ -->
        <nav class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-around border-t border-slate-200 bg-white/95 backdrop-blur-xl lg:hidden" style="padding-bottom: env(safe-area-inset-bottom);">
            <Link
                :href="route('influencer.dashboard')"
                class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-medium transition"
                :class="route().current('influencer.dashboard') ? 'text-teal-600' : 'text-slate-400'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" /></svg>
                Campagnes
            </Link>
            <Link
                :href="route('influencer.links')"
                class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-medium transition"
                :class="route().current('influencer.links') ? 'text-teal-600' : 'text-slate-400'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                Mes liens
            </Link>
            <Link
                :href="route('influencer.wallet.index')"
                class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-medium transition"
                :class="route().current('influencer.wallet.*') ? 'text-teal-600' : 'text-slate-400'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" /></svg>
                Portefeuille
            </Link>
            <Link
                :href="route('influencer.profile.edit')"
                class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-medium transition"
                :class="route().current('influencer.profile.*') ? 'text-teal-600' : 'text-slate-400'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                Profil
            </Link>
            <button
                @click="showMobileMenu = !showMobileMenu"
                class="flex flex-1 flex-col items-center gap-0.5 py-2 text-[10px] font-medium text-slate-400 transition"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                Plus
            </button>
        </nav>

        <!-- ═══════════════════════════════════════════
             Mobile sidebar overlay
        ═══════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showMobileMenu" class="fixed inset-0 z-40 lg:hidden">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-md" @click="showMobileMenu = false" />

                    <!-- Panel -->
                    <div class="relative flex h-full w-80 flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-teal-950">

                        <!-- Close -->
                        <div class="absolute right-4 top-4">
                            <button @click="showMobileMenu = false" class="rounded-xl p-2 text-slate-400 transition hover:bg-white/10 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Logo -->
                        <div class="flex h-20 items-center gap-3 px-6 border-b border-white/10">
                            <img :src="gs.site_logo_light || '/images/logo-white.png'" alt="MANTOTA" class="h-10 w-auto object-contain" :style="{ maxWidth: (gs.logo_width || 140) + 'px', maxHeight: (gs.logo_height || 40) + 'px' }" />
                            <div>
                                <div class="mt-0.5">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-teal-500/20 text-teal-400 ring-1 ring-teal-400/30">Createur de Contenu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Nav items -->
                        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                            <Link
                                :href="route('influencer.dashboard')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.dashboard') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                                Campagnes
                            </Link>

                            <Link
                                :href="route('influencer.links')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.links') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                </svg>
                                Mes liens
                            </Link>

                            <Link
                                :href="route('influencer.wallet.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.wallet.index') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                                </svg>
                                Portefeuille
                            </Link>

                            <Link
                                :href="route('influencer.services.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.services.*') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                </svg>
                                Mes services
                            </Link>

                            <Link
                                :href="route('influencer.service-orders.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.service-orders.*') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                Commandes recues
                            </Link>

                            <Link
                                :href="route('influencer.profile.edit')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.profile.edit') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                Mon profil
                            </Link>

                            <Link
                                :href="route('influencer.kyc.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.kyc.index') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                Verification KYC
                            </Link>

                            <Link
                                :href="route('influencer.support.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('influencer.support.*') ? 'bg-gradient-to-r from-teal-500/20 to-cyan-500/10 text-teal-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796l-4.138-3.448m4.138 3.448a3.736 3.736 0 011.388-.88m-5.526-.568a9.014 9.014 0 00-9.424 0m9.424 0a3.737 3.737 0 00-1.388.88M4.33 7.288l4.138 3.448M4.33 7.288a9.014 9.014 0 000 9.424m4.138-5.976a3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 2.528m0-2.528l-4.138-3.448m4.138 5.976l-4.138 3.448m4.138-3.448a3.737 3.737 0 00.88 1.388m-.88-1.388a3.737 3.737 0 01.88 1.388m0 0a9.027 9.027 0 001.306 1.652c.51.51 1.064.944 1.652 1.306m-2.958-2.958l-4.138 3.448m2.958 2.958a9.014 9.014 0 009.424 0m-9.424 0a3.737 3.737 0 001.388.88m5.078-.88a3.737 3.737 0 01-1.388.88m0 0l3.448 4.138m-3.448-4.138a3.765 3.765 0 01-2.528 0m5.976 4.138a9.014 9.014 0 000-9.424m0 9.424l-3.448-4.138" />
                                </svg>
                                Support
                            </Link>
                        </nav>

                        <!-- User -->
                        <div class="border-t border-white/10 p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 text-sm font-bold text-white">
                                    {{ page.props.auth.user.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-sm font-medium text-white">{{ page.props.auth.user.name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ page.props.auth.user.email }}</p>
                                </div>
                                <button @click="logout" class="rounded-lg p-1.5 text-slate-500 hover:text-red-400" title="Deconnexion">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <WelcomePopup />
    </div>
</template>
