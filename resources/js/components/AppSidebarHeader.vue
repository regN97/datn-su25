<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();

// Các route cần hiển thị nút quay lại
const showBackButton = computed(() => {
    const url = page.url; // vd: "/admin/products/create"
    return url.includes('create') || url.includes('edit') || url.includes('trash');
});

const goBack = () => {
    // Nếu có lịch sử thì quay lại, không thì về dashboard
    window.history.length > 1 ? router.visit(document.referrer) : router.visit('/');
};
</script>

<template>
    <header
        class="border-sidebar-border/70 flex h-16 shrink-0 items-center justify-between border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Nút quay lại -->
        <button
            v-if="showBackButton"
            @click="goBack"
            class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition"
        >
            ← Quay lại
        </button>
    </header>
</template>
