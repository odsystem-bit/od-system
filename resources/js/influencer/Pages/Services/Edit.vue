<script setup>
import InfluencerLayout from '../../Layouts/InfluencerLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    service:          { type: Object, required: true },
    allowedTypes:     { type: Array, default: () => [] },
    allowedDurations: { type: Array, default: () => [] },
});

const types = [
    { value: 'ugc_humain',   label: 'UGC Humain',   desc: 'Contenu tourne par vous-meme (face camera, unboxing, temoignage...).' },
    { value: 'video_pub_ia', label: 'Video Pub IA',  desc: 'Video publicitaire generee avec assistance IA (avatar, voix off...).' },
];

const durations = [
    { value: '15s',  label: '15 secondes' },
    { value: '30s',  label: '30 secondes' },
    { value: '60s',  label: '60 secondes' },
    { value: 'long', label: 'Long format'  },
];

const form = useForm({
    _method: 'put',
    title: props.service.title ?? '',
    type: props.service.type ?? '',
    price: props.service.price ?? '',
    duration: props.service.duration ?? '',
    description: props.service.description ?? '',
    included_revisions: props.service.included_revisions ?? 1,
    image: null,
});

const imagePreview = ref(null);

function handleImageChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target.result; };
    reader.readAsDataURL(file);
}

function submit() {
    form.post(route('influencer.services.update', props.service.id), {
        preserveScroll: true,
        forceFormData: true,
    });
}

function formatCurrency(amount) {
    const v = parseFloat(amount);
    if (!v || v < 0) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' FCFA';
}

const currentImage = computed(() => {
    if (imagePreview.value) return imagePreview.value;
    if (props.service.image_path) return `/storage/${props.service.image_path}`;
    return null;
});
</script>

<template>
    <Head title="Modifier le service" />

    <InfluencerLayout>
        <div class="mx-auto max-w-2xl">

            <!-- Header -->
            <div class="mb-6">
                <Link :href="route('influencer.services.index')" class="inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-teal-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                    Retour aux services
                </Link>
                <h1 class="mt-2 text-xl font-bold text-slate-900">Modifier le service</h1>
                <p class="mt-1 text-sm text-slate-500">Mettez a jour les details de votre offre.</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-5 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">

                <!-- Titre -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Titre du service</label>
                    <input v-model="form.title" type="text" maxlength="255"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                        placeholder="Ex: UGC Unboxing Premium" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Type de service</label>
                    <div class="grid grid-cols-2 gap-3">
                        <button v-for="t in types" :key="t.value" type="button" @click="form.type = t.value"
                            class="rounded-lg border-2 p-3 text-left transition"
                            :class="form.type === t.value
                                ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-500/20'
                                : 'border-slate-200 bg-white hover:border-slate-300'">
                            <p class="text-sm font-semibold text-slate-900">{{ t.label }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ t.desc }}</p>
                        </button>
                    </div>
                    <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Prix + Duree -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Prix (FCFA)</label>
                        <input v-model="form.price" type="number" min="500" step="100"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                            placeholder="5000" />
                        <p v-if="form.errors.price" class="mt-1 text-xs text-red-600">{{ form.errors.price }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Duree</label>
                        <select v-model="form.duration"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none">
                            <option value="" disabled>Choisir</option>
                            <option v-for="d in durations" :key="d.value" :value="d.value">{{ d.label }}</option>
                        </select>
                        <p v-if="form.errors.duration" class="mt-1 text-xs text-red-600">{{ form.errors.duration }}</p>
                    </div>
                </div>

                <!-- Retouches incluses -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Retouches incluses</label>
                    <select v-model="form.included_revisions"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none">
                        <option v-for="n in 6" :key="n - 1" :value="n - 1">{{ n - 1 }} retouche{{ n - 1 > 1 ? 's' : '' }}</option>
                    </select>
                    <p v-if="form.errors.included_revisions" class="mt-1 text-xs text-red-600">{{ form.errors.included_revisions }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                    <textarea v-model="form.description" rows="4" maxlength="3000"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none resize-none"
                        placeholder="Decrivez ce que le vendeur recevra..." />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                </div>

                <!-- Image -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Image de couverture</label>
                    <div v-if="currentImage" class="mb-3">
                        <img :src="currentImage" class="h-32 w-full rounded-lg object-cover border border-slate-200" alt="Couverture du service" />
                    </div>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        Changer l'image
                        <input type="file" accept="image/*" class="hidden" @change="handleImageChange" />
                    </label>
                    <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                    <Link :href="route('influencer.services.index')"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-teal-500/20 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/30 disabled:opacity-50">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </form>
        </div>
    </InfluencerLayout>
</template>
