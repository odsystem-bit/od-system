<script setup>
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Layout from '../Layout.vue';

defineOptions({ layout: Layout });

const props = defineProps({
    partners: { type: Array, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash?.success ?? null);

const showForm = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    url: '',
    logo: null,
    sort_order: 0,
    is_active: true,
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
}

function openEdit(partner) {
    editing.value = partner;
    form.name = partner.name;
    form.url = partner.url || '';
    form.logo = null;
    form.sort_order = partner.sort_order;
    form.is_active = partner.is_active;
    showForm.value = true;
}

function submit() {
    if (editing.value) {
        form.post(route('admin.partners.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    } else {
        form.post(route('admin.partners.store'), {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    }
}

function deletePartner(partner) {
    if (!confirm('Supprimer ce partenaire ?')) return;
    router.delete(route('admin.partners.destroy', partner.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Partenaires" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.54a4.5 4.5 0 00-6.364-6.364L4.5 8.25" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-800">Partenaires</h1>
                    <p class="text-xs text-slate-500">Gestion des partenaires affiches sur le site vitrine</p>
                </div>
            </div>
            <button @click="openCreate" class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Ajouter un partenaire
            </button>
        </div>

        <!-- Flash -->
        <div v-if="flash" class="flex items-center gap-3 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
            {{ flash }}
        </div>

        <!-- Form modal -->
        <div v-if="showForm" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-800">{{ editing ? 'Modifier le partenaire' : 'Nouveau partenaire' }}</h2>
            <form @submit.prevent="submit" class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nom *</label>
                    <input v-model="form.name" type="text" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" required />
                    <div v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Site web</label>
                    <input v-model="form.url" type="url" placeholder="https://..." class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                    <div v-if="form.errors.url" class="mt-1 text-xs text-red-600">{{ form.errors.url }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Logo (image)</label>
                    <input type="file" accept="image/*" @input="form.logo = $event.target.files[0]" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-full file:border-0 file:bg-amber-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-amber-700 hover:file:bg-amber-100" />
                    <div v-if="form.errors.logo" class="mt-1 text-xs text-red-600">{{ form.errors.logo }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Ordre d'affichage</label>
                    <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" />
                </div>
                <div v-if="editing" class="flex items-center gap-2 sm:col-span-2">
                    <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500" />
                    <label for="is_active" class="text-sm text-slate-700">Actif (visible sur le site)</label>
                </div>
                <div class="flex gap-3 sm:col-span-2">
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-50">
                        {{ form.processing ? 'Envoi...' : (editing ? 'Mettre a jour' : 'Ajouter') }}
                    </button>
                    <button type="button" @click="showForm = false" class="rounded-xl bg-slate-100 px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200">Annuler</button>
                </div>
            </form>
        </div>

        <!-- Empty state -->
        <div v-if="!partners.length && !showForm" class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.54a4.5 4.5 0 00-6.364-6.364L4.5 8.25" /></svg>
            <h3 class="mt-4 text-sm font-semibold text-slate-900">Aucun partenaire</h3>
            <p class="mt-1 text-sm text-slate-500">Ajoutez vos premiers partenaires pour les afficher sur le site vitrine.</p>
        </div>

        <!-- Partners grid -->
        <div v-if="partners.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="p in partners" :key="p.id" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
                    <img v-if="p.logo" :src="'/storage/' + p.logo" :alt="p.name" class="h-full w-full object-contain p-1.5" />
                    <span v-else class="text-xl font-bold text-amber-500">{{ p.name?.charAt(0)?.toUpperCase() }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ p.name }}</p>
                    <p v-if="p.url" class="truncate text-xs text-slate-400">{{ p.url }}</p>
                    <span :class="p.is_active ? 'text-emerald-600 bg-emerald-50' : 'text-slate-500 bg-slate-100'" class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium">{{ p.is_active ? 'Actif' : 'Inactif' }}</span>
                </div>
                <div class="flex shrink-0 gap-1">
                    <button @click="openEdit(p)" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-amber-50 hover:text-amber-600" title="Modifier">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                    </button>
                    <button @click="deletePartner(p)" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="Supprimer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
