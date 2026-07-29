<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    shop_name: { type: String, default: null },
    shop_logo_path: { type: String, default: null },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);

// ── Branding form ──
const brandingForm = useForm({
    shop_name: props.shop_name || '',
    shop_logo: null,
});

const logoPreview = ref(props.shop_logo_path ? `/storage/${props.shop_logo_path}` : null);

function onLogoChange(e) {
    const file = e.target.files[0];
    if (file) {
        brandingForm.shop_logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
}

function submitBranding() {
    brandingForm.post(route('vendor.settings.branding'), {
        preserveScroll: true,
        forceFormData: true,
    });
}

// ── Delete account ──
const showDeleteModal = ref(false);
const deleteForm = useForm({ password: '' });

function confirmDelete() {
    deleteForm.delete(route('vendor.account.destroy'), {
        onSuccess: () => { showDeleteModal.value = false; },
        onError: () => {},
    });
}
</script>

<template>
    <Head title="Parametres" />

    <VendorLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-slate-500 to-slate-600 shadow-lg shadow-slate-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Parametres</h1>
                    <p class="text-sm text-slate-500">Gerez les parametres de votre compte.</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-2xl space-y-6">

            <!-- Flash success -->
            <div
                v-if="flashSuccess"
                class="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="font-medium">{{ flashSuccess }}</span>
            </div>

            <!-- Branding Boutique -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <h3 class="flex items-center gap-2 text-base font-bold text-slate-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
                    Branding de la boutique
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    Personnalisez l'apparence de votre boutique avec un nom et un logo.
                </p>

                <form @submit.prevent="submitBranding" class="mt-5 space-y-5">
                    <!-- Nom de la boutique -->
                    <div>
                        <label for="shop_name" class="block text-sm font-medium text-slate-700 mb-1">
                            Nom de la boutique
                        </label>
                        <input
                            id="shop_name"
                            v-model="brandingForm.shop_name"
                            type="text"
                            placeholder="Ex: Ma Super Boutique"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                        />
                        <p class="mt-1 text-xs text-slate-400">
                            Si vide, votre nom commercial ou votre nom personnel sera utilise.
                        </p>
                        <p v-if="brandingForm.errors.shop_name" class="mt-1 text-sm text-red-600">{{ brandingForm.errors.shop_name }}</p>
                    </div>

                    <!-- Logo de la boutique -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Logo de la boutique
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-slate-50">
                                <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-full w-full object-cover" />
                                <div v-else class="flex h-full w-full items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" /></svg>
                                </div>
                            </div>
                            <div>
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                    Choisir un fichier
                                    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onLogoChange" />
                                </label>
                                <p class="mt-1 text-xs text-slate-400">JPEG, PNG ou WebP. Max 2 Mo.</p>
                            </div>
                        </div>
                        <p v-if="brandingForm.errors.shop_logo" class="mt-1 text-sm text-red-600">{{ brandingForm.errors.shop_logo }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="brandingForm.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 disabled:opacity-50"
                    >
                        <svg v-if="brandingForm.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ brandingForm.processing ? 'Enregistrement...' : 'Enregistrer le branding' }}
                    </button>
                </form>
            </div>

            <!-- Zone de Danger -->
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

        <!-- Modal confirmation suppression -->
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
    </VendorLayout>
</template>
