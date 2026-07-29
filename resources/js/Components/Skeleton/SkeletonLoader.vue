<template>
    <div :class="['animate-pulse', containerClass]">
        <!-- Skeleton Lines (Shimmer effect) -->
        <div v-for="i in count" :key="i" :class="lineClasses" />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'text',
        validator: (v) => ['text', 'card', 'table', 'avatar', 'list'].includes(v),
    },
    count: {
        type: Number,
        default: 3,
    },
    width: {
        type: String,
        default: 'w-full',
    },
});

const containerClass = computed(() => {
    return {
        text: 'space-y-3',
        card: 'space-y-3 rounded-2xl bg-slate-100 p-4',
        table: 'space-y-2',
        avatar: 'flex items-center gap-3',
        list: 'space-y-4',
    }[props.variant];
});

const lineClasses = computed(() => {
    const base = 'h-3 rounded-full bg-gradient-to-r from-slate-200 to-slate-300';
    switch (props.variant) {
        case 'text':
            return `${base} ${props.width}`;
        case 'card':
            return `${base} ${props.width}`;
        case 'table':
            return `${base} ${props.width}`;
        case 'avatar':
            return 'h-12 w-12 rounded-full bg-slate-200';
        case 'list':
            return `${base} ${props.width}`;
        default:
            return base;
    }
});
</script>
