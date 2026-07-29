<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '../../Components/NotificationBell.vue';
import WelcomePopup from '../../Components/WelcomePopup.vue';

const page = usePage();
const gs = computed(() => page.props.global_settings || {});
const showMobileMenu = ref(false);
const isImpersonating = computed(() => page.props.admin_impersonating ?? false);

// ── Flash messages globaux ──
const flash = computed(() => page.props.flash || {});
const flashVisible = ref(false);
const flashMsg = ref('');
const flashType = ref('success');

import { watch } from 'vue';
watch(() => page.props.flash, (f) => {
    if (!f) return;
    if (f.success) { flashMsg.value = f.success; flashType.value = 'success'; flashVisible.value = true; }
    else if (f.error) { flashMsg.value = f.error; flashType.value = 'error'; flashVisible.value = true; }
    else if (f.warning) { flashMsg.value = f.warning; flashType.value = 'warning'; flashVisible.value = true; }
    if (flashVisible.value) setTimeout(() => { flashVisible.value = false; }, 5000);
}, { immediate: true, deep: true });
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
    router.post(route('vendor.logout'));
}
</script>

<template>
    <div class="flex min-h-screen bg-gradient-to-br from-slate-50 via-purple-50/30 to-violet-50/20">

        <!-- Impersonation Banner -->
        <div v-if="isImpersonating" class="fixed inset-x-0 top-0 z-[100] flex items-center justify-center gap-3 bg-gradient-to-r from-violet-600 to-purple-600 px-4 py-2 text-sm font-medium text-white shadow-lg">
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
            <div class="flex flex-col flex-1 bg-gradient-to-b from-slate-950 via-slate-900 to-purple-950 border-r border-white/5">

                <!-- Logo -->
                <div class="flex h-20 items-center gap-3 px-6 border-b border-white/10">
                    <img :src="gs.site_logo_light || '/images/logo-white.png'" alt="MANTOTA" class="h-10 w-auto object-contain" :style="{ maxWidth: (gs.logo_width || 140) + 'px', maxHeight: (gs.logo_height || 40) + 'px' }" />
                    <div class="mt-0.5">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-purple-500/20 text-purple-400 ring-1 ring-purple-400/30">Vendeur</span>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1.5">

                    <Link
                        :href="route('vendor.dashboard')"
                        :class="route().current('vendor.dashboard') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        Tableau de bord
                    </Link>

                    <Link
                        :href="route('vendor.wallet.index')"
                        :class="route().current('vendor.wallet.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                        </svg>
                        Portefeuille
                    </Link>

                    <Link
                        :href="route('vendor.campaigns.index')"
                        :class="route().current('vendor.campaigns.index') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Creer campagne
                    </Link>

                    <Link
                        :href="route('vendor.products.index')"
                        :class="route().current('vendor.products.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        Mes produits
                    </Link>

                    <Link
                        :href="route('vendor.orders.index')"
                        :class="route().current('vendor.orders.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                        </svg>
                        Commandes
                    </Link>

                    <Link
                        :href="route('vendor.service-orders.index')"
                        :class="route().current('vendor.service-orders.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Studios
                    </Link>

                    <Link
                        :href="route('vendor.kyc.index')"
                        :class="route().current('vendor.kyc.index') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Verification KYC
                    </Link>

                    <Link
                        :href="route('vendor.settings')"
                        :class="route().current('vendor.settings') ? 'bg-gradient-to-r from-slate-500/20 to-slate-400/10 text-slate-300 border-slate-400/40 shadow-sm shadow-slate-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Parametres
                    </Link>

                    <Link
                        :href="route('vendor.support.index')"
                        :class="route().current('vendor.support.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300 border-purple-400/40 shadow-sm shadow-purple-500/10' : 'text-slate-400 hover:bg-white/5 hover:text-white border-transparent'"
                        class="group flex items-center gap-3 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796l-4.138-3.448m4.138 3.448a3.736 3.736 0 011.388-.88m-5.526-.568a9.014 9.014 0 00-9.424 0m9.424 0a3.737 3.737 0 00-1.388.88M4.33 7.288l4.138 3.448M4.33 7.288a9.014 9.014 0 000 9.424m4.138-5.976a3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 2.528m0-2.528l-4.138-3.448m4.138 5.976l-4.138 3.448m4.138-3.448a3.737 3.737 0 00.88 1.388m-.88-1.388a3.737 3.737 0 01.88 1.388m0 0a9.027 9.027 0 001.306 1.652c.51.51 1.064.944 1.652 1.306m-2.958-2.958l-4.138 3.448m2.958 2.958a9.014 9.014 0 009.424 0m-9.424 0a3.737 3.737 0 001.388.88m5.078-.88a3.737 3.737 0 01-1.388.88m0 0l3.448 4.138m-3.448-4.138a3.765 3.765 0 01-2.528 0m5.976 4.138a9.014 9.014 0 000-9.424m0 9.424l-3.448-4.138" />
                        </svg>
                        Support
                    </Link>
                </nav>

                <!-- User section -->
                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                        <div class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg shadow-purple-500/20">
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

            <!-- Top bar (mobile + desktop) -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-white/10 bg-white/70 px-4 backdrop-blur-2xl sm:px-6 lg:px-8">

                <!-- Mobile menu button -->
                <button
                    @click="showMobileMenu = !showMobileMenu"
                    class="rounded-xl p-2 text-slate-500 transition hover:bg-purple-50 hover:text-purple-600 lg:hidden"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Page header slot -->
                <div class="flex-1">
                    <slot name="header" />
                </div>

                <!-- Desktop user badge -->
                <div class="hidden sm:flex items-center gap-3">
                    <NotificationBell route-prefix="vendor" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-50 to-violet-50 px-4 py-1.5 text-xs font-bold text-purple-700 ring-1 ring-purple-200/60 shadow-sm">
                        <span class="flex h-2 w-2 rounded-full bg-purple-500 shadow-sm shadow-purple-500/50"></span>
                        Espace Vendeur
                    </span>
                </div>
            </header>

            <!-- Flash Messages Global -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="-translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-2 opacity-0"
            >
                <div v-if="flashVisible" class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                    <div class="flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm"
                        :class="flashType === 'success' ? 'border-green-200 bg-green-50 text-green-800' : flashType === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800'">
                        <svg v-if="flashType === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else-if="flashType === 'error'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5-2.239 2.374-3.948 2.374H6.645c-1.71 0-3.082-.874-3.948-2.374S1.831 9.376 2.697 7.876L10.053.378c.866-1.5 3.032-1.5 3.898 0l7.353 7.498c.866 1.5.217 3.374-1.948 3.374z" /></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <span class="flex-1">{{ flashMsg }}</span>
                        <button @click="flashVisible = false" class="shrink-0 rounded-lg p-1 transition hover:bg-black/5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Page content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>

        <!-- ═══════════════════════════════════════════
             Mobile sidebar overlay
        ═══════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showMobileMenu" class="fixed inset-0 z-40 lg:hidden">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-md" @click="showMobileMenu = false" />

                    <!-- Panel -->
                    <div class="relative flex h-full w-80 flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-purple-950">

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
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-purple-500/20 text-purple-400 ring-1 ring-purple-400/30">Vendeur</span>
                                </div>
                            </div>
                        </div>

                        <!-- Nav items -->
                        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1.5">
                            <Link
                                :href="route('vendor.dashboard')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.dashboard') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                </svg>
                                Tableau de bord
                            </Link>

                            <Link
                                :href="route('vendor.wallet.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.wallet.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 013 6v3" />
                                </svg>
                                Portefeuille
                            </Link>

                            <Link
                                :href="route('vendor.campaigns.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.campaigns.index') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Creer campagne
                            </Link>

                            <Link
                                :href="route('vendor.products.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.products.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                Mes produits
                            </Link>

                            <Link
                                :href="route('vendor.orders.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.orders.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                                </svg>
                                Commandes
                            </Link>

                            <Link
                                :href="route('vendor.service-orders.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.service-orders.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                Studios
                            </Link>

                            <Link
                                :href="route('vendor.kyc.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.kyc.index') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                Verification KYC
                            </Link>

                            <Link
                                :href="route('vendor.settings')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.settings') ? 'bg-gradient-to-r from-slate-500/20 to-slate-400/10 text-slate-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Parametres
                            </Link>

                            <Link
                                :href="route('vendor.support.index')"
                                @click="showMobileMenu = false"
                                :class="route().current('vendor.support.*') ? 'bg-gradient-to-r from-purple-500/20 to-violet-500/10 text-purple-300' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                                class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 2.528m-2.268-4.796l-4.138-3.448m4.138 3.448a3.736 3.736 0 011.388-.88m-5.526-.568a9.014 9.014 0 00-9.424 0m9.424 0a3.737 3.737 0 00-1.388.88M4.33 7.288l4.138 3.448M4.33 7.288a9.014 9.014 0 000 9.424m4.138-5.976a3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 2.528m0-2.528l-4.138-3.448m4.138 5.976l-4.138 3.448m4.138-3.448a3.737 3.737 0 00.88 1.388m-.88-1.388a3.737 3.737 0 01.88 1.388m0 0a9.027 9.027 0 001.306 1.652c.51.51 1.064.944 1.652 1.306m-2.958-2.958l-4.138 3.448m2.958 2.958a9.014 9.014 0 009.424 0m-9.424 0a3.737 3.737 0 001.388.88m5.078-.88a3.737 3.737 0 01-1.388.88m0 0l3.448 4.138m-3.448-4.138a3.765 3.765 0 01-2.528 0m5.976 4.138a9.014 9.014 0 000-9.424m0 9.424l-3.448-4.138" />
                                </svg>
                                Support
                            </Link>
                        </nav>

                        <!-- User -->
                        <div class="border-t border-white/10 p-4">
                            <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-sm font-bold text-white shadow-lg shadow-purple-500/20">
                                    {{ page.props.auth.user.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="truncate text-sm font-semibold text-white">{{ page.props.auth.user.name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ page.props.auth.user.email }}</p>
                                </div>
                                <button @click="logout" class="rounded-lg p-2 text-slate-500 transition hover:bg-red-500/10 hover:text-red-400" title="Deconnexion">
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
