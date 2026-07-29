<script setup>
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, nextTick } from 'vue';

const props = defineProps({
    vendor:   { type: Object, required: true },
    products: { type: Array, default: () => [] },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);

// ──────────────────────────────────────────────
//  Produit cible via ?product=ID (SmartLink)
// ──────────────────────────────────────────────
const highlightedProductId = ref(null);

// Produits tries pour mettre le produit cible en premier
const sortedProducts = computed(() => {
    if (!highlightedProductId.value) return props.products;
    return [...props.products].sort((a, b) => {
        if (a.id === highlightedProductId.value) return -1;
        if (b.id === highlightedProductId.value) return 1;
        return 0;
    });
});

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const pid = params.get('product');
    if (pid) {
        highlightedProductId.value = parseInt(pid, 10);
        nextTick(() => {
            const el = document.getElementById('product-' + pid);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
});

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

// ──────────────────────────────────────────────
//  Image helpers
// ──────────────────────────────────────────────
function getImages(product) {
    if (product.images?.length) return product.images;
    if (product.image_path) return [{ path: product.image_path }];
    return [];
}

// Carousel state per product (arrows navigate through images)
const carouselIndexes = ref({});

function carouselIndex(productId) {
    return carouselIndexes.value[productId] || 0;
}
function carouselPrev(product) {
    const imgs = getImages(product);
    if (!imgs.length) return;
    const cur = carouselIndex(product.id);
    carouselIndexes.value[product.id] = cur > 0 ? cur - 1 : imgs.length - 1;
}
function carouselNext(product) {
    const imgs = getImages(product);
    if (!imgs.length) return;
    const cur = carouselIndex(product.id);
    carouselIndexes.value[product.id] = cur < imgs.length - 1 ? cur + 1 : 0;
}

// ──────────────────────────────────────────────
//  Display format → CSS classes
// ──────────────────────────────────────────────
const displayFormat = computed(() => props.vendor.shop_display_format || 'square');

const imageContainerClass = computed(() => {
    switch (displayFormat.value) {
        case 'landscape': return 'aspect-video';
        case 'portrait':  return 'aspect-[3/4]';
        default:          return 'aspect-square';
    }
});
</script>

<template>
    <Head :title="`Boutique ${vendor.business_name || vendor.name}`" />

    <div class="min-h-screen bg-gray-50">

        <!-- ═══════════════════════════════════════════
             Hero
        ═══════════════════════════════════════════ -->
        <header class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 overflow-hidden">
            <!-- Motif decoratif -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute left-1/4 top-0 h-[500px] w-[500px] rounded-full bg-teal-500 blur-[120px]" />
                <div class="absolute right-1/4 bottom-0 h-[400px] w-[400px] rounded-full bg-violet-500 blur-[100px]" />
            </div>

            <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 text-center">
                <!-- Logo vendeur -->
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm ring-1 ring-white/20 mb-6 overflow-hidden">
                    <img v-if="vendor.shop_logo_path" :src="`/storage/${vendor.shop_logo_path}`" :alt="vendor.shop_name || vendor.business_name || vendor.name" class="h-full w-full object-cover" />
                    <span v-else class="text-3xl font-bold text-white">
                        {{ (vendor.shop_name || vendor.business_name || vendor.name)?.charAt(0).toUpperCase() }}
                    </span>
                </div>

                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ vendor.shop_name || vendor.business_name || vendor.name }}
                </h1>
                <p class="mt-3 text-lg text-slate-300">
                    Decouvrez nos produits et services
                </p>

                <!-- Stats rapides -->
                <div class="mt-8 flex items-center justify-center gap-6">
                    <div class="text-center">
                        <span class="text-2xl font-bold text-white">{{ products.length }}</span>
                        <p class="text-xs text-slate-400">Produit{{ products.length > 1 ? 's' : '' }}</p>
                    </div>
                    <div class="h-8 w-px bg-slate-700" />
                    <div class="text-center">
                        <span class="text-2xl font-bold text-white">{{ products.filter(p => p.type === 'physical').length }}</span>
                        <p class="text-xs text-slate-400">Physique{{ products.filter(p => p.type === 'physical').length > 1 ? 's' : '' }}</p>
                    </div>
                    <div class="h-8 w-px bg-slate-700" />
                    <div class="text-center">
                        <span class="text-2xl font-bold text-white">{{ products.filter(p => p.type === 'digital').length }}</span>
                        <p class="text-xs text-slate-400">Digital{{ products.filter(p => p.type === 'digital').length > 1 ? 'aux' : '' }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- ═══════════════════════════════════════════
             Produits
        ═══════════════════════════════════════════ -->
        <main class="mx-auto max-w-6xl px-4 py-12 sm:px-6">

            <!-- Flash succes -->
            <div
                v-if="flashSuccess"
                class="mb-8 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800"
            >
                <!-- Heroicon: check-circle -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ flashSuccess }}</span>
            </div>

            <div v-if="products.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="product in sortedProducts"
                    :key="product.id"
                    :id="'product-' + product.id"
                    class="group flex flex-col rounded-2xl border bg-white shadow-sm transition hover:shadow-lg hover:-translate-y-1"
                    :class="highlightedProductId === product.id
                        ? 'border-purple-500 ring-2 ring-purple-300 shadow-purple-100'
                        : 'border-slate-200'"
                >
                    <!-- ── Image section — format driven by vendor.shop_display_format ── -->
                    <div class="relative overflow-hidden rounded-t-2xl bg-slate-100" :class="imageContainerClass">
                        <!-- Image avec carousel si multiple -->
                        <template v-if="getImages(product).length">
                            <img
                                :src="`/storage/${getImages(product)[carouselIndex(product.id)].path}`"
                                :alt="product.name"
                                class="h-full w-full object-cover transition group-hover:scale-105"
                            />
                            <!-- Fleches carousel (si plusieurs images) -->
                            <template v-if="getImages(product).length > 1">
                                <button
                                    @click.prevent="carouselPrev(product)"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-full bg-white/80 text-slate-700 shadow opacity-0 transition group-hover:opacity-100 hover:bg-white"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                                </button>
                                <button
                                    @click.prevent="carouselNext(product)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 flex h-7 w-7 items-center justify-center rounded-full bg-white/80 text-slate-700 shadow opacity-0 transition group-hover:opacity-100 hover:bg-white"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                </button>
                                <!-- Dots -->
                                <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
                                    <span
                                        v-for="(_, idx) in getImages(product)"
                                        :key="idx"
                                        class="h-1.5 w-1.5 rounded-full transition"
                                        :class="idx === carouselIndex(product.id) ? 'bg-white' : 'bg-white/50'"
                                    />
                                </div>
                            </template>
                        </template>
                        <!-- Placeholder si aucune image -->
                        <div v-else class="flex h-full w-full items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                            </svg>
                        </div>

                        <!-- Badge type -->
                        <div class="absolute left-3 top-3 z-10">
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold backdrop-blur-sm"
                                :class="product.type === 'physical'
                                    ? 'bg-slate-900/70 text-white'
                                    : 'bg-teal-600/80 text-white'"
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

                        <!-- Image count badge -->
                        <div v-if="getImages(product).length > 1" class="absolute right-3 top-3 z-10">
                            <span class="inline-flex items-center gap-1 rounded-full bg-black/60 px-2 py-0.5 text-xs font-medium text-white backdrop-blur-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M2.25 18V6a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v12A2.25 2.25 0 0119.5 20.25H4.5A2.25 2.25 0 012.25 18z" />
                                </svg>
                                {{ getImages(product).length }}
                            </span>
                        </div>
                    </div>

                    <!-- Contenu -->
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="text-base font-semibold text-slate-900 line-clamp-2">{{ product.name }}</h3>
                        <p class="mt-1.5 text-sm text-slate-500 line-clamp-3">{{ product.description }}</p>

                        <div class="mt-auto pt-5">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-lg font-bold text-slate-900">{{ formatCurrency(product.price) }}</span>
                                <span v-if="product.type === 'physical' && product.stock !== null" class="text-xs text-slate-400">
                                    {{ product.stock > 0 ? product.stock + ' en stock' : 'Rupture de stock' }}
                                </span>
                            </div>
                            <Link
                                :href="route('shop.checkout.show', product.id)"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="{ 'pointer-events-none opacity-50': product.type === 'physical' && product.stock === 0 }"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                                </svg>
                                {{ product.type === 'physical' && product.stock === 0 ? 'Indisponible' : 'Commander' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Etat vide -->
            <div v-else class="py-24 text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-slate-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-xl font-semibold text-slate-900">Boutique en preparation</h2>
                <p class="mt-2 text-sm text-slate-500">Ce vendeur n'a pas encore ajoute de produits.</p>
            </div>
        </main>

        <!-- ═══════════════════════════════════════════
             Footer
        ═══════════════════════════════════════════ -->
        <footer class="border-t border-slate-200 bg-white py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 text-center">
                <p class="text-xs text-slate-400">
                    Propulse par
                    <span class="font-semibold text-slate-600">MANTOTA</span>
                    — Plateforme de marketing d'influence
                </p>
            </div>
        </footer>
    </div>
</template>
