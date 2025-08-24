<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý khách hàng',
        href: '/admin/customers',
    },
];

type Customer = {
    id: number;
    customer_name: string;
    email: string | null;
    phone: string;
    address: string | null;
    wallet: number;
};

const page = usePage<SharedData>();

// Safely initialize customers
const customers = ref<Customer[]>(Array.isArray(page.props.customers) ? [...page.props.customers] : []);

// biến search
const search = ref("");

// computed lọc khách hàng
const filteredCustomers = computed(() => {
    if (!search.value) return customers.value;
    const term = search.value.toLowerCase();
    return customers.value.filter((c) =>
        c.customer_name?.toLowerCase().includes(term) ||
        c.email?.toLowerCase().includes(term) ||
        c.phone?.toLowerCase().includes(term)
    );
});

const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const total = computed(() => filteredCustomers.value.length); // đổi sang filtered
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedCustomers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredCustomers.value.slice(start, start + perPage.value); // đổi sang filtered
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

function goToEditCustomer(id: number) {
    router.visit(`/admin/customers/${id}/edit`);
}

function goToCreatePage() {
    router.visit('/admin/customers/create');
}

function goToTrashedPage() {
    router.visit('/admin/customers/trashed');
}

const showDeleteModal = ref(false);
const customerToDelete = ref<number | null>(null);

function confirmDelete(id: number) {
    customerToDelete.value = id;
    showDeleteModal.value = true;
}

function handleDeleteCustomer() {
    if (!customerToDelete.value) return;

    router.delete(`/admin/customers/${customerToDelete.value}`, {
        onSuccess: () => {
            const idx = customers.value.findIndex((cust) => cust.id === customerToDelete.value);
            if (idx !== -1) customers.value.splice(idx, 1);
            showDeleteModal.value = false;
            customerToDelete.value = null;
        },
        preserveState: true,
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    customerToDelete.value = null;
}
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <!-- Tiêu đề và nút Thêm mới và thùng rác -->
                   <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <h1 class="text-2xl font-bold">Khách hàng</h1>
    <div class="flex items-center gap-2">
        <button @click="goToCreatePage" class="rounded-3xl bg-green-500 px-8 py-2 text-white hover:bg-green-600">
            <PackagePlus />
        </button>
        <button @click="goToTrashedPage" class="rounded-3xl bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
            Thùng rác
        </button>
    </div>
</div>

<!-- Ô tìm kiếm -->
<div class="mb-4">
    <input
        v-model="search"
        type="text"
        placeholder="Tìm kiếm..."
        class="border rounded px-3 py-2 w-64 md:w-72 lg:w-80"
    />
</div>

                    <!-- Bảng danh sách khách hàng -->
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="w-[5%] p-3 text-center text-sm font-semibold">STT</th>
                                    <th class="w-[20%] p-3 text-left text-sm font-semibold">Tên khách hàng</th>
                                    <th class="w-[20%] p-3 text-left text-sm font-semibold">Email</th>
                                    <th class="w-[15%] p-3 text-center text-sm font-semibold">SĐT</th>
                                    <!-- Đã xóa cột địa chỉ -->
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Ví tiền</th>
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(customer, idx) in paginatedCustomers" :key="customer.id" class="border-t">
                                    <td class="w-[5%] p-3 text-center text-sm">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                    <td class="w-[20%] p-3 text-left text-sm">
                                        {{ customer.customer_name }}
                                    </td>
                                    <td class="w-[20%] p-3 text-left text-sm">
                                        {{
                                            customer.email && customer.email.length > 15
                                                ? customer.email.slice(0, 15) + '...'
                                                : customer.email || 'N/A'
                                        }}
                                    </td>
                                    <td class="w-[15%] p-3 text-center text-sm">
                                        {{ customer.phone || 'N/A' }}
                                    </td>
                                    <!-- Đã xóa cột địa chỉ -->
                                    <td class="w-[10%] p-3 text-center text-sm">
                                        {{ (typeof customer.wallet === 'number' && !isNaN(customer.wallet)) ? customer.wallet.toLocaleString('vi-VN') + ' VND' : 'N/A' }}
                                    </td>
                                    <td class="w-[10%] p-3 text-center text-sm">
                                        <button
                                            class="me-1 rounded-md bg-blue-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                            @click="goToEditCustomer(customer.id)"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button
                                            class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                            @click="confirmDelete(customer.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="paginatedCustomers.length === 0">
                                    <td colspan="7" class="p-3 text-center text-sm">Không có dữ liệu</td>
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
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700" :disabled="currentPage === 1" @click="prevPage">
                                ← Trang trước
                            </button>
                            <template v-for="page in totalPages" :key="page">
                                <button
                                    class="rounded px-3 py-1 text-sm"
                                    :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(page)"
                                >
                                    {{ page }}
                                </button>
                            </template>
                            <button
                                class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === totalPages"
                                @click="nextPage"
                            >
                                Trang sau →
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

        <DeleteModal
            :is-open="showDeleteModal"
            title="Xóa khách hàng"
            message="Bạn có chắc chắn muốn xóa khách hàng này?"
            @confirm="handleDeleteCustomer"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

<style lang="css" scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: fixed;
}
</style>
