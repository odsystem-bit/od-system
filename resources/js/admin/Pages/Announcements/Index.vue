<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import Layout from '../Layout.vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { useConfirm } from '../../../Composables/useConfirm.js';

defineOptions({ layout: Layout });

const { visible: confirmVisible, title: confirmTitle, message: confirmMessage, variant: confirmVariant, confirmLabel, cancelLabel, ask, onConfirm, onCancel } = useConfirm();

const props = defineProps({
    announcements: { type: Object, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const form = reactive({
    message: '',
    target_role: 'all',
    is_active: true,
});

function resetForm() {
    form.message = '';
    form.target_role = 'all';
    form.is_active = true;
    editingId.value = null;
    showForm.value = false;
}

function startEdit(ann) {
    form.message = ann.message;
    form.target_role = ann.target_role;
    form.is_active = ann.is_active;
    editingId.value = ann.id;
    showForm.value = true;
}

function submit() {
    if (editingId.value) {
        router.put(route('admin.announcements.update', editingId.value), form, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        router.post(route('admin.announcements.store'), form, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
}

async function deleteAnnouncement(id) {
    if (!await ask({ title: 'Supprimer l\'annonce', message: 'Supprimer cette annonce ?', variant: 'danger', confirmLabel: 'Supprimer' })) return;
    router.delete(route('admin.announcements.destroy', id), { preserveScroll: true });
}

const roleLabels = {
    all: 'Tous',
    vendor: 'Vendors',
    influencer: 'Createurs de Contenu',
    admin: 'Admins',
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Annonces</h1>
                <p class="mt-1 text-sm text-slate-500">Diffusez des messages globaux sur la plateforme.</p>
            </div>
            <button
                v-if="!showForm"
                @click="showForm = true"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nouvelle annonce
            </button>
        </div>

        <!-- Form -->
        <div v-if="showForm" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-4">
                {{ editingId ? 'Modifier l\'annonce' : 'Nouvelle annonce' }}
            </h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="message" class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="3"
                        maxlength="500"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        placeholder="Ex: Maintenance prevue ce soir de 22h a 23h..."
                    ></textarea>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div>
                        <label for="target_role" class="block text-sm font-medium text-slate-700 mb-1">Cible</label>
                        <select
                            id="target_role"
                            v-model="form.target_role"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        >
                            <option value="all">Tous</option>
                            <option value="vendor">Vendors uniquement</option>
                            <option value="influencer">Createurs de Contenu uniquement</option>
                            <option value="admin">Admins uniquement</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            Active
                        </label>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        {{ editingId ? 'Mettre a jour' : 'Publier' }}
                    </button>
                    <button type="button" @click="resetForm" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        Annuler
                    </button>
                </div>
            </form>
        </div>

        <!-- List -->
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-900">Historique des annonces</h2>
            </div>
            <div v-if="!announcements.data.length" class="px-6 py-12 text-center text-sm text-slate-400">
                Aucune annonce pour le moment.
            </div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="ann in announcements.data" :key="ann.id" class="flex items-start gap-4 px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="ann.is_active ? 'bg-indigo-50' : 'bg-slate-100'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="ann.is_active ? 'text-indigo-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" /></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-900">{{ ann.message }}</p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" :class="ann.is_active ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10'">
                                {{ ann.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                {{ roleLabels[ann.target_role] || ann.target_role }}
                            </span>
                            <span>{{ new Date(ann.created_at).toLocaleDateString('fr-FR') }}</span>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <button @click="startEdit(ann)" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                        </button>
                        <button @click="deleteAnnouncement(ann.id)" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal :show="confirmVisible" :title="confirmTitle" :message="confirmMessage" :variant="confirmVariant" :confirm-label="confirmLabel" :cancel-label="cancelLabel" @confirmed="onConfirm" @cancelled="onCancel" />
</template>
