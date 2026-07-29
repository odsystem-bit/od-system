<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    order_id:   { type: [Number, String], required: true },
    reference:  { type: String, required: true },
    amount_paid: { type: [Number, String], required: true },
    token:      { type: String, required: true },
    status_url: { type: String, required: true },
    success_url: { type: String, required: true },
    cancel_url:  { type: String, required: true },
});

const status = ref('pending'); // pending | paid | failed | cancelled
const attempts = ref(0);
const maxAttempts = 60; // 3 min max
let pollInterval = null;

async function checkStatus() {
    try {
        const url = props.status_url + (props.status_url.includes('?') ? '&' : '?') + 'token=' + encodeURIComponent(props.token);
        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!resp.ok) return;
        const data = await resp.json();
        status.value = data.payment_status ?? 'pending';

        if (status.value === 'paid') {
            clearInterval(pollInterval);
            window.location.href = props.success_url;
        } else if (status.value === 'failed') {
            clearInterval(pollInterval);
        }
    } catch (e) {
        // ignore network errors, keep polling
    }

    attempts.value++;
    if (attempts.value >= maxAttempts) {
        clearInterval(pollInterval);
        status.value = 'timeout';
    }
}

onMounted(() => {
    // First check immediately
    checkStatus();
    // Then poll every 3 seconds
    pollInterval = setInterval(checkStatus, 3000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const formattedAmount = new Intl.NumberFormat('fr-FR').format(props.amount_paid);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-teal-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">

            <!-- Header -->
            <div class="bg-gradient-to-r from-teal-500 to-purple-600 p-6 text-center">
                <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-white/20">
                    <!-- Spinner (pending/timeout) -->
                    <svg v-if="status === 'pending' || status === 'timeout'" class="h-8 w-8 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <!-- Success check -->
                    <svg v-else-if="status === 'paid'" class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <!-- Failed X -->
                    <svg v-else class="h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-white">
                    <span v-if="status === 'pending'">Vérification du paiement…</span>
                    <span v-else-if="status === 'paid'">Paiement confirmé !</span>
                    <span v-else-if="status === 'failed'">Paiement échoué</span>
                    <span v-else-if="status === 'timeout'">Délai dépassé</span>
                    <span v-else>Traitement en cours…</span>
                </h1>
                <p class="text-white/80 text-sm mt-1">Commande {{ reference }}</p>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">

                <!-- Amount -->
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 flex justify-between items-center">
                    <span class="text-sm text-slate-600">Montant</span>
                    <span class="font-bold text-slate-900">{{ formattedAmount }} FCFA</span>
                </div>

                <!-- Status message -->
                <div v-if="status === 'pending'" class="text-center text-sm text-slate-600">
                    <p>Nous attendons la confirmation de votre paiement.</p>
                    <p class="mt-1 text-xs text-slate-400">Cette page se met à jour automatiquement. Ne la fermez pas.</p>
                </div>

                <div v-else-if="status === 'paid'" class="rounded-xl bg-green-50 border border-green-200 p-4 text-sm text-green-700 text-center">
                    Paiement confirmé. Redirection en cours…
                </div>

                <div v-else-if="status === 'failed'" class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 text-center">
                    Le paiement n'a pas abouti. Veuillez réessayer.
                </div>

                <div v-else-if="status === 'timeout'" class="rounded-xl bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-700 text-center">
                    La confirmation prend plus de temps que prévu. Vérifiez votre commande dans quelques minutes.
                </div>

                <!-- Action buttons -->
                <div class="space-y-3">
                    <a
                        v-if="status === 'failed' || status === 'timeout'"
                        :href="cancel_url"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-teal-500 px-4 py-3 text-sm font-semibold text-teal-600 transition hover:bg-teal-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                        </svg>
                        Retourner au produit
                    </a>

                    <a
                        v-if="status === 'pending'"
                        :href="cancel_url"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-500 transition hover:bg-slate-50"
                    >
                        Annuler et retourner
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
