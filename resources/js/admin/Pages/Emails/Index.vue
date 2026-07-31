<script setup>
import AdminLayout from '../Layout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    history: { type: Object, default: () => ({ data: [], links: [] }) },
    templates: { type: Object, default: () => ({}) },
    mergeTags: { type: Object, default: () => ({}) },
});

const selectedTemplate = ref('personnalise');
const showHistory = ref(false);

const form = useForm({
    subject: '',
    body: '',
    target_role: 'all',
    template: 'personnalise',
});

function applyTemplate(key) {
    selectedTemplate.value = key;
    form.template = key;
    const tpl = props.templates[key];
    if (tpl) {
        form.subject = tpl.subject;
        form.body = tpl.body;
    }
}

function sendEmail() {
    form.post(route('admin.emails.send'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('subject', 'body');
            selectedTemplate.value = 'personnalise';
        },
    });
}

function insertTag(tag) {
    form.body = (form.body || '') + ' ' + tag;
}

const recipientPreview = computed(() => {
    const labels = { all: 'Tous les utilisateurs', vendor: 'Vendeurs uniquement', influencer: 'Createurs de contenu uniquement' };
    return labels[form.target_role] || 'Tous';
});

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Emails de masse — Administration" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Emails de masse</h1>
                    <p class="mt-1 text-sm text-slate-500">Envoyez des emails a vos utilisateurs (vendeurs et/ou createurs de contenu).</p>
                </div>
                <button
                    @click="showHistory = !showHistory"
                    class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ showHistory ? 'Masquer' : 'Historique' }}
                </button>
            </div>

            <!-- History -->
            <div v-if="showHistory" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Historique des envois</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Sujet</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cible</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Destinataires</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Envoye par</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="email in (history.data || [])" :key="email.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-900 max-w-xs truncate">{{ email.subject }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="email.target_role === 'all' ? 'bg-purple-100 text-purple-700' : email.target_role === 'vendor' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'">
                                        {{ email.target_role === 'all' ? 'Tous' : email.target_role === 'vendor' ? 'Vendeurs' : 'Createurs' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ email.recipients_count }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ email.sender?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(email.sent_at) }}</td>
                            </tr>
                            <tr v-if="!(history.data || []).length">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">Aucun email envoye pour le moment.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="history.links && history.links.length > 3" class="flex items-center justify-between border-t border-slate-200 px-4 py-3">
                    <div class="flex gap-1">
                        <button
                            v-for="(link, i) in history.links"
                            :key="i"
                            :disabled="!link.url"
                            @click="router.visit(link.url)"
                            v-html="link.label"
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="link.active ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                        />
                    </div>
                </div>
            </div>

            <!-- Composer -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Templates sidebar -->
                <div class="lg:col-span-1 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
                        <h3 class="text-sm font-bold text-slate-900 mb-3">Modeles predefinis</h3>
                        <div class="space-y-2">
                            <button
                                v-for="(tpl, key) in templates"
                                :key="key"
                                @click="applyTemplate(key)"
                                class="w-full text-left rounded-xl border-2 px-4 py-3 text-sm font-medium transition"
                                :class="selectedTemplate === key ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                            >
                                {{ tpl.label }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
                        <h3 class="text-sm font-bold text-slate-900 mb-3">Tags de fusion</h3>
                        <p class="text-xs text-slate-500 mb-3">Cliquez pour inserer un tag dans le corps du message.</p>
                        <div class="space-y-2">
                            <button
                                v-for="(desc, tag) in mergeTags"
                                :key="tag"
                                @click="insertTag(tag)"
                                class="flex w-full items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs transition hover:bg-slate-100"
                            >
                                <code class="font-mono text-purple-600">{{ tag }}</code>
                                <span class="text-slate-500">{{ desc }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="sendEmail" class="lg:col-span-2 space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-5">
                        <!-- Target -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-800 mb-2">Destinataires</label>
                            <div class="flex flex-wrap gap-2">
                                <button type="button"
                                    @click="form.target_role = 'all'"
                                    class="rounded-2xl border-2 px-4 py-2 text-sm font-medium transition"
                                    :class="form.target_role === 'all' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                                    Tous
                                </button>
                                <button type="button"
                                    @click="form.target_role = 'vendor'"
                                    class="rounded-2xl border-2 px-4 py-2 text-sm font-medium transition"
                                    :class="form.target_role === 'vendor' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                                    Vendeurs
                                </button>
                                <button type="button"
                                    @click="form.target_role = 'influencer'"
                                    class="rounded-2xl border-2 px-4 py-2 text-sm font-medium transition"
                                    :class="form.target_role === 'influencer' ? 'border-purple-500 bg-purple-50 text-purple-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                                    Createurs de contenu
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-slate-400">{{ recipientPreview }}</p>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label for="subject" class="block text-sm font-semibold text-slate-800 mb-1.5">Sujet</label>
                            <input
                                id="subject"
                                v-model="form.subject"
                                type="text"
                                maxlength="255"
                                placeholder="Sujet de l'email"
                                class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                            />
                            <p v-if="form.errors.subject" class="mt-1.5 text-sm text-red-600">{{ form.errors.subject }}</p>
                        </div>

                        <!-- Body -->
                        <div>
                            <label for="body" class="block text-sm font-semibold text-slate-800 mb-1.5">Corps du message</label>
                            <textarea
                                id="body"
                                v-model="form.body"
                                rows="12"
                                maxlength="10000"
                                placeholder="Contenu de l'email..."
                                class="block w-full rounded-2xl border-slate-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm font-mono"
                            ></textarea>
                            <div class="mt-1 flex items-center justify-between">
                                <p v-if="form.errors.body" class="text-sm text-red-600">{{ form.errors.body }}</p>
                                <p class="ml-auto text-xs text-slate-400">{{ (form.body || '').length }} / 10000 caracteres</p>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-2xl bg-purple-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-700 disabled:opacity-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                                {{ form.processing ? 'Envoi en cours...' : 'Envoyer l\'email' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
