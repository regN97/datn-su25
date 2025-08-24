<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
};

const page = usePage<SharedData>();

const suppliers = ref([...(page.props.suppliers as Supplier[])]);
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const searchTerm = ref('');

function normalize(str: string) {
    return str
        ? str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
        : '';
}

// danh sách sau khi lọc
const filteredSuppliers = computed(() => {
    if (!searchTerm.value) return suppliers.value;
    const term = normalize(searchTerm.value);
    return suppliers.value.filter(
        (s) =>
            normalize(s.name).includes(term) ||
            normalize(s.contact_person || '').includes(term) ||
            normalize(s.email || '').includes(term) ||
            normalize(s.phone || '').includes(term) ||
            normalize(s.address || '').includes(term)
    );
});

const total = computed(() => filteredSuppliers.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedSuppliers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredSuppliers.value.slice(start, start + perPage.value);
});

//  reset về trang 1 khi search
watch(searchTerm, () => {
    currentPage.value = 1;
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

function goToEditSupplier(id: number) {
    router.visit(`/admin/suppliers/${id}/edit`);
}
function goToCreatePage() {
    router.visit('/admin/suppliers/create');
}

function goToTrashedPage() {
    router.visit('/admin/suppliers/trashed');
}

const showDeleteModal = ref(false);
const supplierToDelete = ref<number | null>(null);

function confirmDelete(id: number) {
    supplierToDelete.value = id;
    showDeleteModal.value = true;
}

function handleDeleteSupplier() {
    if (!supplierToDelete.value) return;

    router.delete(`/admin/suppliers/${supplierToDelete.value}`, {
        onSuccess: (page) => {
            const flash = page?.props?.flash || {};
            if (flash.success) {
                const idx = suppliers.value.findIndex((cat) => cat.id === supplierToDelete.value);
                if (idx !== -1) suppliers.value.splice(idx, 1);
            }
            showDeleteModal.value = false;
            supplierToDelete.value = null;
        },
        preserveScroll: true,
        preserveState: false,
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    supplierToDelete.value = null;
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
                        <div class="flex gap-2">
                            <button @click="goToCreatePage"
                                class="rounded-3xl bg-green-500 px-8 py-2 text-white hover:bg-green-600">
                                <PackagePlus />
                            </button>
                            <button @click="goToTrashedPage"
                                class="rounded-3xl bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">Thùng
                                rác</button>
                        </div>
                    </div>

                    <!-- Ô tìm kiếm -->
                    <div class="mb-4">
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="Tìm kiếm nhà cung cấp..."
                            class="border rounded px-3 py-2 w-64 md:w-72 lg:w-80"
                        />
                    </div>

                    <!-- Bảng danh mục -->
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="w-[5%] p-3 text-center text-sm font-semibold">STT</th>
                                    <th class="w-[20%] p-3 text-left text-sm font-semibold">Tên nhà cung cấp</th>
                                    <th class="w-[15%] p-3 text-left text-sm font-semibold">Người liên hệ</th>
                                    <th class="w-[15%] p-3 text-left text-sm font-semibold">Email</th>
                                    <th class="w-[15%] p-3 text-center text-sm font-semibold">SĐT</th>
                                    <th class="w-[20%] p-3 text-left text-sm font-semibold">Địa chỉ</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(supplier, idx) in paginatedSuppliers" :key="supplier.id" class="border-t">
                                    <td class="w-[5%] p-3 text-center text-sm">{{ (currentPage - 1) * perPage + idx + 1
                                        }}</td>
                                    <td class="w-[20%] p-3 text-left text-sm">
                                        {{ supplier.name }}
                                    </td>
                                    <td class="w-[15%] p-3 text-left text-sm">
                                        {{ supplier.contact_person || 'N/A' }}
                                    </td>
                                    <td class="w-[15%] p-3 text-left text-sm">
                                        {{
                                            supplier.email && supplier.email.length > 15
                                                ? supplier.email.slice(0, 15) + '...'
                                                : supplier.email || 'N/A'
                                        }}
                                    </td>
                                    <td class="w-[15%] p-3 text-center text-sm">
                                        {{ supplier.phone || 'N/A' }}
                                    </td>
                                    <td class="w-[20%] p-3 text-left text-sm">
                                        {{ supplier.address || 'N/A' }}
                                    </td>
                                    <td class="w-[10%] p-3 text-center text-sm">
                                        <button
                                            class="me-1 rounded-md bg-blue-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                            @click="goToEditSupplier(supplier.id)">
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button
                                            class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                            @click="confirmDelete(supplier.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
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

        <DeleteModal :is-open="showDeleteModal" title="Xóa nhà cung cấp"
            message="Bạn có chắc chắn muốn xóa nhà cung cấp này?" @confirm="handleDeleteSupplier"
            @cancel="cancelDelete" />
    </AppLayout>
</template>

<style lang="css" scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: fixed;
}
</style>
