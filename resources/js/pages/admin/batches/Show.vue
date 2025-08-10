<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { CheckCircle, CircleAlert } from 'lucide-vue-next';
import { Money3Directive } from 'v-money3';
import Swal from 'sweetalert2';

// Types
interface Product {
    id: number;
    name: string;
    sku: string;
    barcode?: string;
    description?: string;
    category_id?: number;
    unit_id: number;
    purchase_price: number;
    selling_price: number;
    image_url?: string;
    min_stock_level: number;
    max_stock_level: number;
    is_active: boolean;
    suppliers: Supplier[];
}

interface Batch {
    id: number;
    batch_number: string;
    purchase_order_id: number;
    purchase_order?: PurchaseOrder | null;
    supplier_id: number | null;
    supplier?: Supplier | null;
    received_date: string;
    invoice_number: string | null;
    total_amount: number;
    payment_status: "unpaid" | "partially_paid" | "paid";
    paid_amount: number;
    receipt_status: "partially_received" | "completed" | "cancelled";
    notes: string | null;
    created_by: number;
    creator?: User;
    discount_amount: number;
    discount_type: string;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    expected_delivery_date?: string;
}

interface BatchItem {
    id: number;
    batch_id: number;
    product_id: number;
    purchase_order_item_id: number;
    product_name: string;
    product_sku: string;
    ordered_quantity: number;
    received_quantity: number;
    rejected_quantity: number;
    remaining_quantity: number;
    current_quantity: number;
    purchase_price: number;
    total_amount: number;
    manufacturing_date: string | null;
    expiry_date: string | null;
    inventory_status: "active" | "low_stock" | "out_of_stock" | "expired" | "damaged";
    product?: {
        name: string;
        sku: string;
        unit?: {
            name: string;
        };
        image_url?: string;
    };
}

interface Supplier {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    address?: string;
    pivot: {
        purchase_price: number;
    };
}

interface User {
    id: number;
    name: string;
}

interface Props {
    suppliers?: Supplier[];
    users?: User[];
    batch: Batch[];
    batchItem: BatchItem[];
}

interface POStatus {
    id: number;
    name: string;
    code: string;
};

// Kiểu mới cho PurchaseOrderItem (các mặt hàng trong đơn đặt hàng)
interface PurchaseOrderItem {
    id: number;
    purchase_order_id: number;
    product_id: number;
    product?: Product; // Mối quan hệ product, bao gồm cả unit
    product_name: string;
    product_sku: string;
    ordered_quantity: number;
    received_quantity: number;
    quantity_returned: number; // Đã thêm
    unit_cost: number;
    subtotal: number;
    discount_amount: number | null; // Có thể null
    discount_type: 'percent' | 'amount' | null; // Có thể null
    notes: string | null; // Đã thêm
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};

// Kiểu cho PurchaseOrder, đã cập nhật theo cấu trúc database mới
interface PurchaseOrder {
    id: number;
    po_number: string;
    supplier_id: number;
    supplier?: Supplier;
    status_id: number;
    status?: POStatus;
    order_date: string;
    expected_delivery_date: string | null;
    actual_delivery_date: string | null;
    discount_amount: number | null;
    discount_type: 'percent' | 'amount' | null;
    total_amount: number;
    created_by: number;
    creator?: User;
    approved_by: number | null;
    approver?: User;
    approved_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    items?: PurchaseOrderItem[]; // Mảng các mặt hàng trong đơn đặt hàng
};

const props = withDefaults(defineProps<Props>(), {
    suppliers: () => [],
    users: () => [],
    batch: () => [],
    batchItem: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chi tiết lô hàng',
        href: '/admin/batches/show',
    },
];

const po_products = ref<BatchItem[]>(props.batchItem.map(item => ({
    ...item,
    received_quantity: item.received_quantity || 0,
})));

