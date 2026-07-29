<template>
    <div>
        <div v-if="hasError" class="flex items-center justify-center min-h-96">
            <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
                <!-- Error Icon -->
                <div class="mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100">
                        <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="text-xl font-semibold text-slate-900 mb-2">
                    Une erreur est survenue
                </h2>

                <!-- Description -->
                <p class="text-slate-500 text-sm mb-6">
                    Nous sommes désolés. Une erreur inattendue s'est produite. Veuillez réessayer ou contacter le support.
                </p>

                <!-- Error Details (Dev only) -->
                <details v-if="isDev && errorMessage" class="mb-6 text-left">
                    <summary class="cursor-pointer text-xs font-mono text-slate-600 hover:text-slate-900">
                        Détails techniques
                    </summary>
                    <pre class="mt-2 text-xs bg-slate-100 rounded p-2 overflow-auto max-h-32 text-slate-700">{{ errorMessage }}</pre>
                </details>

                <!-- Action Buttons -->
                <div class="flex gap-2 justify-center">
                    <button
                        @click="resetError"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-teal-600 to-teal-500 text-white font-semibold rounded-full hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5"
                    >
                        Réessayer
                    </button>
                    <a
                        href="/"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-100 text-slate-700 font-semibold rounded-full hover:bg-slate-200 transition-colors"
                    >
                        Accueil
                    </a>
                </div>
            </div>
        </div>

        <!-- Normal render -->
        <slot v-else />
    </div>
</template>

<script setup>
import { ref } from 'vue';

const hasError = ref(false);
const errorMessage = ref('');
const isDev = process.env.NODE_ENV === 'development';

const resetError = () => {
    hasError.value = false;
    errorMessage.value = '';
};

// Handle errors from child components
const handleError = (error) => {
    hasError.value = true;
    errorMessage.value = error?.message || 'Unknown error';
    console.error('Error caught by boundary:', error);
};

defineExpose({
    handleError,
    resetError,
});

// Optionally catch promise rejections
if (typeof window !== 'undefined') {
    window.addEventListener('error', (event) => {
        if (event.error) {
            handleError(event.error);
        }
    });
}
</script>
