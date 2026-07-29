<template>
    <div class="fixed top-0 right-0 z-50 pointer-events-none">
        <TransitionGroup name="toast-slide" tag="div" class="space-y-3 p-4">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="[
                    'pointer-events-auto flex items-start gap-3 rounded-xl px-4 py-3 shadow-lg max-w-sm',
                    {
                        'bg-emerald-50 border border-emerald-200 text-emerald-900': notification.type === 'success',
                        'bg-red-50 border border-red-200 text-red-900': notification.type === 'danger',
                        'bg-blue-50 border border-blue-200 text-blue-900': notification.type === 'info',
                        'bg-amber-50 border border-amber-200 text-amber-900': notification.type === 'warning',
                    }
                ]"
            >
                <!-- Icon -->
                <svg
                    v-if="notification.type === 'success'"
                    class="h-5 w-5 shrink-0 text-emerald-600 mt-0.5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg
                    v-else-if="notification.type === 'danger'"
                    class="h-5 w-5 shrink-0 text-red-600 mt-0.5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <svg
                    v-else-if="notification.type === 'warning'"
                    class="h-5 w-5 shrink-0 text-amber-600 mt-0.5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <svg
                    v-else
                    class="h-5 w-5 shrink-0 text-blue-600 mt-0.5"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>

                <!-- Message -->
                <div class="flex-1">
                    <p class="text-sm font-medium">{{ notification.message }}</p>
                </div>

                <!-- Close Button -->
                <button
                    @click="removeNotification(notification.id)"
                    class="shrink-0 text-current/50 hover:text-current transition-colors"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref, inject } from 'vue';

const notifications = ref([]);
let nextId = 0;

const addNotification = (message, type = 'info', duration = 4000) => {
    const id = nextId++;
    const notification = { id, message, type };
    
    notifications.value.push(notification);
    
    if (duration > 0) {
        setTimeout(() => removeNotification(id), duration);
    }
    
    return id;
};

const removeNotification = (id) => {
    notifications.value = notifications.value.filter(n => n.id !== id);
};

// Expose via provide/inject
defineExpose({
    addNotification,
    removeNotification,
});
</script>

<style scoped>
.toast-slide-enter-active,
.toast-slide-leave-active {
    transition: all 300ms ease;
}

.toast-slide-enter-from {
    transform: translateX(400px);
    opacity: 0;
}

.toast-slide-leave-to {
    transform: translateX(400px);
    opacity: 0;
}
</style>