// === Logic mới để gộp sản phẩm ===
const aggregatedProducts = computed(() => {
    const productMap = new Map();

    props.batchItem.forEach(item => {
        if (productMap.has(item.product_id)) {
            const existingItem = productMap.get(item.product_id);
            existingItem.received_quantity += item.received_quantity;
            existingItem.rejected_quantity += item.rejected_quantity;
            existingItem.total_amount += item.total_amount;
        } else {
            productMap.set(item.product_id, {
                ...item,
            });
        }
    });

    return Array.from(productMap.values());
});
// === Kết thúc logic mới ===


// Sử dụng computed để truy cập phần tử đầu tiên của mảng batch
const currentBatch = computed(() => props.batch[0] || null);

const selectedUser = computed(() =>
    props.users.find((u) => u.id === currentBatch.value?.created_by) || null
);

const selectedSupplier = computed(() =>
    props.suppliers.find((s) => s.id === currentBatch.value?.supplier_id) || null
);

const batchNumber = computed(() => currentBatch.value?.batch_number || 'Chưa có mã lô');

const formattedReceivedDate = computed(() => {
    const date = currentBatch.value?.received_date;
    return date ? new Date(date).toLocaleDateString('vi-VN') : 'Chưa xác định';
});

const formattedDiscount = computed(() => {
    if (!currentBatch.value || currentBatch.value.discount_amount === 0) return '0₫';
    if (currentBatch.value.discount_type === 'amount') {
        return `-${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(currentBatch.value.discount_amount)}`;
    }
    return `-${currentBatch.value.discount_amount}%`;
});

// Sử dụng aggregatedProducts để tính toán các giá trị tổng
const formattedSubtotal = computed(() => {
    const total = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(total);
});

// Sử dụng aggregatedProducts để tính toán các giá trị tổng
const totalAfterDiscount = computed(() => {
    const subtotal = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    const discountAmount = currentBatch.value?.discount_amount || 0;
    const discountType = currentBatch.value?.discount_type || 'amount';

    let total = subtotal;
    if (discountType === 'amount') {
        total -= discountAmount;
    } else if (discountType === 'percent') {
        total -= (subtotal * discountAmount) / 100;
    }

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(total);
});

function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(price);
}

const receiptStatusLabel = computed(() => {
    const status = currentBatch.value?.receipt_status;
    if (status === 'completed') return 'Hoàn thành';
    if (status === 'partially_received') return 'Nhận một phần';
    if (status === 'cancelled') return 'Đã hủy';
    return 'Không rõ';
});

const paymentStatusInfo = computed(() => {
    const status = currentBatch.value?.payment_status;
    if (status === 'paid') {
        return {
            label: 'Đã thanh toán',
            icon: CheckCircle,
            class: 'text-green-600',
        };
    } else if (status === 'partially_paid') {
        return {
            label: 'Thanh toán một phần',
            icon: CircleAlert,
            class: 'text-orange-600',
        };
    }
    return {
        label: 'Chưa thanh toán',
        icon: CircleAlert,
        class: 'text-yellow-600',
    };
});


const isPaid = computed(() => currentBatch.value?.payment_status === 'paid');
const isUnpaidOrPartial = computed(() =>
    ['unpaid', 'partially_paid'].includes(currentBatch.value?.payment_status)
);

const formattedPaidAmount = computed(() => {
    const amount = currentBatch.value?.paid_amount || 0;
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(amount);
});

// Sử dụng aggregatedProducts để tính toán các giá trị tổng
const formattedRemainingAmount = computed(() => {
    const subtotal = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    const discountAmount = currentBatch.value?.discount_amount || 0;
    const discountType = currentBatch.value?.discount_type || 'amount';
    const paid = currentBatch.value?.paid_amount || 0;

    let total = subtotal;
    if (discountType === 'amount') {
        total -= discountAmount;
    } else if (discountType === 'percent') {
        total -= (subtotal * discountAmount) / 100;
    }

    const remaining = total - paid;

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(remaining);
});

