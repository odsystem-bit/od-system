<script setup>
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    available_niches: { type: Array, required: true },
});

// ── Suppression de compte ──
const showDeleteModal = ref(false);
const deleteForm = useForm({ password: '' });

function confirmDelete() {
    deleteForm.delete(route('influencer.account.destroy'), {
        onSuccess: () => { showDeleteModal.value = false; },
        onError: () => {},
    });
}

// ── Photo de profil ──
const photoPreview = ref(null);
const photoInput = ref(null);

function selectPhoto() {
    photoInput.value.click();
}

function onPhotoSelected(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => { photoPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
}

function uploadPhoto() {
    if (!photoInput.value?.files[0]) return;
    router.post(route('influencer.profile.photo.update'), {
        photo: photoInput.value.files[0],
    }, {
        preserveScroll: true,
        onSuccess: () => { photoPreview.value = null; },
    });
}

const form = useForm({
    niches:              props.user.niches || [],
    tiktok_url:          props.user.tiktok_url || '',
    tiktok_followers:    props.user.tiktok_followers || 0,
    instagram_url:       props.user.instagram_url || '',
    instagram_followers: props.user.instagram_followers || 0,
    facebook_url:        props.user.facebook_url || '',
    facebook_followers:  props.user.facebook_followers || 0,
    youtube_url:         props.user.youtube_url || '',
    youtube_followers:   props.user.youtube_followers || 0,
    snapchat_url:        props.user.snapchat_url || '',
    snapchat_followers:  props.user.snapchat_followers || 0,
});

const isVip = computed(() => props.user.is_vip);

const MAX_NICHES = 3;
const nichesAtMax = computed(() => form.niches.length >= MAX_NICHES);

function toggleNiche(value) {
    const idx = form.niches.indexOf(value);
    if (idx > -1) {
        form.niches.splice(idx, 1);
    } else if (form.niches.length < MAX_NICHES) {
        form.niches.push(value);
    }
}

const platforms = [
    { key: 'tiktok',    label: 'TikTok',    placeholder: 'https://tiktok.com/@votre-profil' },
    { key: 'instagram', label: 'Instagram',  placeholder: 'https://instagram.com/votre-profil' },
    { key: 'facebook',  label: 'Facebook',   placeholder: 'https://facebook.com/votre-page' },
    { key: 'youtube',   label: 'YouTube',    placeholder: 'https://youtube.com/@votre-chaine' },
    { key: 'snapchat',  label: 'Snapchat',   placeholder: 'https://snapchat.com/add/votre-profil' },
];

function submit() {
    form.put(route('influencer.profile.socials.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Mon profil" />

    <InfluencerLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-slate-800">
                    Mon profil
                </h2>
                <Link
                    :href="route('influencer.dashboard')"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">

                <!-- ═══════════════════════════════════════════
                     Photo de profil
                ═══════════════════════════════════════════ -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                            </svg>
                            Photo de profil
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Ajoutez une photo qui sera visible par les vendeurs et sur votre profil public.
                        </p>
                    </div>

                    <div class="flex items-center gap-6">
                        <!-- Avatar preview -->
                        <div class="relative shrink-0">
                            <img
                                v-if="photoPreview"
                                :src="photoPreview"
                                alt="Apercu"
                                class="h-24 w-24 rounded-full object-cover ring-2 ring-teal-200"
                            />
                            <img
                                v-else-if="user.profile_photo"
                                :src="`/storage/${user.profile_photo}`"
                                alt="Photo de profil"
                                class="h-24 w-24 rounded-full object-cover ring-2 ring-slate-200"
                            />
                            <div
                                v-else
                                class="flex h-24 w-24 items-center justify-center rounded-full bg-teal-100 text-2xl font-bold text-teal-600 ring-2 ring-slate-200"
                            >
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>

                        <div class="space-y-3">
                            <input
                                ref="photoInput"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/webp"
                                class="hidden"
                                @change="onPhotoSelected"
                            />
                            <button
                                type="button"
                                @click="selectPhoto"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                Choisir une photo
                            </button>
                            <button
                                v-if="photoPreview"
                                type="button"
                                @click="uploadPhoto"
                                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-teal-500 to-cyan-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-lg hover:shadow-teal-500/30"
                            >
                                Enregistrer la photo
                            </button>
                            <p class="text-xs text-slate-400">JPG, PNG ou WebP. Max 2 Mo.</p>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     Badge Statut VIP
                ═══════════════════════════════════════════ -->
                <div
                    class="flex items-center gap-4 rounded-xl border px-5 py-4"
                    :class="isVip
                        ? 'border-teal-200 bg-gradient-to-r from-indigo-50 to-amber-50'
                        : 'border-slate-200 bg-slate-50'"
                >
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                        :class="isVip ? 'bg-teal-100' : 'bg-slate-200'"
                    >
                        <!-- Heroicon: star (VIP) / user (Standard) -->
                        <svg v-if="isVip" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3
                                class="text-sm font-bold"
                                :class="isVip ? 'text-teal-800' : 'text-slate-700'"
                            >
                                {{ isVip ? 'Statut VIP UGC' : 'Statut Standard' }}
                            </h3>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="isVip
                                    ? 'bg-teal-100 text-teal-700 ring-1 ring-inset ring-teal-600/20'
                                    : 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-300'"
                            >
                                {{ isVip ? 'VIP' : 'Standard' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm" :class="isVip ? 'text-teal-600' : 'text-slate-500'">
                            <template v-if="isVip">
                                Vous avez acces aux campagnes UGC et aux missions de creation de contenu.
                            </template>
                            <template v-else>
                                Renseignez vos reseaux sociaux pour etre eligible au statut VIP.
                            </template>
                        </p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     Selection des Niches (max 3)
                ═══════════════════════════════════════════ -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                            </svg>
                            Mes niches de contenu
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Selectionnez jusqu'a <span class="font-semibold text-slate-700">3 niches</span> correspondant a votre contenu.
                            Seules les campagnes ciblant vos niches apparaitront dans votre tableau de bord.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label
                            v-for="niche in available_niches"
                            :key="niche.value"
                            class="flex items-center gap-3 rounded-lg border p-3 transition cursor-pointer"
                            :class="[
                                form.niches.includes(niche.value)
                                    ? 'border-teal-300 bg-teal-50 ring-1 ring-teal-200'
                                    : nichesAtMax
                                        ? 'border-slate-200 bg-slate-50 opacity-50 cursor-not-allowed'
                                        : 'border-slate-200 bg-white hover:border-teal-200 hover:bg-teal-50/30',
                            ]"
                        >
                            <input
                                type="checkbox"
                                :value="niche.value"
                                :checked="form.niches.includes(niche.value)"
                                :disabled="nichesAtMax && !form.niches.includes(niche.value)"
                                class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                @change="toggleNiche(niche.value)"
                            />
                            <span
                                class="text-sm font-medium"
                                :class="form.niches.includes(niche.value) ? 'text-teal-800' : 'text-slate-700'"
                            >
                                {{ niche.label }}
                            </span>
                        </label>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <p class="text-xs text-slate-400">
                            {{ form.niches.length }} / {{ MAX_NICHES }} niches selectionnees
                        </p>
                        <p v-if="form.errors.niches" class="text-xs text-red-600">{{ form.errors.niches }}</p>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     Formulaire Reseaux Sociaux
                ═══════════════════════════════════════════ -->
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                            Reseaux sociaux et abonnes
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Renseignez vos profils et le nombre d'abonnes pour chaque plateforme.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Un bloc par reseau social -->
                        <div
                            v-for="platform in platforms"
                            :key="platform.key"
                            class="rounded-lg border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex items-center gap-2 mb-3">
                                <!-- TikTok -->
                                <svg v-if="platform.key === 'tiktok'" class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.46V13.2a8.2 8.2 0 005.58 2.17V12a4.85 4.85 0 01-3.44-1.44 4.83 4.83 0 01-1.42-3.44h3.45c-.01.53.08 1.04.24 1.52a4.82 4.82 0 001.17 1.65V6.69z"/>
                                </svg>
                                <!-- Instagram -->
                                <svg v-else-if="platform.key === 'instagram'" class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="2" y="2" width="20" height="20" rx="5" />
                                    <circle cx="12" cy="12" r="5" />
                                    <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none" />
                                </svg>
                                <!-- Facebook -->
                                <svg v-else-if="platform.key === 'facebook'" class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <!-- YouTube -->
                                <svg v-else-if="platform.key === 'youtube'" class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                                <!-- Snapchat -->
                                <svg v-else class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12.999-.262a.747.747 0 01.266-.063c.12 0 .237.024.36.071.36.12.51.367.51.59a.588.588 0 01-.39.546 4.907 4.907 0 01-.838.3c-.12.036-.238.075-.36.12a2.593 2.593 0 00-.96.63c-.12.15-.18.33-.18.54 0 .09.012.18.036.27.124.47.361.9.643 1.29.106.14.219.27.339.39.571.59 1.32 1.05 2.159 1.35.3.1.541.196.791.3a.585.585 0 01.359.539c0 .27-.12.51-.36.66a2.72 2.72 0 01-1.35.57c-.15.02-.24.12-.27.27a1.001 1.001 0 01-.06.27c-.03.063-.06.12-.09.18-.06.09-.15.12-.27.12h-.06c-.54-.06-1.05-.24-1.597-.36-.36-.06-.72-.09-1.08.06-.36.15-.72.42-.96.72-.3.36-.66.66-1.08.87a3.482 3.482 0 01-1.59.39c-.54 0-1.08-.15-1.59-.39a3.7 3.7 0 01-1.08-.87c-.24-.3-.6-.57-.96-.72-.36-.15-.72-.12-1.08-.06-.54.12-1.05.3-1.59.36h-.06c-.12 0-.21-.03-.27-.12a.696.696 0 01-.09-.18 1.064 1.064 0 01-.06-.27c-.03-.15-.12-.24-.27-.27-.51-.09-.99-.27-1.35-.57a.72.72 0 01-.36-.66c0-.24.12-.45.36-.54.24-.09.48-.18.78-.3.84-.3 1.59-.75 2.16-1.35.12-.12.24-.24.33-.39.29-.39.52-.82.65-1.29.024-.09.036-.18.036-.27 0-.21-.06-.39-.18-.54a2.593 2.593 0 00-.96-.63c-.12-.045-.24-.084-.36-.12a4.838 4.838 0 01-.84-.3.59.59 0 01-.39-.546c0-.224.15-.47.51-.59a.98.98 0 01.36-.071c.09 0 .18.015.27.063.33.14.69.246.99.262.18 0 .33-.045.4-.09a41.68 41.68 0 01-.03-.51l-.004-.06c-.104-1.628-.23-3.654.3-4.847C7.86 1.069 11.216.793 12.206.793z"/>
                                </svg>
                                <span class="text-sm font-semibold text-slate-800">{{ platform.label }}</span>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <!-- URL du profil -->
                                <div>
                                    <label :for="`${platform.key}_url`" class="block text-xs font-medium text-slate-500 mb-1">
                                        Lien du profil
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                                            </svg>
                                        </div>
                                        <input
                                            :id="`${platform.key}_url`"
                                            v-model="form[`${platform.key}_url`]"
                                            type="url"
                                            :placeholder="platform.placeholder"
                                            class="block w-full rounded-lg border-slate-300 pl-10 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        />
                                    </div>
                                    <p v-if="form.errors[`${platform.key}_url`]" class="mt-1 text-xs text-red-600">{{ form.errors[`${platform.key}_url`] }}</p>
                                </div>

                                <!-- Nombre d'abonnes -->
                                <div>
                                    <label :for="`${platform.key}_followers`" class="block text-xs font-medium text-slate-500 mb-1">
                                        Nombre d'abonnes
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                            </svg>
                                        </div>
                                        <input
                                            :id="`${platform.key}_followers`"
                                            v-model.number="form[`${platform.key}_followers`]"
                                            type="number"
                                            min="0"
                                            placeholder="0"
                                            class="block w-full rounded-lg border-slate-300 pl-10 shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                        />
                                    </div>
                                    <p v-if="form.errors[`${platform.key}_followers`]" class="mt-1 text-xs text-red-600">{{ form.errors[`${platform.key}_followers`] }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Note explicative -->
                        <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-slate-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Vos statistiques seront verifiees par l'administration avant l'activation de votre statut VIP.
                                Les profils avec un nombre significatif d'abonnes et un contenu de qualite seront eligibles aux campagnes UGC.
                            </p>
                        </div>

                        <!-- Bouton de soumission -->
                        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-teal-500 to-cyan-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-lg hover:shadow-teal-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.processing ? 'Enregistrement...' : 'Enregistrer mes reseaux' }}
                            </button>
                            <p
                                v-if="form.recentlySuccessful"
                                class="text-sm text-emerald-600 font-medium"
                            >
                                Modifications enregistrees.
                            </p>
                        </div>
                    </form>
                </div>

            </div>

            <!-- ═══ Zone de Danger ═══ -->
            <div class="rounded-2xl border border-red-200/80 bg-white p-6 shadow-sm">
                <h3 class="flex items-center gap-2 text-base font-bold text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    Zone de Danger
                </h3>
                <p class="mt-2 text-sm text-slate-600">
                    La suppression de votre compte est irreversible. Toutes vos donnees personnelles seront supprimees.
                </p>
                <button
                    @click="showDeleteModal = true"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    Supprimer mon compte
                </button>
            </div>

        </div>

        <!-- ═══ Modal confirmation suppression ═══ -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showDeleteModal = false">
                    <div class="w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xl">
                        <h3 class="text-lg font-bold text-slate-900">Confirmer la suppression</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            Cette action est irreversible. Entrez votre mot de passe pour confirmer la suppression de votre compte.
                        </p>
                        <div class="mt-4">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Mot de passe</label>
                            <input
                                v-model="deleteForm.password"
                                type="password"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                                placeholder="Votre mot de passe"
                                @keyup.enter="confirmDelete"
                            />
                            <p v-if="deleteForm.errors.password" class="mt-1 text-xs text-red-600">{{ deleteForm.errors.password }}</p>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button @click="showDeleteModal = false; deleteForm.reset()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Annuler</button>
                            <button
                                @click="confirmDelete"
                                :disabled="deleteForm.processing"
                                class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:opacity-50"
                            >
                                {{ deleteForm.processing ? 'Suppression...' : 'Supprimer definitivement' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </InfluencerLayout>
</template>
