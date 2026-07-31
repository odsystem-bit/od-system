<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Layout from '../Layout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

defineOptions({ layout: Layout });

/**
 * Props transmises par Admin\SystemHealthController@index.
 *
 * - checks        : [{ id, label, value, status, hint }] — status : ok | warning | critical.
 * - recentErrors  : [{ time, level, message }].
 * - packages      : [{ name, version, desc }].
 * - alertHistory  : [{ time, alerts_sent, new_errors, critical_checks }].
 */
const props = defineProps({
    checks: { type: Array, default: () => [] },
    recentErrors: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    logSizeKb: { type: Number, default: 0 },
    generatedAt: { type: String, default: '' },
    alertHistory: { type: Array, default: () => [] },
    alertsEnabled: { type: Boolean, default: true },
});

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const busy = ref(false);
const lastRun = ref(null);
const showPackages = ref(false);

const summary = computed(() => ({
    ok: props.checks.filter((c) => c.status === 'ok').length,
    warning: props.checks.filter((c) => c.status === 'warning').length,
    critical: props.checks.filter((c) => c.status === 'critical').length,
}));

const statusStyles = {
    ok: 'bg-emerald-50 text-emerald-700 ring-emerald-500/30',
    warning: 'bg-amber-50 text-amber-700 ring-amber-500/30',
    critical: 'bg-red-50 text-red-700 ring-red-500/30',
};

const statusLabels = { ok: 'OK', warning: 'Alerte', critical: 'Critique' };

function statusClass(status) {
    return statusStyles[status] ?? 'bg-slate-100 text-slate-600 ring-slate-400/30';
}

const levelStyles = {
    ERROR: 'bg-red-50 text-red-700',
    CRITICAL: 'bg-red-100 text-red-800',
    ALERT: 'bg-red-100 text-red-800',
    EMERGENCY: 'bg-red-200 text-red-900',
    WARNING: 'bg-amber-50 text-amber-700',
};

function levelClass(level) {
    return levelStyles[level] ?? 'bg-slate-100 text-slate-600';
}

function formatSize(kb) {
    if (kb >= 1024) return (kb / 1024).toFixed(1) + ' Mo';
    return Math.round(kb) + ' Ko';
}

function reload() {
    router.reload({ only: ['checks', 'recentErrors', 'logSizeKb', 'generatedAt', 'alertHistory', 'alertsEnabled'] });
}

async function runCheck() {
    busy.value = true;
    try {
        const res = await fetch(route('admin.health.run-check'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        });
        lastRun.value = await res.json();
    } catch (e) {
        lastRun.value = { error: 'Verification impossible : ' + e.message };
    } finally {
        busy.value = false;
        reload();
    }
}

async function toggleAlerts() {
    busy.value = true;
    try {
        await fetch(route('admin.health.toggle-alerts'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        });
    } finally {
        busy.value = false;
        reload();
    }
}

