<template>
    <div
        :class="[
            'bg-white rounded-2xl p-6 shadow-sm border border-slate-200 transition-all duration-300',
            {
                'hover:-translate-y-1 hover:shadow-md': hoverable,
                'ring-2 ring-teal-500': active,
            }
        ]"
    >
        <slot />
    </div>
</template>

<script setup>
defineProps({
    hoverable: {
        type: Boolean,
        default: false,
    },
    active: {
        type: Boolean,
        default: false,
    },
});
</script>
