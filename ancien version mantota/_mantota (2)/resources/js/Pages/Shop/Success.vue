<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    order: { type: Object, required: true },
});

/**
 * Nettoie un numero de telephone pour wa.me : ne garde que les chiffres.
 */
function cleanPhone(phone) {
    if (!phone) return '';
    return phone.replace(/[^0-9]/g, '');
}

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v) + ' FCFA';
}

const isDigital = props.order.product?.type === 'digital';

/**
 * Construit le lien WhatsApp Click-to-Chat vers le vendeur.
 */
function whatsappLink() {
    const vendorPhone = cleanPhone(props.order.vendor?.phone ?? '');
    if (!vendorPhone) return null;

    const message = isDigital
        ? 'Bonjour ! Je viens d\'acheter '
            + (props.order.product?.name ?? 'un produit digital')
            + ' (Ref: ' + props.order.id + ') sur votre boutique MANTOTA. '
            + "J'ai paye " + props.order.amount_paid + ' FCFA. Merci !'
        : 'Bonjour ! Je viens de commander '
            + (props.order.product?.name ?? 'un produit')
            + ' (Ref: ' + props.order.id + ') sur votre boutique MANTOTA. '
            + "J'ai paye " + props.order.amount_paid + ' FCFA. '
            + 'Merci de me confirmer la livraison a ' + props.order.city + ' !';

    return 'https://wa.me/' + vendorPhone + '?text=' + encodeURIComponent(message);
}
</script>

