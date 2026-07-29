<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    gateways: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const editing = ref(null);
const form = ref({});

function startEdit(gw) {
    editing.value = gw.id;
    form.value = {
        is_active: gw.is_active,
        public_key: gw.public_key ?? '',
        secret_key: gw.secret_key ?? '',
        webhook_secret: gw.webhook_secret ?? '',
        environment: gw.environment ?? 'sandbox',
    };
}

function cancelEdit() {
    editing.value = null;
    form.value = {};
}

function save(gwId) {
    router.put(route('admin.gateways.update', gwId), form.value, {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Passerelles de paiement</h1>
            <p class="mt-1 text-sm text-slate-500">Activez et configurez les cles API de chaque passerelle.</p>
        </div>

        <!-- Flash -->
        <div v-if="flash.success" class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">
            {{ flash.success }}
        </div>

        <!-- Gateway cards -->
        <div class="grid gap-6 md:grid-cols-2">
            <div v-for="gw in gateways" :key="gw.id" class="rounded-xl border bg-white p-6" :class="gw.is_active ? 'border-teal-200' : 'border-slate-200'">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg" :class="gw.is_active ? 'bg-teal-50' : 'bg-slate-100'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="gw.is_active ? 'text-teal-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">{{ gw.name }}</h3>
                            <p class="text-xs text-slate-500">{{ gw.slug }}</p>
                        </div>
                    </div>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="gw.is_active ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-500'">
                        {{ gw.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>

                <!-- View mode -->
                <div v-if="editing !== gw.id" class="mt-5 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Environnement</span>
                        <span class="font-medium text-slate-700">{{ gw.environment === 'live' ? 'Production' : 'Sandbox' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Cle publique</span>
                        <span class="font-mono text-slate-600">{{ gw.public_key ? '****' + gw.public_key.slice(-8) : '--' }}</span>
                    </div>
                    <button @click="startEdit(gw)" class="mt-4 w-full rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                        Configurer
                    </button>
                </div>

                <!-- Edit mode -->
                <div v-else class="mt-5 space-y-4">
                    <!-- Active toggle -->
                    <label class="flex items-center gap-3">
                        <input type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                        <span class="text-sm text-slate-700">Passerelle active</span>
                    </label>

                    <!-- Environment -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Environnement</label>
                        <select v-model="form.environment" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-teal-500 focus:ring-teal-500">
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Production (Live)</option>
                        </select>
                    </div>

                    <!-- Public key -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Cle publique</label>
                        <input type="text" v-model="form.public_key" placeholder="pk_..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-teal-500 focus:ring-teal-500" />
                    </div>

                    <!-- Secret key -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Cle secrete</label>
                        <input type="password" v-model="form.secret_key" placeholder="sk_..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-teal-500 focus:ring-teal-500" />
                    </div>

                    <!-- Webhook secret -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Secret Webhook</label>
                        <input type="password" v-model="form.webhook_secret" placeholder="whsec_..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-teal-500 focus:ring-teal-500" />
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-2">
                        <button @click="save(gw.id)" class="flex-1 rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-500">
                            Enregistrer
                        </button>
                        <button @click="cancelEdit" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