// Sử dụng aggregatedProducts để tính toán các giá trị tổng
const remainingAmount = computed(() => {
    const subtotal = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    const discountAmount = currentBatch.value?.discount_amount || 0;
    const discountType = currentBatch.value?.discount_type || 'amount';
    const paid = currentBatch.value?.paid_amount || 0;

    let total = subtotal;
    if (discountType === 'amount') {
        total -= discountAmount;
    } else if (discountType === 'percent') {
        total -= (subtotal * discountAmount) / 100;
    }

    return total - paid;
});

function handlePayment() {
    if (isUnpaidOrPartial.value) {
        const today = new Date().toISOString().split('T')[0];
        paymentForm.value = {
            paymentAmount: remainingAmount.value, // Sử dụng remainingAmount đã tính toán
            paymentDate: today,
            reference: '',
        };
        showPaymentForm.value = true;
    }
}


const money = {
    decimal: '.',
    thousands: ',',
    prefix: '',
    suffix: '',
    precision: 0,
    masked: false,
    allowNegative: false
}

// Hàm format tiền tệ
function formatCurrency(value: number | null | undefined) {
    if (value === undefined || value === null) return '0₫';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(value);
}

// Hàm format ngày giờ
function formatDateTime(dateString: string | null | undefined) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric'
    };
    return new Intl.DateTimeFormat('vi-VN', options).format(date);
}

// Hàm lấy tên trạng thái
function getStatusDisplayName(status: string | undefined) {
    switch (status) {
        case 'paid':
            return 'Đã thanh toán';
        case 'partially_paid':
            return 'Thanh toán một phần';
        case 'unpaid':
            return 'Chưa thanh toán';
        default:
            return 'Không rõ';
    }
}

defineExpose({}); // nếu chưa có
defineOptions({
    directives: {
        money: Money3Directive,
    },
});

// Tổng số lượng thực nhập (đã trừ rejected)
// Sử dụng aggregatedProducts để tính toán các giá trị tổng
const totalActualQuantity = computed(() => {
    return aggregatedProducts.value.reduce((sum, p) => sum + (p.received_quantity || 0), 0);
});

const paymentMethod = ref('cash');

const today = new Date();
const formattedDate = today.toISOString().split('T')[0];
// Add state for the payment form
const showPaymentForm = ref(false);
const paymentForm = ref({
    paymentAmount: 0,
    paymentDate: formattedDate,
    reference: '',
});

// Handle payment form submission
function handlePaymentSubmit() {
    const amount = Number(String(paymentForm.value.paymentAmount).replace(/[^\d.-]/g, ''));
    if (amount <= 0) {
        alert('Số tiền thanh toán phải lớn hơn 0.');
        return;
    }

    if (amount > remainingAmount.value) {
        alert('Số tiền thanh toán không được vượt quá số tiền còn phải trả.');
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    if (paymentForm.value.paymentDate > today) {
        alert('Ngày thanh toán không được lớn hơn ngày hôm nay.');
        return;
    }

    const payload = {
        paymentAmount: amount,
        paymentDate: paymentForm.value.paymentDate,
        paymentMethod: paymentMethod.value,
    };

    router.post(route('admin.batches.pay', { id: currentBatch.value?.id }), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentForm.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Thanh toán thành công!',
                showConfirmButton: false,
                timer: 1500,
            });
            setTimeout(() => {
                router.visit(route('admin.batches.show', { id: currentBatch.value?.id }));
            }, 1500);
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
}

function goReturn() {
    if (currentBatch.value?.id) {
        router.visit(route('admin.purchaseReturn.create', { batch_id: currentBatch.value.id }));
    } else {
        alert('Không thể tạo đơn hoàn trả. Lô hàng không hợp lệ.');
    }
}

function printOrder() {
    window.print();
}

function cancelPayment() {
    showPaymentForm.value = false;
}

function goBack() {
    router.visit(route('admin.batches.index'));
}

</script>

