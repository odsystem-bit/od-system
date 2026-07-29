<template>
    <component
        :is="as"
        :type="type"
        :class="[
            baseClasses,
            getVariantClasses(variant),
            getSizeClasses(size),
            {
                'opacity-50 cursor-not-allowed': disabled,
                'transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0': !disabled && variant !== 'ghost',
            }
        ]"
        :href="href"
        :disabled="disabled"
        v-bind="$attrs"
    >
        <slot />
    </component>
</template>

<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'danger', 'success', 'outline', 'ghost'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    as: {
        type: String,
        default: 'button',
        validator: (v) => ['button', 'a', 'router-link', 'inertia-link', 'Link'].includes(v),
    },
    type: {
        type: String,
        default: 'button',
        validator: (v) => ['button', 'submit', 'reset'].includes(v),
    },
    href: String,
    variant: String,
    size: String,
});

const baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold rounded-full border border-transparent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 whitespace-nowrap';

const getVariantClasses = (v) => ({
    primary: 'bg-gradient-to-r from-teal-600 to-teal-500 text-white shadow-md hover:shadow-brand focus-visible:outline-teal-600',
    secondary: 'bg-gradient-to-r from-purple-600 to-purple-500 text-white shadow-md hover:shadow-brand focus-visible:outline-purple-600',
    danger: 'bg-gradient-to-r from-red-600 to-red-500 text-white shadow-md hover:shadow-brand focus-visible:outline-red-600',
    success: 'bg-gradient-to-r from-emerald-600 to-emerald-500 text-white shadow-md hover:shadow-brand focus-visible:outline-emerald-600',
    outline: 'border-2 border-teal-600 text-teal-600 bg-white hover:bg-teal-50 focus-visible:outline-teal-600',
    ghost: 'text-slate-700 hover:bg-slate-100 focus-visible:outline-slate-600',
})[v];

const getSizeClasses = (s) => ({
    xs: 'text-xs px-3 py-1.5',
    sm: 'text-sm px-4 py-2',
    md: 'text-sm px-5 py-2.5',
    lg: 'text-base px-6 py-3',
    xl: 'text-lg px-8 py-4',
})[s];
</script>
