<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu nhập kho',
        href: '/admin/purchase_receipts',
    },
];

type PurchaseReceipt = {
    id: number;
    receipt_number: string;
    purchase_order_id: number;
    total_items_received: number;
    total_value_received: number;
    is_partial: boolean;
    receipt_date: Date;
    received_by: number;
    notes: Text,
};

const page = usePage<SharedData>();


const purchase_receipts = page.props.purchase_receipts as PurchaseReceipt[];

const getParentName = (parent_id: number | null) => {
    if (!parent_id) return '—';
    const parent = purchase_receipts.find((purchase_receipt) => purchase_receipt.id === parent_id);
    return parent ? parent.receipt_number : '—';
};

const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const total = computed(() => purchase_receipts.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedPurchaseReceipts = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return purchase_receipts.slice(start, start + perPage.value);
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


const showDeleteModal = ref(false);
const categoryToDelete = ref<number | null>(null);

function confirmDelete(id: number) {
    categoryToDelete.value = id;
    showDeleteModal.value = true;
}

function getStatusDisplayName(status: string) {
    switch (status) {
        case 'true':
            return 'Hoàn tất';
        case 'false':
            return 'Nhận một phàn';
        default:
            return status;
    }
}

</script>

<template>

    <Head title="PurchaseReceipts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Danh sách phiếu nhập kho</h1>
                        <div class="flex gap-2">
                            <button class="rounded-3xl bg-green-500 px-8 py-2 text-white hover:bg-green-600">
                                <PackagePlus />
                            </button>
                            <button class="rounded-3xl bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">Thùng
                                rác</button>
                        </div>
                    </div>

                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="w-[10%] p-3 text-center text-sm font-semibold">STT</th>
                                    <th class="w-[25%] p-3 text-left text-sm font-semibold">Số phiếu nhập kho</th>
                                    <th class="w-[25%] p-3 text-center text-sm font-semibold">Mã đơn hàng</th>
                                    <th class="w-[25%] p-3 text-center text-sm font-semibold">Số lượng đã nhận</th>
                                    <th class="w-[25%] p-3 text-left text-sm font-semibold">Tổng tiền</th>
                                    <th class="w-[25%] p-3 text-left text-sm font-semibold">Ngày nhận đơn</th>
                                    <th class="w-[25%] p-3 text-center text-sm font-semibold">Trạng thái</th>
                                    <th class="w-[15%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(purchase_receipt, idx) in paginatedPurchaseReceipts"
                                    :key="purchase_receipt.id" class="border-t">
                                    <td class="w-[10%] p-3 text-center text-sm">{{ (currentPage - 1) * perPage + idx + 1
                                        }}</td>
                                    <td class="w-[25%] p-3 text-left text-sm">{{ purchase_receipt.receipt_number }}</td>
                                    <td class="w-[25%] p-3 text-center text-sm">{{ purchase_receipt.po_number }}
                                    </td>
                                    <td class="w-[25] p-3 text-center text-sm">{{ purchase_receipt.total_items_received
                                    }}</td>
                                    <td class="w-[25%] p-3 text-left text-sm">
                                        {{ new Intl.NumberFormat('vi-VN', {
                                            style: 'currency', currency: 'VND'
                                        }).format(purchase_receipt.total_value_received) }}
                                    </td>

                                    <td class="w-[25%] p-3 text-left text-sm">{{ purchase_receipt.receipt_date
                                    }}</td>
                                     <td class="w-[15%] p-4 text-center text-sm">
                                        <span :class="{
                                            'bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold shadow-sm': purchase_receipt.is_partial === 'true',
                                            'bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold shadow-sm': purchase_receipt.is_partial === 'false',
                                        }">
                                            {{ getStatusDisplayName(purchase_receipt.is_partial) }}
                                        </span>
                                    </td>
                                    <td class="w-[15%] p-3 text-center text-sm">
                                        <div class="flex items-center justify-center space-x-2">
                                            <button
                                                class="rounded-md bg-blue-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none">
                                                <Pencil class="h-4 w-4" />
                                            </button>

                                            <button
                                                class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none">
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="paginatedPurchaseReceipts.length === 0">
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

        <!-- <DeleteModal
            :is-open="showDeleteModal"
            title="Xóa danh mục"
            message="Bạn có chắc chắn muốn xóa phiếu nhập kho này?"
            @confirm="handleDeleteCategory"
            @cancel="cancelDelete"
        /> -->

    </AppLayout>
</template>

<style lang="css" scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: fixed;
}
</style>
