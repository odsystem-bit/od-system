<script setup>
import VendorLayout from '../../Layouts/VendorLayout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    shopDisplayFormat: {
        type: String,
        default: 'square',
    },
});

// ──────────────────────────────────────────────
//  Mini-Site — Partage
// ──────────────────────────────────────────────

const page = usePage();
const shopUrl = computed(() => {
    const slug = page.props.auth?.user?.slug;
    return slug ? route('shop.show', slug) : null;
});

const copied = ref(false);

function copyLink() {
    if (!shopUrl.value) return;
    navigator.clipboard.writeText(shopUrl.value).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}

// ──────────────────────────────────────────────
//  Format d'affichage boutique
// ──────────────────────────────────────────────

const activeFormat = ref(props.shopDisplayFormat);
const savingFormat = ref(false);

const formats = [
    { value: 'square',    label: 'Carre',    ratio: 'aspect-square' },
    { value: 'landscape', label: 'Paysage',  ratio: 'aspect-video' },
    { value: 'portrait',  label: 'Portrait', ratio: 'aspect-[3/4]' },
];

function selectFormat(value) {
    if (value === activeFormat.value) return;
    activeFormat.value = value;
    savingFormat.value = true;
    router.put(route('vendor.products.display-format'), {
        shop_display_format: value,
    }, {
        preserveScroll: true,
        onFinish: () => (savingFormat.value = false),
    });
}

// ──────────────────────────────────────────────
//  Suppression
// ──────────────────────────────────────────────

const deletingId = ref(null);

async function confirmDelete(product) {
    if (!await ask({ title: 'Supprimer le produit', message: `Supprimer le produit "${product.name}" ? Cette action est irreversible.`, variant: 'danger', confirmLabel: 'Supprimer' })) return;
    deletingId.value = product.id;
    router.delete(route('vendor.products.destroy', product.id), {
        preserveScroll: true,
        onFinish: () => (deletingId.value = null),
    });
}

// ──────────────────────────────────────────────
//  Helpers
// ──────────────────────────────────────────────

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v) + ' FCFA';
}
</script>

<template>
    <Head title="Mes produits" />

    <VendorLayout>
        <div class="space-y-6">

            <!-- ─────────────────────────────────────
                 Banniere Mini-Site Boutique
            ───────────────────────────────────── -->
            <div v-if="shopUrl" class="rounded-xl border border-purple-100 bg-purple-50 p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-purple-900">Votre Mini-Site Boutique est en ligne</h2>
                            <p class="mt-0.5 text-xs text-purple-700/80">
                                Partagez ce lien sur WhatsApp, Facebook ou a vos createurs de contenu pour generer des ventes.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- URL + Actions -->
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.04a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.343 8.28" />
                        </svg>
                        <span class="truncate text-sm text-slate-700">{{ shopUrl }}</span>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button
                            type="button"
                            @click="copyLink"
                            class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-white px-4 py-2 text-xs font-semibold transition"
                            :class="copied ? 'text-emerald-600 border-emerald-300 bg-emerald-50' : 'text-purple-700 hover:bg-purple-100'"
                        >
                            <svg v-if="!copied" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            {{ copied ? 'Copie !' : 'Copier le lien' }}
                        </button>
                        <a
                            :href="shopUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            Voir ma boutique
                        </a>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Mes produits</h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Gerez vos produits physiques et digitaux.
                    </p>
                </div>
                <Link
                    :href="route('vendor.products.create')"
                    class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un produit
                </Link>
            </div>

            <!-- ─────────────────────────────────────
                 Format d'affichage boutique
            ───────────────────────────────────── -->
            <div v-if="products.length" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                            Format d'affichage des produits
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-500">Choisissez la forme des images dans votre boutique publique.</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            v-for="fmt in formats"
                            :key="fmt.value"
                            type="button"
                            :disabled="savingFormat"
                            class="flex flex-col items-center gap-2 rounded-xl border-2 px-4 py-3 transition disabled:opacity-50"
                            :class="activeFormat === fmt.value
                                ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-500/20'
                                : 'border-slate-200 bg-white hover:border-slate-300'"
                            @click="selectFormat(fmt.value)"
                        >
                            <div
                                class="rounded bg-slate-200 transition"
                                :class="[
                                    fmt.ratio,
                                    activeFormat === fmt.value ? 'bg-purple-400' : 'bg-slate-300',
                                    fmt.value === 'square' ? 'w-10' : fmt.value === 'landscape' ? 'w-14' : 'w-8',
                                ]"
                            ></div>
                            <span class="text-xs font-medium" :class="activeFormat === fmt.value ? 'text-purple-700' : 'text-slate-600'">{{ fmt.label }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────
                 Liste des produits
            ───────────────────────────────────── -->
            <div v-if="products.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="product in products"
                    :key="product.id"
                    class="group relative flex flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm transition-all duration-500 hover:shadow-xl hover:shadow-purple-500/5"
                >
                    <!-- Image -->
                    <div class="relative h-44 overflow-hidden rounded-t-xl bg-slate-100">
                        <img
                            v-if="product.images?.length"
                            :src="`/storage/${product.images[0].path}`"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                        <img
                            v-else-if="product.image_path"
                            :src="`/storage/${product.image_path}`"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                            </svg>
                        </div>

                        <!-- Badge nombre d'images -->
                        <span v-if="product.images?.length > 1" class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-semibold text-white backdrop-blur-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                            </svg>
                            {{ product.images.length }}
                        </span>

                        <!-- Badge type -->
                        <div class="absolute left-3 top-3">
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold backdrop-blur-sm"
                                :class="product.type === 'physical'
                                    ? 'bg-slate-900/70 text-white'
                                    : 'bg-purple-600/80 text-white'"
                            >
                                <svg v-if="product.type === 'physical'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                </svg>
                                {{ product.type === 'physical' ? 'Physique' : 'Digital' }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex flex-1 flex-col p-4">
                        <h3 class="text-sm font-semibold text-slate-900 line-clamp-2">{{ product.name }}</h3>
                        <p class="mt-1 text-xs text-slate-500 line-clamp-2">{{ product.description }}</p>

                        <!-- Infos -->
                        <div class="mt-auto pt-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900">{{ formatCurrency(product.price) }}</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 ring-1 ring-purple-600/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                    </svg>
                                    {{ product.commission_percent }}%
                                </span>
                            </div>

                            <div v-if="product.type === 'physical' && product.stock !== null" class="text-xs text-slate-500">
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    {{ product.stock }} en stock
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                            <Link
                                :href="route('vendor.products.edit', product.id)"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                                Modifier
                            </Link>
                            <button
                                @click="confirmDelete(product)"
                                :disabled="deletingId === product.id"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                            >
                                <svg v-if="deletingId !== product.id" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                <svg v-else class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────
                 Etat vide
            ───────────────────────────────────── -->
            <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Aucun produit</h3>
                <p class="mt-1.5 text-sm text-slate-500">
                    Commencez par ajouter votre premier produit physique ou digital.
                </p>
                <Link
                    :href="route('vendor.products.create')"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-600 hover:to-violet-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Ajouter un produit
                </Link>
            </div>

        </div>
    </VendorLayout>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
