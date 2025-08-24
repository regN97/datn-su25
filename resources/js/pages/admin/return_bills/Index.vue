<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { PackageX, ChevronDown, ChevronUp, Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// --- Khai báo kiểu dữ liệu tương ứng với dữ liệu từ server ---
type ReturnBillDetail = {
    p_name: string; // Tên sản phẩm, lấy từ ReturnBillDetail
    returned_quantity: number;
    unit_price: number;
    subtotal: number;
};

type Cashier = {
    name: string;
};

type Bill = {
    bill_number: string;
};

type ReturnBill = {
    id: number;
    return_bill_number: string;
    bill: Bill;
    cashier: Cashier;
    total_amount_returned: number;
    reason: string;
    created_at: string;
    details: ReturnBillDetail[];
};

// --- Breadcrumbs cho giao diện ---
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý đơn trả hàng',
        href: '/admin/return-bills',
    },
];

// --- Lấy dữ liệu từ Inertia Props ---
const page = usePage<SharedData>();
const returnBills = computed(() => (page.props.returnBills as ReturnBill[]) || []);

// --- Logic tìm kiếm và lọc ---
const filters = ref({
    search: page.props.filters?.search || '',
    filter_date: page.props.filters?.filter_date || '',
    selected_date: page.props.filters?.selected_date || '',
});

watch(
    filters,
    (newFilters) => {
        const queryParams = {
            search: newFilters.search,
            filter_date: newFilters.filter_date,
            selected_date: newFilters.filter_date === 'custom_date' ? newFilters.selected_date : null,
        };
        const filteredParams = Object.fromEntries(
            Object.entries(queryParams).filter(([, value]) => value !== '' && value !== null)
        );
        router.get('/admin/return-bills', filteredParams, {
            preserveState: true,
            replace: true,
        });
    },
    { deep: true }
);

watch(
    () => filters.value.filter_date,
    (newValue) => {
        if (newValue !== 'custom_date') {
            filters.value.selected_date = '';
        }
    }
);

// --- Logic phân trang ---
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(10);
const currentPage = ref(1);

const total = computed(() => returnBills.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedReturnBills = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return returnBills.value.slice(start, start + perPage.value);
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

// --- Logic quản lý trạng thái hiển thị dropdown ---
const expandedBillId = ref<number | null>(null);

function toggleDetails(billId: number) {
    if (expandedBillId.value === billId) {
        expandedBillId.value = null;
    } else {
        expandedBillId.value = billId;
    }
}

// --- Computed property mới để gộp các sản phẩm giống nhau ---
const groupedReturnDetails = computed(() => {
    const bill = returnBills.value.find(b => b.id === expandedBillId.value);
    if (!bill) return [];

    const groups: { [key: string]: ReturnBillDetail } = {};
    bill.details.forEach(detail => {
        const key = detail.p_name;
        if (!groups[key]) {
            groups[key] = {
                p_name: detail.p_name,
                returned_quantity: 0,
                unit_price: detail.unit_price,
                subtotal: 0,
            };
        }
        groups[key].returned_quantity += detail.returned_quantity;
        groups[key].subtotal += detail.subtotal;
    });

    // Cập nhật lại subtotal sau khi đã tính tổng số lượng
    Object.values(groups).forEach(group => {
        group.subtotal = group.returned_quantity * group.unit_price;
    });

    return Object.values(groups);
});

// --- Hàm định dạng tiền tệ và ngày tháng ---
function formatCurrency(amount: number) {
    if (amount == null || isNaN(amount)) return '0₫';
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

function formatDate(dateString: string) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}
</script>
<template>
    <Head title="Đơn Trả Hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Danh sách Đơn Trả Hàng</h1>
                    </div>
                    <div class="mb-4 flex flex-col items-center gap-4 md:flex-row md:justify-between">
                        <input type="text" v-model="filters.search" placeholder="Tìm kiếm theo mã đơn hoặc thu ngân..."
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
                                    <th class="w-[15%] p-3 text-left text-sm font-semibold">Mã Đơn Trả</th>
                                    <th class="w-[15%] p-3 text-left text-sm font-semibold">Hóa Đơn Gốc</th>
                                    <th class="w-[15%] p-3 text-left text-sm font-semibold">Thu Ngân</th>
                                    <th class="w-[15%] p-3 text-right text-sm font-semibold">Tổng Tiền Hoàn</th>
                                    <th class="w-[10%] p-3 text-left text-sm font-semibold">Ngày Tạo</th>
                                    <th class="w-[5%] p-3 text-center text-sm font-semibold">Chi Tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(bill, idx) in paginatedReturnBills" :key="bill.id">
                                    <tr class="border-t">
                                        <td class="w-[15%] p-3 text-left text-sm">
                                            {{ bill.return_bill_number }}
                                        </td>
                                        <td class="w-[15%] p-3 text-left text-sm">
                                            {{ bill.bill?.bill_number ?? 'Không có' }}
                                        </td>
                                        <td class="w-[15%] p-3 text-left text-sm">
                                            {{ bill.cashier?.name ?? 'N/A' }}
                                        </td>
                                        <td class="w-[15%] p-3 text-right text-sm">
                                            {{ formatCurrency(bill.total_amount_returned) }}
                                        </td>
                                        <td class="w-[10%] p-3 text-left text-sm">
                                            {{ formatDate(bill.created_at) }}
                                        </td>
                                        <td class="w-[5%] p-3 text-center text-sm">
                                            <button @click="toggleDetails(bill.id)" class="text-blue-500 hover:text-blue-700">
                                                <component :is="expandedBillId === bill.id ? EyeOff : Eye" class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="expandedBillId === bill.id" class="bg-gray-100">
                                        <td colspan="6" class="p-4">
                                            <div class="px-4 py-2 border-l-4 border-blue-500 bg-white shadow-inner rounded-md">
                                                <h4 class="font-bold text-gray-700 mb-2">Chi tiết đơn trả</h4>
                                                <p class="text-sm font-semibold mb-2">Lý do: <span class="font-normal">{{ bill.reason }}</span></p>
                                                
                                                <table class="w-full text-left mt-2">
                                                    <thead>
                                                        <tr class="bg-gray-200">
                                                            <th class="p-2 text-xs font-semibold">Sản phẩm</th>
                                                            <th class="p-2 text-xs font-semibold text-center">Số lượng đã trả</th>
                                                            <th class="p-2 text-xs font-semibold text-right">Giá</th>
                                                            <th class="p-2 text-xs font-semibold text-right">Thành tiền</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="item in groupedReturnDetails" :key="item.p_name">
                                                            <td class="p-2 text-sm">{{ item.p_name }}</td>
                                                            <td class="p-2 text-sm text-center">{{ item.returned_quantity }}</td>
                                                            <td class="p-2 text-sm text-right">{{ formatCurrency(item.unit_price) }}</td>
                                                            <td class="p-2 text-sm text-right">{{ formatCurrency(item.subtotal) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="paginatedReturnBills.length === 0">
                                    <td colspan="8" class="p-3 text-center text-sm">Không có dữ liệu đơn trả hàng nào.</td>
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