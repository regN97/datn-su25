<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DeleteModal from '@/components/DeleteModal.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackagePlus, Eye, Trash2, Camera } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// ... (Giữ nguyên các type và breadcrumbs) ...
type Customer = {
    customer_name: string;
};

type Cashier = {
    name: string;
};

type PaymentStatus = {
    name: string;
    code: string;
};

type Bill = {
    id: number;
    bill_number: string;
    customer: Customer;
    cashier: Cashier;
    total_amount: number;
    payment_status: PaymentStatus;
    created_at: string;
    payment_method: string;
    payment_proof_url: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý hóa đơn',
        href: '/admin/bills',
    },
];

const page = usePage<SharedData>();
const bills = computed(() => (page.props.bills as Bill[]) || []);

// Khai báo các biến cho tìm kiếm và lọc
const filters = ref({
    search: page.props.filters?.search || '',
    filter_date: page.props.filters?.filter_date || '',
    // Thay thế start_date và end_date bằng một biến duy nhất
    selected_date: page.props.filters?.selected_date || '',
});

// Logic watch để gửi request
watch(
    filters,
    (newFilters) => {
        const queryParams = {
            search: newFilters.search,
            filter_date: newFilters.filter_date,
            // Gửi selected_date chỉ khi filter_date là 'custom_date'
            selected_date: newFilters.filter_date === 'custom_date' ? newFilters.selected_date : null,
        };

        // Loại bỏ các trường rỗng
        const filteredParams = Object.fromEntries(
            Object.entries(queryParams).filter(([, value]) => value !== '' && value !== null)
        );

        router.get('/admin/bills', filteredParams, {
            preserveState: true,
            replace: true,
        });
    },
    { deep: true }
);

// Thêm watch để xóa ngày khi thay đổi loại lọc
watch(
    () => filters.value.filter_date,
    (newValue) => {
        if (newValue !== 'custom_date') {
            filters.value.selected_date = '';
        }
    }
);

// Logic phân trang
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(10);
const currentPage = ref(1);

