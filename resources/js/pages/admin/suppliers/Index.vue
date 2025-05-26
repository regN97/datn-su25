<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
// import { router } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý nhà cung cấp',
        href: '/admin/suppliers',
    },
];

type Supplier = {
    id: number;
    name: string;
    contact_person: string;
    email: string;
    phone: string;
    address: string | null;
}

const page = usePage<SharedData>();

const suppliers = ref([...page.props.suppliers as Supplier[]]);
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const total = computed(() => suppliers.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedSuppliers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return suppliers.value.slice(start, start + perPage.value);
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
function softDeleteSupplier(id: number) {
    if (!confirm('Bạn có chắc chắn muốn xóa nhà cung cấp này?')) return;

    router.delete(`/admin/suppliers/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            suppliers.value = suppliers.value.filter(s => s.id !== id);
            alert('Xóa thành công!');
        },
        onError: () => {
            alert('Xóa thất bại!');
        }
    });
}
function goToEditSupplier(id: number) {
    router.visit(`/admin/suppliers/${id}/edit`);
}
function addNewSupplier() {
    router.visit('/admin/suppliers/create');
}
</script>

<template>

    <Head title="Suppliers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <!-- Tiêu đề và nút Thêm mới và thùng rác -->
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Nhà cung cấp</h1>
                        <div class="flex space-x-2">
                            <button @click="addNewSupplier"
                                class="rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600">Thêm
                                mới</button>
                            <a href="/admin/suppliers/trashed"
                                class="rounded-3xl bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                                Thùng rác
                            </a>
                        </div>
                    </div>


                    <!-- Bảng danh mục -->
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-3 text-sm font-semibold">
                                        STT
                                    </th>
                                    <th class="p-3 text-sm font-semibold">
                                        Tên nhà cung cấp
                                    </th>
                                    <th class="p-3 text-sm font-semibold">
                                        Người liên hệ
                                    </th>
                                    <th class="p-3 text-sm font-semibold">
                                        Email
                                    </th>
                                    <th class="p-3 text-sm font-semibold">
                                        SĐT
                                    </th>
                                    <th class="p-3 text-sm font-semibold">
                                        Địa chỉ
                                    </th>
                                    <th class="p-3 text-sm font-semibold">
                                        Thao tác
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(supplier, idx) in paginatedSuppliers" :key="supplier.id" class="border-t">
                                    <td class="p-3 text-sm">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                    <td class="p-3 text-sm">
                                        {{ supplier.name }}
                                    </td>
                                    <td class="p-3 text-sm">
                                        {{ supplier.contact_person || 'N/A' }}
                                    </td>
                                    <td class="p-3 text-sm">
                                        {{ supplier.email && supplier.email.length > 15 ? supplier.email.slice(0, 15) +
                                        '...' : (supplier.email || 'N/A') }}
                                    </td>
                                    <td class="p-3 text-sm">
                                        {{ supplier.phone || 'N/A' }}
                                    </td>
                                    <td class="p-3 text-sm">
                                        {{ supplier.address || 'N/A' }}
                                    </td>
                                    <td class="p-3 text-sm">
                                        <button class="text-gray-500 hover:text-gray-700"
                                            @click="goToEditSupplier(supplier.id)">Sửa</button>
                                        <button class="text-red-500 hover:text-red-700 ml-2"
                                            @click="softDeleteSupplier(supplier.id)">Xóa</button>
                                    </td>
                                </tr>
                                <tr v-if="paginatedSuppliers.length === 0">
                                    <td colspan="5" class="p-3 text-center text-sm">Không có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Phân trang -->
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
