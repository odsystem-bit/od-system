<template>
    <div class="flex flex-col items-center justify-center min-h-80 px-4">
        <div class="bg-white rounded-2xl p-10 text-center max-w-md w-full">
            <!-- Icon -->
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-100">
                    <svg
                        :class="['w-10 h-10', iconColor]"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <slot name="icon">
                            <!-- Default: document icon (generic empty state) -->
                            <path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </slot>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h3 class="text-xl font-semibold text-slate-900 mb-2">
                {{ title }}
            </h3>

            <!-- Description -->
            <p class="text-slate-500 text-sm mb-8">
                {{ description }}
            </p>

            <!-- Action Button -->
            <slot name="action" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: 'Aucun contenu',
    },
    description: {
        type: String,
        default: 'Il n\'y a rien à afficher pour le moment.',
    },
    iconColor: {
        type: String,
        default: 'text-slate-400',
        validator: (v) => ['text-teal-400', 'text-purple-400', 'text-slate-400', 'text-emerald-400'].includes(v),
    },
});
</script>
