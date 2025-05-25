<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý danh mục',
        href: '/admin/categories',
    },
];

type Category = {
    id: number;
    name: string;
    parent_id: number | null;
    description: string;
};

const page = usePage<SharedData>();

// Dữ liệu categories sẽ tự động được cập nhật khi backend render lại component
const categories = page.props.categories as Category[];

const getParentName = (parent_id: number | null) => {
    if (!parent_id) return '—';
    const parent = categories.find(cat => cat.id === parent_id);
    return parent ? parent.name : '—';
};

const perPageOptions = [5, 10, 25, 50];
const perPage = ref(20);
const currentPage = ref(1);

const total = computed(() => categories.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return categories.slice(start, start + perPage.value);
});

function goToPage(page: number) {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
}

function prevPage() {
    if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value++;
}

function changePerPage(event: Event) {
    perPage.value = +(event.target as HTMLSelectElement).value;
    currentPage.value = 1;
}

// --- Logic xóa danh mục sử dụng confirm() mặc định ---
function deleteCategory(id: number) {
    if (confirm('Bạn có chắc chắn muốn xóa danh mục này không?')) {
        router.delete(`/admin/categories/${id}`);
    }
}
// --- Kết thúc Logic xóa danh mục ---

</script>

<template>

    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Danh sách danh mục</h1>
                        <div class="flex gap-3">
                            <Link
                                href="/admin/categories/create"
                                class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Thêm mới
                            </Link>

                            <Link
                                href="/admin/categories/trashed"
                                class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                title="Xem danh mục đã xóa"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                Thùng rác
                            </Link>
                        </div>
                    </div>

                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-3 text-sm font-semibold">STT</th>
                                    <th class="p-3 text-sm font-semibold">Tên danh mục</th>
                                    <th class="p-3 text-sm font-semibold">Danh mục cha</th>
                                    <th class="p-3 text-sm font-semibold">Mô tả</th>
                                    <th class="p-3 text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(cat, idx) in paginatedCategories" :key="cat.id" class="border-t">
                                    <td class="p-3 text-sm">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                    <td class="p-3 text-sm">{{ cat.name }}</td>
                                    <td class="p-3 text-sm">{{ getParentName(cat.parent_id) }}</td>
                                    <td class="p-3 text-sm">{{ cat.description }}</td>
                                    <td class="p-3 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <Link :href="`/admin/categories/${cat.id}/edit`"
                                                class="px-3 py-1 rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150">
                                                Sửa
                                            </Link>
                                            <button @click="deleteCategory(cat.id)"
                                                class="px-3 py-1 rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition ease-in-out duration-150 flex items-center gap-1">
                                                ✂
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="paginatedCategories.length === 0">
                                    <td colspan="5" class="p-3 text-center text-sm">Không có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm">
                            Hiển thị kết quả từ
                            <span class="font-semibold">{{ (currentPage - 1) * perPage + 1 }}</span>
                            -
                            <span class="font-semibold">{{ Math.min(currentPage * perPage, total) }}</span>
                            trên tổng <span class="font-semibold">{{ total }}</span>
                        </p>
                        <div class="flex items-center space-x-2">
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === 1" @click="prevPage">
                                &larr; Trang trước
                            </button>
                            <template v-for="page in totalPages" :key="page">
                                <button class="rounded px-3 py-1 text-sm"
                                    :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(page)">
                                    {{ page }}
                                </button>
                            </template>
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === totalPages" @click="nextPage">
                                Trang sau &rarr;
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <p class="text-sm">Hiển thị</p>
                            <select class="rounded border p-1 text-sm" v-model="perPage" @change="changePerPage">
                                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <p class="text-sm">kết quả</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="css" scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: fixed;
}
</style>
