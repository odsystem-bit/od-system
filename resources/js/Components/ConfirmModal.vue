<script setup>
import Modal from './Modal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmation' },
    message: { type: String, required: true },
    confirmLabel: { type: String, default: 'Confirmer' },
    cancelLabel: { type: String, default: 'Annuler' },
    variant: { type: String, default: 'danger' }, // danger | warning | info
});

const emit = defineEmits(['confirmed', 'cancelled']);

const variantClasses = {
    danger: {
        icon: 'bg-red-100 text-red-600',
        btn: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    },
    warning: {
        icon: 'bg-amber-100 text-amber-600',
        btn: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
    },
    info: {
        icon: 'bg-teal-100 text-teal-600',
        btn: 'bg-teal-600 hover:bg-teal-700 focus:ring-teal-500',
    },
};

const classes = variantClasses[props.variant] || variantClasses.danger;
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('cancelled')">
        <div class="px-6 py-5">
            <div class="flex items-start gap-4">
                <!-- Icon -->
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="classes.icon">
                    <svg v-if="variant === 'danger'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <svg v-else-if="variant === 'warning'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <!-- Content -->
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
                    <p class="mt-1 text-sm text-slate-600 leading-relaxed">{{ message }}</p>
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-3">
            <button
                type="button"
                @click="emit('cancelled')"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1"
            >
                {{ cancelLabel }}
            </button>
            <button
                type="button"
                @click="emit('confirmed')"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1"
                :class="classes.btn"
            >
                {{ confirmLabel }}
            </button>
        </div>
    </Modal>
</template>
