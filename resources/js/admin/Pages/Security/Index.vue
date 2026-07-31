<script setup>
import AdminLayout from '../Layout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    events: { type: Object, default: () => ({ data: [], links: [] }) },
    blockedIps: { type: Array, default: () => [] },
    topAttackers: { type: Array, default: () => [] },
    threatLevel: { type: String, default: 'normal' },
    filters: { type: Object, default: () => ({}) },
    trustedDevices: { type: Array, default: () => [] },
    currentDeviceToken: { type: String, default: null },
    currentIp: { type: String, default: '' },
    clickFraudStats: { type: Object, default: () => ({}) },
    recentFraudClicks: { type: Array, default: () => [] },
});

const threatConfig = {
    normal: { label: 'Normal', class: 'bg-emerald-100 text-emerald-700', icon: 'shield-check' },
    elevated: { label: 'Eleve', class: 'bg-amber-100 text-amber-700', icon: 'shield-exclamation' },
    warning: { label: 'Avertissement', class: 'bg-orange-100 text-orange-700', icon: 'shield-exclamation' },
    critical: { label: 'Critique', class: 'bg-red-100 text-red-700', icon: 'shield-exclamation' },
};

const blockForm = useForm({
    ip_address: '',
    reason: '',
    is_permanent: false,
    duration_hours: 24,
});

const deviceForm = useForm({
    device_name: '',
});

function blockIp() {
    blockForm.post(route('admin.security.block-ip'), {
        preserveScroll: true,
        onSuccess: () => blockForm.reset(),
    });
}

function unblockIp(id) {
    if (confirm('Debloquer cette IP ?')) {
        router.delete(route('admin.security.unblock-ip', id));
    }
}

function addTrustedDevice() {
    deviceForm.post(route('admin.security.trusted-device.add'), {
        preserveScroll: true,
        onSuccess: () => deviceForm.reset(),
    });
}