<template>

    <Head title="Chi tiết lô hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 p-4">
            <div class="mx-auto max-w-7xl">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <button @click="goBack"
                            class="mb-0 flex h-10 w-10 items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:border-gray-400 hover:text-gray-800">
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <h1 class="ml-4 text-3xl font-bold text-gray-900">{{ batchNumber }}</h1>
                        <span class="ml-2 rounded-full px-3 py-1 text-sm font-medium" :class="{
                            'bg-green-100 text-green-700': currentBatch?.receipt_status === 'completed',
                            'bg-yellow-100 text-yellow-700': currentBatch?.receipt_status === 'partially_received',
                            'bg-red-100 text-red-700': currentBatch?.receipt_status === 'cancelled'
                        }">
                            {{ receiptStatusLabel }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 pr-2">
                        <button
                            v-if="currentBatch?.receipt_status === 'completed' || currentBatch?.receipt_status === 'partially_received'"
                            @click="goReturn" class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm
         hover:bg-gray-100 hover:border-gray-400 transition-all duration-300 ease-in-out" title="Tạo đơn hoàn trả">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span>Hoàn trả</span>
                        </button>
                        <button @click="printOrder"
                            class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-800 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 hover:border-gray-300 transition"
                            title="In đơn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 9V4a2 2 0 012-2h8a2 2 0 012 2v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-6 0h4" />
                            </svg>
                            <span>In đơn</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Chi tiết lô hàng</h2>
                            </div>
                            <div class="space-y-6 p-6">
                                <div v-if="aggregatedProducts.length > 0" class="space-y-3">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Sản phẩm
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Số lượng
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Đơn giá
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Thành tiền
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="p in aggregatedProducts" :key="p.product_id">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center space-x-4">
                                                        <img :src="p.product?.image_url || '/storage/piclumen-1747750187180.png'"
                                                            :alt="p.product_name"
                                                            class="h-12 w-12 rounded-lg border border-gray-200 object-cover" />
                                                        <div>
                                                            <h4 class="font-medium text-gray-900">{{ p.product_name }}
                                                            </h4>
                                                            <p class="text-sm text-gray-500">SKU: {{ p.product_sku }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex flex-col">
                                                        <span>{{ p.received_quantity }}</span>
                                                        <span v-if="p.rejected_quantity > 0"
                                                            class="text-xs text-red-500">
                                                            {{ p.rejected_quantity }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                    <span>{{ formatPrice(p.purchase_price) }}</span>
                                                </td>
                                                <td
                                                    class="px-6 py-4 text-sm font-semibold whitespace-nowrap text-gray-900">
                                                    {{ formatPrice(p.total_amount) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="p-4 border-b border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <component :is="paymentStatusInfo.icon" class="h-5 w-5"
                                        :class="paymentStatusInfo.class" />
                                    <span :class="paymentStatusInfo.class + ' font-semibold text-base lg:text-lg'">
                                        {{ paymentStatusInfo.label }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-4 space-y-4">
                                <div class="flex justify-between items-center text-sm text-gray-800">
                                    <span class="w-1/3 font-medium">Tổng tiền</span>
                                    <span class="w-1/3 text-center text-gray-600">
                                        {{ totalActualQuantity }} sản phẩm
                                    </span>
                                    <span class="w-1/3 text-right font-semibold text-black">
                                        {{ formattedSubtotal }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-blue-600 font-medium">Chiết khấu lô</span>
                                    <span class="text-red-600 font-semibold text-right">
                                        {{ formattedDiscount }}
                                    </span>
                                </div>

                                <div class="pt-3 border-t border-gray-200">
                                    <div class="flex justify-between items-center text-base font-bold text-gray-900">
                                        <span>Tiền cần trả NCC</span>
                                        <span class="text-right">{{ totalAfterDiscount }}</span>
                                    </div>
                                </div>
                                <div v-if="isPaid || currentBatch?.payment_status === 'partially_paid'"
                                    class="pt-4 border-t border-gray-200 space-y-2">
                                    <div class="flex justify-between text-sm text-gray-800">
                                        <span class="font-medium">Đã trả</span>
                                        <span class="text-right">{{ formattedPaidAmount }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-800">
                                        <span class="font-medium">Còn phải trả</span>
                                        <span class="text-right text-red-600">{{ formattedRemainingAmount }}</span>
                                    </div>
                                </div>
                                <div v-if="isUnpaidOrPartial && remainingAmount > 0"
                                    class="pt-4 border-t border-gray-200">
                                    <div class="flex justify-end pr-2">
                                        <button @click="handlePayment"
                                            class="text-xs rounded-md bg-blue-600 px-2.5 py-1 text-white hover:bg-blue-700 focus:ring-1 focus:ring-offset-1 focus:ring-blue-500">
                                            Xác nhận thanh toán
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div v-if="showPaymentForm"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm transition-all duration-300">
                            <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
                                <h2 class="text-xl font-semibold mb-6">Xác nhận thanh toán</h2>
                                <form @submit.prevent="handlePaymentSubmit">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Hình thức thanh
                                                toán</label>
                                            <select v-model="paymentMethod"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 bg-white focus:border-blue-500 focus:ring-blue-500">
                                                <option value="cash">Tiền mặt</option>
                                                <option value="bank_transfer">Chuyển khoản</option>
                                                <option value="credit_card">Thẻ</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền thanh
                                                toán</label>
                                            <input v-model="paymentForm.paymentAmount" v-money="money"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500" />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày ghi
                                                nhận</label>
                                            <input type="date" v-model="paymentForm.paymentDate"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500" />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Tham
                                                chiếu</label>
                                            <input type="text" v-model="paymentForm.reference"
                                                placeholder="Nhập mã tham chiếu"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500" />
                                        </div>
                                    </div>

                                    <div class="mt-6 flex justify-end space-x-4">
                                        <button type="button" @click="cancelPayment"
                                            class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                            Hủy
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                            Xác nhận
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>

                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Nhà cung cấp</h2>
                            </div>
                            <div class="p-4">
                                <div v-if="selectedSupplier" class="rounded-md border border-gray-200 bg-gray-50 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h3 class="font-medium text-gray-900">{{ selectedSupplier.name }}</h3>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <h3 class="font-bold text-black-900">Thông tin nhà cung cấp</h3>
                                        <p v-if="selectedSupplier.email" class="text-black-400">{{
                                            selectedSupplier.email }}</p>
                                        <p v-if="selectedSupplier.phone" class="text-black-400">{{
                                            selectedSupplier.phone }}</p>
                                        <p v-if="selectedSupplier.address" class="text-black-400">{{
                                            selectedSupplier.address }}</p>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500">Không có thông tin nhà cung cấp</div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thông tin bổ sung</h2>
                            </div>
                            <div class="space-y-4 p-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Nhân viên phụ
                                        trách</label>
                                    <input type="text" disabled :value="selectedUser ? selectedUser.name : ''"
                                        class="h-10 w-full rounded-md border border-gray-300 pl-4 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhận hàng</label>
                                    <input type="text" disabled :value="formattedReceivedDate"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Mã đơn nhập hàng</label>
                                    <input type="text" disabled :value="batchNumber"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="print-only">
                <div class="receipt-container">
                    <div class="receipt-header">
                        <h1 class="receipt-title">PHIẾU NHẬP HÀNG</h1>
                    </div>

                    <div class="receipt-details" v-if="currentBatch">
                        <p><strong>Mã phiếu:</strong> {{ currentBatch.batch_number }}</p>
                        <p><strong>Ngày giờ:</strong> {{ formatDateTime(currentBatch.received_date) }}</p>
                        <p><strong>Nhà cung cấp:</strong> {{ currentBatch.supplier?.name || 'N/A' }}</p>
                        <p><strong>Người tạo phiếu:</strong> {{ selectedUser?.name || 'N/A' }}</p>
                        <p><strong>Trạng thái TT:</strong> {{ getStatusDisplayName(currentBatch.payment_status) }}</p>
                    </div>

                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;">STT</th>
                                <th style="width: 45%; text-align: left;">Tên sản phẩm</th>
                                <th style="width: 15%;">SL</th>
                                <th style="width: 20%;">Đơn giá</th>
                                <th style="width: 15%; text-align: right;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in aggregatedProducts" :key="item.product_id">
                                <td style="text-align: center;">{{ index + 1 }}</td>
                                <td style="text-align: left;">{{ item.product_name }}</td>
                                <td style="text-align: center;">{{ item.received_quantity }}</td>
                                <td style="text-align: right;">{{ formatCurrency(item.purchase_price) }}</td>
                                <td style="text-align: right;">{{ formatCurrency(item.total_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="receipt-summary" v-if="currentBatch">
                        <p><strong>Tạm tính:</strong> <span>{{formatCurrency(aggregatedProducts.reduce((sum, item) =>
                            sum + item.total_amount, 0))}}</span></p>
                        <p><strong>Giảm giá:</strong> <span>-{{ formatCurrency(currentBatch.discount_amount || 0)
                        }}</span>
                        </p>
                        <p><strong>Phí vận chuyển:</strong> <span>{{ formatCurrency(currentBatch.shipping_fee || 0)
                        }}</span>
                        </p>
                        <p class="total-line"><strong>Tổng tiền cần trả:</strong> <span>{{
                            formatCurrency(remainingAmount + (currentBatch?.paid_amount || 0)) }}</span></p>
                        <p><strong>Đã trả:</strong> <span>{{ formatCurrency(currentBatch.paid_amount) }}</span></p>
                        <p><strong>Còn phải trả:</strong> <span>{{ formatCurrency(remainingAmount) }}</span></p>
                    </div>

                    <div class="receipt-footer">
                        <p>Cảm ơn quý khách!</p>
                        <p>(Vui lòng kiểm tra kỹ trước khi rời đi)</p>
                    </div>
                </div>
            </div>
        </div>

            <!-- Additional Info -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-4">
                    <h2 class="text-lg font-semibold">Thông tin bổ sung</h2>
                </div>
                <div class="space-y-4 p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nhân viên phụ trách</label>
                        <input type="text" disabled :value="selectedUser ? selectedUser.name : ''"
                            class="h-10 w-full rounded-md border border-gray-300 pl-4 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhận hàng</label>
                        <input type="text" disabled :value="formattedReceivedDate"
                            class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Mã đơn đặt hàng</label>
                        <input type="text" disabled :value="props.batch[0]?.purchase_order?.po_number"
                            class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />

                    </div>
                </div>
            </div>
    </AppLayout>
</template>
<style scoped>
/* Base styles for screen (hide print-only content) */
.print-only {
    display: none;
}

/* Styles to hide main content when printing */
@media print {

    /* Đảm bảo chỉ nội dung in hiển thị */
    body {
        margin: 0;
        padding: 0;
        background: white;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        /* Font dễ đọc */
        -webkit-print-color-adjust: exact;
        /* Đảm bảo in màu chính xác */
    }

    body>* {
        display: none !important;
        /* Ẩn tất cả nội dung trên trang */
    }

    .no-print {
        display: none !important;
        /* Đảm bảo ẩn các phần tử có class no-print */
    }

    /* Hiển thị chỉ phần phiếu in */
    .print-only {
        display: block !important;
        visibility: visible !important;
        width: 100%;
        /* Chiếm toàn bộ chiều rộng trang in */
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        /* Đảm bảo padding không làm tràn width */
    }

    .receipt-container {
        width: 78mm;
        /* Chiều rộng giấy in nhiệt phổ biến */
        max-width: 78mm;
        /* Đảm bảo không vượt quá */
        margin: 0 auto;
        /* Căn giữa phiếu trên trang */
        padding: 8mm 5mm;
        /* Padding trên dưới nhiều hơn, hai bên vừa phải */
        background: white;
        color: #000;
        /* Đảm bảo màu chữ đen */
        font-size: 10pt;
        /* Kích thước chữ cơ bản */
        line-height: 1.4;
    }

    .receipt-header {
        text-align: center;
        margin-bottom: 18px;
        /* Tăng khoảng cách dưới header */
    }

    .receipt-logo {
        max-width: 80px;
        /* Tăng kích thước logo một chút cho dễ nhìn */
        height: auto;
        margin-bottom: 10px;
        /* Khoảng cách giữa logo và tiêu đề */
    }

    .receipt-title {
        font-size: 18pt;
        /* Kích thước tiêu đề lớn hơn */
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
    }

    .receipt-details {
        margin-bottom: 18px;
        /* Tăng khoảng cách dưới thông tin chi tiết chung */
        border-bottom: 1px dashed #aaa;
        /* Đường kẻ phân cách */
        padding-bottom: 12px;
        /* Tăng padding dưới */
    }

    .receipt-details p {
        margin-bottom: 5px;
        /* Tăng khoảng cách giữa các dòng thông tin */
    }

    .receipt-details strong {
        display: inline-block;
        width: 120px;
        /* Điều chỉnh độ rộng để căn chỉnh các nhãn */
    }

    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        /* Tăng khoảng cách trên bảng */
        margin-bottom: 15px;
        /* Tăng khoảng cách dưới bảng */
        font-size: 9pt;
        /* Kích thước chữ cho bảng */
    }

    .receipt-table th,
    .receipt-table td {
        border-bottom: 1px dashed #ddd;
        /* Đường kẻ mờ hơn */
        padding: 6px 0;
        /* Tăng padding để giãn cách các dòng trong bảng */
        vertical-align: top;
        /* Căn trên cho nội dung dài */
    }

    .receipt-table th {
        font-weight: bold;
        text-align: center;
        /* Căn giữa tiêu đề cột */
    }

    /* Điều chỉnh căn chỉnh cụ thể cho các cột trong bảng */
    .receipt-table th:nth-child(1),
    /* STT */
    .receipt-table td:nth-child(1) {
        text-align: center;
    }

    .receipt-table th:nth-child(2),
    /* Tên sản phẩm */
    .receipt-table td:nth-child(2) {
        text-align: left;
        padding-left: 2px;
    }

    .receipt-table th:nth-child(3),
    /* SL */
    .receipt-table td:nth-child(3) {
        text-align: center;
    }

    .receipt-table th:nth-child(4),
    /* Đơn giá */
    .receipt-table td:nth-child(4) {
        text-align: right;
    }

    .receipt-table th:nth-child(5),
    /* Thành tiền */
    .receipt-table td:nth-child(5) {
        text-align: right;
    }

    .receipt-summary {
        margin-top: 20px;
        /* Tăng khoảng cách trên phần tổng kết */
        text-align: right;
        font-size: 10pt;
        border-top: 1px dashed #aaa;
        padding-top: 12px;
        /* Tăng padding trên */
    }

    .receipt-summary p {
        margin-bottom: 5px;
    }

    .receipt-summary strong {
        display: inline-block;
        width: 150px;
    }

    .receipt-summary span {
        display: inline-block;
        width: 120px;
        text-align: right;
    }

    .total-line {
        font-size: 11pt;
        /* Kích thước chữ cho dòng tổng tiền */
        font-weight: bold;
        border-top: 1px dashed #aaa;
        padding-top: 10px;
        margin-top: 10px;
    }

    .receipt-footer {
        text-align: center;
        margin-top: 30px;
        /* Tăng khoảng cách trên footer */
        font-size: 9pt;
        line-height: 1.5;
    }
}
</style>