async function clearLog() {
    if (!await ask({
        title: 'Vider le fichier de log',
        message: 'Le contenu de laravel.log sera efface definitivement. Continuer ?',
        variant: 'danger',
        confirmLabel: 'Vider le log',
    })) return;

    busy.value = true;
    try {
        await fetch(route('admin.health.clear-log'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        });
    } finally {
        busy.value = false;
        reload();
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- En-tete -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Sante du systeme</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Genere le {{ generatedAt || '\u2014' }} &middot; log : {{ formatSize(logSizeKb) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    :disabled="busy"
                    @click="runCheck"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
                >
                    {{ busy ? 'Verification\u2026' : 'Lancer une verification' }}
                </button>
                <button
                    type="button"
                    :disabled="busy"
                    @click="toggleAlerts"
                    class="rounded-xl px-4 py-2 text-sm font-semibold ring-1 transition disabled:opacity-50"
                    :class="alertsEnabled ? 'bg-emerald-50 text-emerald-700 ring-emerald-500/30 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-400/30 hover:bg-slate-200'"
                >
                    Alertes : {{ alertsEnabled ? 'activees' : 'desactivees' }}
                </button>
                <button
                    type="button"
                    :disabled="busy"
                    @click="clearLog"
                    class="rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 ring-1 ring-red-500/30 transition hover:bg-red-100 disabled:opacity-50"
                >
                    Vider le log
                </button>
            </div>
        </div>

        <!-- Resultat de la derniere verification manuelle -->
        <div v-if="lastRun" class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-700">
            <p v-if="lastRun.error" class="font-semibold text-red-700">{{ lastRun.error }}</p>
            <p v-else-if="lastRun.skipped" class="text-slate-600">Verification ignoree : {{ lastRun.reason }}</p>
            <p v-else>
                Verifiee a {{ lastRun.checked_at }} &middot;
                {{ lastRun.new_errors }} nouvelle(s) erreur(s) &middot;
                {{ lastRun.critical_checks }} controle(s) critique(s) &middot;
                {{ lastRun.alerts_sent }} alerte(s) envoyee(s)
            </p>
        </div>

        <!-- Synthese -->
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
                <p class="text-sm font-medium text-slate-500">Controles OK</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ summary.ok }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
                <p class="text-sm font-medium text-slate-500">Alertes</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.warning }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
                <p class="text-sm font-medium text-slate-500">Critiques</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ summary.critical }}</p>
            </div>
        </div>

        <!-- Controles -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Controles ({{ checks.length }})</h2>
            </div>
            <p v-if="!checks.length" class="px-5 py-8 text-center text-sm text-slate-500">Aucun controle disponible.</p>
            <ul v-else class="divide-y divide-slate-100">
                <li v-for="check in checks" :key="check.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ check.label }}</p>
                        <p class="mt-0.5 break-words text-sm text-slate-600">{{ check.value }}</p>
                        <p v-if="check.hint" class="mt-1 text-xs text-slate-400">{{ check.hint }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ring-1" :class="statusClass(check.status)">
                        {{ statusLabels[check.status] ?? check.status }}
                    </span>
                </li>
            </ul>
        </div>

        <!-- Erreurs recentes -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Erreurs recentes ({{ recentErrors.length }})</h2>
            </div>
            <p v-if="!recentErrors.length" class="px-5 py-8 text-center text-sm text-slate-500">Aucune erreur recente.</p>
            <ul v-else class="divide-y divide-slate-100">
                <li v-for="(err, i) in recentErrors" :key="i" class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded px-1.5 py-0.5 text-[10px] font-bold" :class="levelClass(err.level)">{{ err.level }}</span>
                        <span class="text-xs text-slate-400">{{ err.time }}</span>
                    </div>
                    <p class="mt-1 break-words text-sm text-slate-700">{{ err.message }}</p>
                </li>
            </ul>
        </div>

        <!-- Historique des alertes -->
        <div v-if="alertHistory.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Historique des alertes</h2>
            </div>
            <ul class="divide-y divide-slate-100">
                <li v-for="(h, i) in alertHistory" :key="i" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                    <span class="text-slate-500">{{ h.time }}</span>
                    <span class="text-slate-700">
                        {{ h.new_errors }} erreur(s) &middot; {{ h.critical_checks }} critique(s) &middot; {{ h.alerts_sent }} alerte(s)
                    </span>
                </li>
            </ul>
        </div>

        <!-- Paquets installes -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <button
                type="button"
                @click="showPackages = !showPackages"
                class="flex w-full items-center justify-between px-5 py-4 text-left"
            >
                <h2 class="text-sm font-semibold text-slate-900">Paquets installes ({{ packages.length }})</h2>
                <span class="text-sm text-slate-500">{{ showPackages ? 'Masquer' : 'Afficher' }}</span>
            </button>
            <ul v-if="showPackages" class="divide-y divide-slate-100 border-t border-slate-200">
                <li v-for="pkg in packages" :key="pkg.name" class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800">{{ pkg.name }}</p>
                        <p v-if="pkg.desc" class="text-xs text-slate-400">{{ pkg.desc }}</p>
                    </div>
                    <span class="shrink-0 font-mono text-xs text-slate-500">{{ pkg.version }}</span>
                </li>
            </ul>
        </div>

        <ConfirmModal
            :show="confirmVisible"
            :title="confirmTitle"
            :message="confirmMessage"
            :variant="confirmVariant"
            :confirm-label="confirmLabel"
            :cancel-label="cancelLabel"
            @confirmed="onConfirm"
            @cancelled="onCancel"
        />
    </div>
</template>
