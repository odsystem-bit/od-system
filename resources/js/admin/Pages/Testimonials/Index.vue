<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    testimonials: { type: Array, required: true },
});

const showForm = ref(false);
const editingId = ref(null);

const form = reactive({
    name: '',
    role: '',
    content: '',
    rating: 5,
    is_active: true,
    sort_order: 0,
});

function resetForm() {
    form.name = '';
    form.role = '';
    form.content = '';
    form.rating = 5;
    form.is_active = true;
    form.sort_order = 0;
    editingId.value = null;
    showForm.value = false;
}

function editItem(t) {
    form.name = t.name;
    form.role = t.role || '';
    form.content = t.content;
    form.rating = t.rating;
    form.is_active = t.is_active;
    form.sort_order = t.sort_order;
    editingId.value = t.id;
    showForm.value = true;
}

function submit() {
    if (editingId.value) {
        router.put(route('admin.testimonials.update', editingId.value), { ...form }, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    } else {
        router.post(route('admin.testimonials.store'), { ...form }, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });
    }
}

function deleteItem(id) {
    if (!confirm('Supprimer ce temoignage ?')) return;
    router.delete(route('admin.testimonials.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Temoignages</h1>
                <p class="mt-1 text-sm text-slate-500">Gerez les temoignages affiches sur la page d'accueil.</p>
            </div>
            <button
                @click="showForm = !showForm; if (!showForm) resetForm()"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                <svg v-if="!showForm" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ showForm ? 'Annuler' : 'Ajouter' }}
            </button>
        </div>

        <!-- Form -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showForm" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-base font-semibold text-slate-900">{{ editingId ? 'Modifier le temoignage' : 'Nouveau temoignage' }}</h3>
                <form @submit.prevent="submit" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nom</label>
                        <input v-model="form.name" type="text" required maxlength="100" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ex: Fatoumata K." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Role / Titre</label>
                        <input v-model="form.role" type="text" maxlength="100" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ex: Vendeur de cosmetiques" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Temoignage</label>
                        <textarea v-model="form.content" required maxlength="500" rows="3" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Le temoignage du client..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Note (1-5)</label>
                        <input v-model.number="form.rating" type="number" min="1" max="5" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ordre d'affichage</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="is_active" class="text-sm text-slate-700">Actif (visible sur le site)</label>
                    </div>
                    <div class="flex justify-end sm:col-span-2">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            {{ editingId ? 'Mettre a jour' : 'Ajouter' }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- List -->
        <div v-if="testimonials.length" class="space-y-3">
            <div v-for="t in testimonials" :key="t.id" class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">
                    {{ t.name?.charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ t.name }}</p>
                        <span v-if="t.role" class="text-xs text-slate-500">-- {{ t.role }}</span>
                        <span v-if="!t.is_active" class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-600">Inactif</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ t.content }}</p>
                    <div class="mt-2 flex items-center gap-1">
                        <svg v-for="s in 5" :key="s" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" :class="s <= t.rating ? 'text-amber-400' : 'text-slate-200'" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.37-2.448a1 1 0 00-1.175 0l-3.37 2.448c-.784.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.065 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.284-3.957z" /></svg>
                        <span class="ml-1 text-xs text-slate-400">Ordre: {{ t.sort_order }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 gap-1">
                    <button @click="editItem(t)" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-indigo-600" title="Modifier">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
                    </button>
                    <button @click="deleteItem(t.id)" class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Supprimer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
            <p class="mt-3 text-sm font-medium text-slate-500">Aucun temoignage pour le moment.</p>
            <p class="mt-1 text-xs text-slate-400">Cliquez sur "Ajouter" pour creer votre premier temoignage.</p>
        </div>
    </div>
</template>