const total = computed(() => bills.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedBills = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return bills.value.slice(start, start + perPage.value);
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

// Chuyển hướng
function goToShowBill(id: number) {
    router.visit(`/admin/bills/${id}`);
}


// Hàm định dạng tiền tệ
function formatCurrency(amount: number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

function getStatusColor(status: PaymentStatus) {
    if (!status || !status.code) {
        return {
            textColor: 'text-gray-900',
            bgColor: 'bg-gray-200'
        };
    }
    switch (status.code) {
        case 'PAID':
            return {
                textColor: 'text-green-900',
                bgColor: 'bg-green-200'
            };
        case 'UNPAID':
            return {
                textColor: 'text-yellow-900',
                bgColor: 'bg-yellow-200'
            };
        case 'REFUNDED':
            return {
                textColor: 'text-red-900',
                bgColor: 'bg-red-200'
            };
        default:
            return {
                textColor: 'text-gray-900',
                bgColor: 'bg-gray-200'
            };
    }
}

// Logic cho modal hiển thị ảnh minh chứng
const showProofModal = ref(false);
const proofImageUrl = ref<string | null>(null);

function showImageProof(url: string | null) {
    if (url) {
        proofImageUrl.value = url;
        showProofModal.value = true;
    }
}

function closeProofModal() {
    showProofModal.value = false;
    proofImageUrl.value = null;
}

// Hàm chuyển đổi phương thức thanh toán sang tiếng Việt
function getPaymentMethodName(method: string) {
    switch (method) {
        case 'cash':
            return 'Tiền mặt';
        case 'credit_card':
            return 'Thẻ ngân hàng';
        case 'bank_transfer':
            return 'Chuyển khoản';
        case 'wallet':
            return 'Ví khách hàng';
        default:
            return 'Khác';
    }
}

</script>

<template>

    <Head title="Bills" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
        <div class="container mx-auto p-6">
          <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Danh sách Hóa đơn</h1>
          </div>

          <div class="mb-4 flex flex-col items-center gap-4 md:flex-row md:justify-between">
            <input type="text" v-model="filters.search" placeholder="Tìm kiếm theo mã hóa đơn..."
              class="w-full rounded-lg border px-4 py-2 md:w-auto" />
            <div class="flex w-full flex-col gap-2 md:flex-row md:w-auto">
              <select v-model="filters.filter_date" class="rounded-lg border px-4 py-2">
                <option value="">Tất cả thời gian</option>
                <option value="today">Hôm nay</option>
                <option value="this_week">Tuần này</option>
                <option value="this_month">Tháng này</option>
                <option value="custom_date">Tùy chọn ngày</option>
              </select>
              <input v-if="filters.filter_date === 'custom_date'" type="date"
                v-model="filters.selected_date" class="rounded-lg border px-4 py-2" />
            </div>
          </div>

<div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
    <table class="w-full text-left">
        <thead>
            <tr class="bg-gray-200">
                <th class="w-[5%] p-3 text-center text-sm font-semibold">STT</th>
                <th class="w-[12%] p-3 text-left text-sm font-semibold">Mã HĐ</th>
                <th class="w-[12%] p-3 text-left text-sm font-semibold">Khách hàng</th>
                <th class="w-[12%] p-3 text-left text-sm font-semibold">Thu ngân</th>
                <th class="w-[12%] p-3 text-right text-sm font-semibold">Tổng tiền</th>
                <th class="w-[12%] p-3 text-left text-sm font-semibold">Trạng thái TT</th>
                <th class="w-[10%] p-3 text-left text-sm font-semibold">Phương thức</th>
                <th class="w-[7%] p-3 text-center text-sm font-semibold">Minh chứng</th>
                <th class="w-[8%] p-3 text-left text-sm font-semibold">Ngày tạo</th>
                <th class="w-[10%] p-3 text-center text-sm font-semibold">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(bill, idx) in paginatedBills" :key="bill.id" class="border-t">
                <td class="w-[5%] p-3 text-center text-sm">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                <td class="w-[12%] p-3 text-left text-sm">{{ bill.bill_number }}</td>
                <td class="w-[12%] p-3 text-left text-sm">{{ bill.customer?.customer_name ?? 'Khách lẻ' }}</td>
                <td class="w-[12%] p-3 text-left text-sm">{{ bill.cashier.name ?? 'N/A' }}</td>
                <td class="w-[12%] p-3 text-right text-sm">{{ formatCurrency(bill.total_amount) }}</td>
                <td class="w-[12%] p-3 text-left text-sm">
                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight"
                        :class="getStatusColor(bill.payment_status).textColor">
                        <span aria-hidden class="absolute inset-0 opacity-50 rounded-full"
                            :class="getStatusColor(bill.payment_status).bgColor"></span>
                        <span class="relative">{{ bill.payment_status.name ?? 'N/A' }}</span>
                    </span>
                </td>
                <td class="w-[10%] p-3 text-left text-sm">{{ getPaymentMethodName(bill.payment_method) }}</td>
                <td class="w-[7%] p-3 text-center text-sm">
                    <button v-if="bill.payment_proof_url" @click="showImageProof(bill.payment_proof_url)" class="text-blue-500 hover:text-blue-700">
                        <Camera class="h-4 w-4" />
                    </button>
                </td>
                <td class="w-[8%] p-3 text-left text-sm">{{ new Date(bill.created_at).toLocaleDateString() }}</td>
                <td class="w-[10%] p-3 text-center text-sm">
                    <button
                        class="me-1 rounded-md bg-gray-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                        @click="goToShowBill(bill.id)">
                        <Eye class="h-4 w-4" />
                    </button>
                </td>
            </tr>
            <tr v-if="paginatedBills.length === 0">
                <td colspan="10" class="p-3 text-center text-sm">Không có dữ liệu hóa đơn nào.</td>
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

    <div v-if="showProofModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-70" @click.self="closeProofModal()">
        <div class="relative max-w-4xl p-4 bg-white rounded-lg shadow-lg">
            <button @click="closeProofModal()" class="absolute top-2 right-2 text-gray-800 hover:text-gray-600 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img :src="proofImageUrl" alt="Ảnh minh chứng thanh toán" class="max-h-[80vh] w-full object-contain">
        </div>
    </div>

  </AppLayout></template>

<style lang="css" scoped>
.table-wrapper table {
    min-width: 100%;
    table-layout: fixed;
}
</style>