<template>
    <Head title="Commande confirmee" />

    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-white to-purple-50">
        <!-- Header minimaliste -->
        <header class="border-b border-slate-200 bg-white/80 backdrop-blur-sm">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
                <span class="text-lg font-bold tracking-tight text-slate-900">MANTOTA</span>
                <Link
                    v-if="order.vendor?.slug"
                    :href="route('shop.show', order.vendor.slug)"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-600 transition hover:text-teal-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Retour a la boutique
                </Link>
            </div>
        </header>

        <!-- Contenu -->
        <main class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
            <!-- Icone de succes -->
            <div class="flex justify-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 ring-8 ring-emerald-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Message de remerciement -->
            <div class="mt-6 text-center">
                <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Commande confirmee !</h1>
                <p class="mt-3 text-base text-slate-600 leading-relaxed">
                    Merci pour votre achat. Votre paiement est securise par le systeme MANTOTA.
                    Le vendeur sera notifie immediatement.
                </p>
            </div>

            <!-- CODE SECRET DE LIVRAISON (produits physiques uniquement) -->
            <div v-if="!isDigital && order.delivery_pin" class="mt-8 overflow-hidden rounded-2xl border-2 border-red-300 bg-red-50 shadow-sm">
                <div class="px-5 py-6 text-center">
                    <p class="text-sm font-bold uppercase tracking-wide text-red-700">Code Secret de Livraison</p>
                    <p class="mt-3 text-6xl font-black tracking-[0.3em] text-red-600 sm:text-7xl">{{ order.delivery_pin }}</p>
                </div>
                <div class="border-t border-red-200 bg-red-100/60 px-5 py-4">
                    <div class="flex gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-red-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <p class="text-sm font-semibold text-red-800 leading-relaxed">
                            Prenez une capture d'ecran de ce code a 4 chiffres. Il vous sera demande pour valider la reception de votre colis.
                            <span class="font-bold underline">Ne le donnez pas au vendeur avant la livraison !</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Resume de la commande -->
            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                    <h2 class="text-sm font-bold text-slate-800">Resume de la commande</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    <!-- Reference -->
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Reference</span>
                        <span class="text-sm font-bold text-slate-900">{{ order.reference }}</span>
                    </div>

                    <!-- Produit -->
                    <div class="flex items-center justify-between gap-3 px-5 py-3">
                        <span class="text-sm text-slate-500">Produit</span>
                        <div class="flex items-center gap-2 text-right">
                            <div v-if="order.product?.image_path" class="h-8 w-8 shrink-0 overflow-hidden rounded-md">
                                <img :src="`/storage/${order.product.image_path}`" :alt="order.product.name" class="h-full w-full object-cover" />
                            </div>
                            <span class="text-sm font-semibold text-slate-900 line-clamp-1">{{ order.product?.name }}</span>
                        </div>
                    </div>

                    <!-- Montant paye -->
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Montant paye</span>
                        <span class="text-base font-bold text-emerald-600">{{ formatCurrency(order.amount_paid) }}</span>
                    </div>

                    <!-- Livraison (physique) -->
                    <div v-if="!isDigital" class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Ville de livraison</span>
                        <span class="text-sm font-medium text-slate-900">{{ order.city }}</span>
                    </div>

                    <!-- Type (digital) -->
                    <div v-if="isDigital" class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Type</span>
                        <span class="text-sm font-medium text-teal-600">Produit digital — acces immediat</span>
                    </div>

                    <!-- Statut escrow -->
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm text-slate-500">Statut du paiement</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            Fonds en escrow — securises
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bouton Acceder au produit digital -->
            <div v-if="isDigital && order.product?.access_url" class="mt-8">
                <a
                    :href="order.product.access_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex w-full items-center justify-center gap-3 rounded-2xl bg-teal-600 px-6 py-4 text-base font-bold text-white shadow-lg transition hover:bg-teal-700 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-teal-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Acceder a mon produit digital
                </a>
                <p class="mt-2 text-center text-xs text-slate-500">
                    Conservez ce lien pour acceder a votre produit a tout moment.
                </p>
            </div>

            <!-- Bouton WhatsApp : Envoyer mon recu au vendeur -->
            <div v-if="whatsappLink()" class="mt-8">
                <a
                    :href="whatsappLink()"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex w-full items-center justify-center gap-3 rounded-2xl bg-emerald-600 px-6 py-4 text-base font-bold text-white shadow-lg transition hover:bg-emerald-700 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-200"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Envoyer mon recu au Vendeur
                </a>
                <p class="mt-3 text-center text-xs text-slate-500">
                    Ce bouton ouvre WhatsApp avec un message pre-rempli pour le vendeur.
                </p>
            </div>

            <!-- Fallback si pas de telephone vendeur -->
            <div v-else class="mt-8 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 text-center">
                <p class="text-sm text-slate-600">
                    Le vendeur sera contacte automatiquement. Vous pouvez aussi le joindre depuis sa boutique.
                </p>
            </div>

            <!-- Lien magique de suivi de commande -->
            <div v-if="order.tracking_token" class="mt-4">
                <a
                    :href="route('order.track', order.id) + '?token=' + order.tracking_token"
                    class="flex w-full items-center justify-center gap-3 rounded-2xl border-2 border-teal-600 bg-white px-6 py-4 text-base font-bold text-teal-600 shadow-sm transition hover:bg-teal-50 focus:outline-none focus:ring-4 focus:ring-teal-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    Suivre ma commande
                </a>
                <p class="mt-2 text-center text-xs text-slate-500">
                    Conservez ce lien pour suivre l'avancement de votre commande et confirmer la reception.
                </p>
            </div>

            <!-- Informations complementaires -->
            <div class="mt-6 rounded-xl border border-purple-200 bg-purple-50 px-5 py-4">
                <div class="flex gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-purple-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <div v-if="isDigital" class="space-y-1 text-sm text-purple-800">
                        <p class="font-semibold">Produit digital</p>
                        <ul class="space-y-1 text-purple-700">
                            <li class="flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Votre achat est confirme et securise par MANTOTA.
                            </li>
                            <li class="flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Vous pouvez acceder a votre produit immediatement via le bouton ci-dessus.
                            </li>
                            <li class="flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Conservez votre lien de suivi pour retrouver votre achat a tout moment.
                            </li>
                        </ul>
                    </div>
                    <div v-else class="space-y-1 text-sm text-purple-800">
                        <p class="font-semibold">Comment ca marche ?</p>
                        <ul class="space-y-1 text-purple-700">
                            <li class="flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Votre argent est bloque en escrow (securise par MANTOTA).
                            </li>
                            <li class="flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Le vendeur prepare et expedie votre colis.
                            </li>
                            <li class="flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                A la reception, la livraison est confirmee et les fonds sont liberes.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
