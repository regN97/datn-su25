<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lý danh mục', href: '/admin/categories' },
    { title: 'Thùng rác', href: '/admin/categories/trashed' },
];

type Category = {
    id: number;
    name: string;
    parent_id: number | null;
    description: string;
    deleted_at: string;
};

const page = usePage<SharedData>();
const categories = ref<Category[]>(page.props.categories as Category[]);

const getParentName = (parent_id: number | null) => {
    if (!parent_id) return '—';
    const parent = categories.value.find((cat) => cat.id === parent_id);
    return parent ? parent.name : '—';
};

function restoreCategory(id: number) {
    router.post(
        `/admin/categories/${id}/restore`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                categories.value = categories.value.filter((s) => s.id !== id);
                alert('Khôi phục thành công!');
            },
            onError: () => {
                alert('Khôi phục thất bại!');
            },
        },
    );
}

function forceDeleteCategory(id: number) {
    if (confirm('Bạn có chắc muốn xóa vĩnh viễn nhà cung cấp này?')) {
        router.delete(`/admin/categories/${id}/force-delete`, {
            preserveScroll: true,
            onSuccess: () => {
                categories.value = categories.value.filter((s) => s.id !== id);
                alert('Xóa vĩnh viễn thành công!');
            },
            onError: () => {
                alert('Xóa vĩnh viễn thất bại!');
            },
        });
    }
}
function comback() {
    router.visit('/admin/categories');
}
</script>

<template>
    <Head title="Thùng rác danh mục" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <h1 class="text-2xl font-bold">Thùng rác danh mục</h1>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <table class="w-full table-auto text-left">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="p-3 text-sm font-semibold">Tên danh mục</th>
                            <th class="p-3 text-sm font-semibold">Danh mục cha</th>
                            <th class="p-3 text-sm font-semibold">Mô tả</th>
                            <th class="p-3 text-sm font-semibold">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="cat in categories" :key="cat.id" class="border-t">
                            <td class="p-3 text-sm">{{ cat.name }}</td>
                            <td class="p-3 text-sm">{{ getParentName(cat.parent_id) }}</td>
                            <td class="p-3 text-sm">{{ cat.description }}</td>
                            <td class="p-3 text-sm">
                                <button @click="restoreCategory(cat.id)" class="text-blue-600 hover:underline">Khôi phục</button>
                                <button @click="forceDeleteCategory(cat.id)" class="ml-2 text-red-600 hover:underline">Xóa vĩnh viễn</button>
                            </td>
                        </tr>
                        <tr v-if="categories.length === 0">
                            <td colspan="7" class="p-3 text-center text-sm">Không có nhà cung cấp nào trong thùng rác</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end">
                <button @click="comback()" class="text-primary-700 rounded bg-gray-200 px-6 py-2 hover:bg-gray-300">Quay lại</button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    border: 1px solid #e5e7eb;
}
</style>