function removeTrustedDevice(id) {
    if (confirm('Retirer cet appareil de confiance ?')) {
        router.delete(route('admin.security.trusted-device.remove', id));
    }
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

const fraudStats = props.clickFraudStats || {};
</script>

<template>
    <Head title="Securite — Administration" />
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Panneau de Securite</h1>
                <p class="mt-1 text-sm text-slate-500">Monitorer les intrusions, bloquer des IPs et gerer les appareils de confiance.</p>
            </div>

            <!-- Threat Level + Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-slate-500">Niveau de menace</p>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold" :class="threatConfig[threatLevel]?.class">{{ threatConfig[threatLevel]?.label }}</span>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Tentatives echouees (24h)</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.failed_logins ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Brute force detectes</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ stats.brute_force ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">IPs bloquees</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.blocked_ips ?? 0 }}</p>
                </div>
            </div>

            <!-- Click Fraud Stats -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Anti-fraude clics (24h)</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-600">{{ fraudStats.vpn_blocked ?? 0 }}</p>
                        <p class="text-xs text-slate-500">VPN bloques</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-600">{{ fraudStats.bots_blocked ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Bots bloques</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-amber-600">{{ fraudStats.device_duplicates ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Duplicatas appareil</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-amber-600">{{ fraudStats.ip_duplicates ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Duplicatas IP</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-emerald-600">{{ fraudStats.total_valid ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Clics valides</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-600">{{ fraudStats.total_blocked ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Clics bloques</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-900">{{ fraudStats.unique_devices ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Appareils uniques</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-amber-600">{{ fraudStats.geo_mismatches ?? 0 }}</p>
                        <p class="text-xs text-slate-500">Geo incoherents</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Block IP -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-900 mb-4">Bloquer une IP</h3>
                    <form @submit.prevent="blockIp" class="space-y-4">
                        <input v-model="blockForm.ip_address" type="text" placeholder="Adresse IP (ex: 192.168.1.1)"
                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" />
                        <input v-model="blockForm.reason" type="text" placeholder="Raison du blocage"
                            class="block w-full rounded-xl border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" />
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input v-model="blockForm.is_permanent" type="checkbox" class="rounded border-slate-300 text-red-600" />
                                Permanent
                            </label>
                            <input v-if="!blockForm.is_permanent" v-model.number="blockForm.duration_hours" type="number" min="1" max="8760"
                                class="w-24 rounded-xl border-slate-300 text-sm" placeholder="heures" />
                        </div>
                        <p v-if="blockForm.errors.ip_address" class="text-sm text-red-600">{{ blockForm.errors.ip_address }}</p>
                        <button type="submit" :disabled="blockForm.processing"
                            class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">
                            Bloquer
                        </button>
                    </form>

                    <!-- Blocked IPs list -->
                    <div class="mt-6 space-y-2">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase">IPs actuellement bloquees ({{ blockedIps.length }})</h4>
                        <div v-for="ip in blockedIps" :key="ip.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <div>
                                <span class="font-mono font-semibold text-slate-900">{{ ip.ip_address }}</span>
                                <span class="ml-2 text-xs text-slate-500">{{ ip.reason }}</span>
                                <span v-if="ip.expires_at" class="ml-2 text-xs text-amber-600">Expire: {{ formatDate(ip.expires_at) }}</span>
                            </div>
                            <button @click="unblockIp(ip.id)" class="text-xs font-medium text-red-600 hover:text-red-800">Debloquer</button>
                        </div>
                        <p v-if="!blockedIps.length" class="text-sm text-slate-400">Aucune IP bloquee.</p>
                    </div>
                </div>

                <!-- Trusted Devices -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
                    <h3 class="text-sm font-bold text-slate-900 mb-4">Appareils de confiance</h3>
                    <form @submit.prevent="addTrustedDevice" class="flex gap-2 mb-4">
                        <input v-model="deviceForm.device_name" type="text" placeholder="Nom de l'appareil"
                            class="flex-1 rounded-xl border-slate-300 text-sm" />
                        <button type="submit" :disabled="deviceForm.processing"
                            class="rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                            Ajouter
                        </button>
                    </form>
                    <div class="space-y-2">
                        <div v-for="device in trustedDevices" :key="device.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <div>
                                <span class="font-medium text-slate-900">{{ device.device_name }}</span>
                                <p class="text-xs text-slate-400">{{ device.ip_address }} - {{ formatDate(device.last_used_at) }}</p>
                            </div>
                            <button @click="removeTrustedDevice(device.id)" class="text-xs font-medium text-red-600 hover:text-red-800">Retirer</button>
                        </div>
                        <p v-if="!trustedDevices.length" class="text-sm text-slate-400">Aucun appareil de confiance.</p>
                    </div>
                </div>
            </div>

            <!-- Top Attackers -->
            <div v-if="topAttackers.length" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Top attaquants (24h)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tentatives</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Derniere</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="attacker in topAttackers" :key="attacker.ip_address" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm font-mono text-slate-900">{{ attacker.ip_address }}</td>
                                <td class="px-4 py-3 text-sm font-bold text-red-600">{{ attacker.attempts }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(attacker.last_attempt) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Security Events -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Evenements de securite recents</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Utilisateur</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="event in (events.data || [])" :key="event.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="event.event_type === 'login_failed' ? 'bg-red-100 text-red-700' : event.event_type === 'ip_blocked_manual' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-700'">
                                        {{ event.event_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ event.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-mono text-slate-500">{{ event.ip_address }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(event.created_at) }}</td>
                            </tr>
                            <tr v-if="!(events.data || []).length">
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">Aucun evenement.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="events.links && events.links.length > 3" class="flex items-center justify-between border-t px-4 py-3">
                    <div class="flex gap-1">
                        <button v-for="(link, i) in events.links" :key="i" :disabled="!link.url" @click="router.visit(link.url)" v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-sm" :class="link.active ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-100'" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
