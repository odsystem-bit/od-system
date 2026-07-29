<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    routePrefix: { type: String, required: true },
});

const page = usePage();
const open = ref(false);

const notifications = computed(() => page.props.notifications ?? []);
const unreadCount = computed(() => page.props.auth?.user?.unread_notifications_count ?? 0);

const colorMap = {
    teal: 'bg-teal-500',
    purple: 'bg-purple-500',
    red: 'bg-red-500',
    blue: 'bg-blue-500',
    slate: 'bg-slate-400',
};

function dotColor(color) {
    return colorMap[color] ?? colorMap.slate;
}

function toggle() {
    open.value = !open.value;
}

function close() {
    open.value = false;
}

function clickNotification(n) {
    router.post(route(props.routePrefix + '.notifications.read', n.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            close();
            if (n.url) {
                router.visit(n.url);
            }
        },
    });
}

function markAllRead() {
    router.post(route(props.routePrefix + '.notifications.read-all'), {}, {
        preserveScroll: true,
        onSuccess: () => close(),
    });
}
</script>

<template>
    <div class="relative">
        <!-- Bell Button -->
        <button @click="toggle" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <!-- Badge -->
            <span v-if="unreadCount > 0" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Overlay (click-away) -->
        <div v-if="open" class="fixed inset-0 z-40" @click="close"></div>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div v-if="open" class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-xl border border-slate-200 bg-white shadow-xl">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Notifications</h3>
                    <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs font-medium text-teal-600 transition hover:text-teal-700">
                        Tout marquer lu
                    </button>
                </div>

                <!-- Notification List -->
                <div v-if="notifications.length === 0" class="px-4 py-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <p class="mt-2 text-xs text-slate-400">Aucune notification</p>
                </div>

                <div v-else class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                    <button
                        v-for="n in notifications"
                        :key="n.id"
                        @click="clickNotification(n)"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50"
                    >
                        <!-- Color dot -->
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :class="dotColor(n.color)"></span>
                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ n.title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 truncate">{{ n.message }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">{{ n.created_at }}</p>
                        </div>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
