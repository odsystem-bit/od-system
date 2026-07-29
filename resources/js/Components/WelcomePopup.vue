<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Modal from './Modal.vue';

const page = usePage();
const welcomePopup = computed(() => page.props.welcome_popup);
const show = ref(true);

function dismiss() {
    show.value = false;
    if (welcomePopup.value?.dismiss_route) {
        router.post(route(welcomePopup.value.dismiss_route), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Modal :show="!!welcomePopup && show" max-width="lg" :closeable="false">
        <div class="p-8 text-center">
            <!-- Icon -->
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-100 to-purple-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                </svg>
            </div>

            <!-- Title -->
            <h2 class="mb-4 text-xl font-bold text-slate-900">Bienvenue sur MANTOTA</h2>

            <!-- Message -->
            <div class="mb-6 whitespace-pre-line text-sm leading-relaxed text-slate-600">
                {{ welcomePopup?.message }}
            </div>

            <!-- Dismiss button -->
            <button
                @click="dismiss"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-purple-500/25 transition hover:from-violet-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
            >
                J'ai compris
            </button>
        </div>
    </Modal>
</template>
