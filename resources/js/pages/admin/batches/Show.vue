<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, ChevronLeft, CircleAlert } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { Money3Directive } from 'v-money3';
import { computed, ref } from 'vue';

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
    shipping_fee?: number;
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
    inventory_status: 'active' | 'low_stock' | 'out_of_stock' | 'expired' | 'damaged';
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
}

interface PurchaseOrderItem {
    id: number;
    purchase_order_id: number;
    product_id: number;
    product?: Product;
    product_name: string;
    product_sku: string;
    ordered_quantity: number;
    received_quantity: number;
    quantity_returned: number;
    unit_cost: number;
    subtotal: number;
    discount_amount: number | null;
    discount_type: 'percent' | 'amount' | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

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
    items?: PurchaseOrderItem[];
};

const props = withDefaults(defineProps<Props>(), {
    suppliers: () => [],
    users: () => [],
    batch: () => [],
    batchItem: () => [],
});

// const breadcrumbs: BreadcrumbItem[] = [
//     {
//         title: 'Chi tiết lô hàng',
//         href: '/admin/batches/show',
//     },
// ];

const po_products = ref<BatchItem[]>(
    props.batchItem.map((item) => ({
        ...item,
        received_quantity: item.received_quantity || 0,
    })),
);

const aggregatedProducts = computed(() => {
    const productMap = new Map();

    props.batchItem.forEach((item) => {
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

const selectedUser = computed(() => props.users.find((u) => u.id === currentBatch.value?.created_by) || null);

const selectedSupplier = computed(() => props.suppliers.find((s) => s.id === currentBatch.value?.supplier_id) || null);

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

const formattedSubtotal = computed(() => {
    const total = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(total);
});

const totalAfterDiscount = computed(() => {
    const subtotal = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    const discountAmount = currentBatch.value?.discount_amount || 0;
    const discountType = currentBatch.value?.discount_type || 'amount';
    const shippingFee = currentBatch.value?.shipping_fee || 0;

    let total = subtotal;
    if (discountType === 'amount') {
        total -= discountAmount;
    } else if (discountType === 'percent') {
        total -= (subtotal * discountAmount) / 100;
    }
    total += shippingFee;

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
const isUnpaidOrPartial = computed(() => ['unpaid', 'partially_paid'].includes(currentBatch.value?.payment_status));

const formattedPaidAmount = computed(() => {
    const amount = currentBatch.value?.paid_amount || 0;
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(amount);
});

const formattedRemainingAmount = computed(() => {
    const subtotal = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    const discountAmount = currentBatch.value?.discount_amount || 0;
    const discountType = currentBatch.value?.discount_type || 'amount';
    const paid = currentBatch.value?.paid_amount || 0;
    const shippingFee = currentBatch.value?.shipping_fee || 0;

    let total = subtotal;
    if (discountType === 'amount') {
        total -= discountAmount;
    } else if (discountType === 'percent') {
        total -= (subtotal * discountAmount) / 100;
    }
    total += shippingFee;

    const remaining = total - paid;

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(remaining);
});

const remainingAmount = computed(() => {
    const subtotal = aggregatedProducts.value.reduce((sum, item) => sum + item.total_amount, 0);
    const discountAmount = currentBatch.value?.discount_amount || 0;
    const discountType = currentBatch.value?.discount_type || 'amount';
    const paid = currentBatch.value?.paid_amount || 0;
    const shippingFee = currentBatch.value?.shipping_fee || 0;

    let total = subtotal;
    if (discountType === 'amount') {
        total -= discountAmount;
    } else if (discountType === 'percent') {
        total -= (subtotal * discountAmount) / 100;
    }
    total += shippingFee;

    return total - paid;
});

function handlePayment() {
    if (isUnpaidOrPartial.value) {
        const today = new Date().toISOString().split('T')[0];
        paymentForm.value = {
            paymentAmount: remainingAmount.value,
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
    allowNegative: false,
};

function formatCurrency(value: number | null | undefined) {
    if (value === undefined || value === null) return '0₫';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(value);
}

function formatDateTime(dateString: string | null | undefined) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
    };
    return new Intl.DateTimeFormat('vi-VN', options).format(date);
}

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

defineExpose({});
defineOptions({
    directives: {
        money: Money3Directive,
    },
});

const totalActualQuantity = computed(() => {
    return aggregatedProducts.value.reduce((sum, p) => sum + (p.received_quantity || 0), 0);
});

const paymentMethod = ref('cash');

const today = new Date();
const formattedDate = today.toISOString().split('T')[0];
const showPaymentForm = ref(false);
const paymentForm = ref({
    paymentAmount: 0,
    paymentDate: formattedDate,
    reference: '',
});

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
    <!-- <AppLayout :breadcrumbs="breadcrumbs"> -->
    <AppLayout>
        <div class="min-h-screen bg-gray-50 p-4 no-print">
            <div class="mx-auto max-w-7xl">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <button
                            @click="goBack"
                            class="mb-0 flex h-10 w-10 items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:border-gray-400 hover:text-gray-800"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <h1 class="ml-4 text-3xl font-bold text-gray-900">{{ batchNumber }}</h1>
                        <span
                            class="ml-2 rounded-full px-3 py-1 text-sm font-medium"
                            :class="{
                                'bg-green-100 text-green-700': currentBatch?.receipt_status === 'completed',
                                'bg-yellow-100 text-yellow-700': currentBatch?.receipt_status === 'partially_received',
                                'bg-red-100 text-red-700': currentBatch?.receipt_status === 'cancelled',
                            }"
                        >
                            {{ receiptStatusLabel }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 pr-2">
                        <button
                            v-if="currentBatch?.receipt_status === 'completed' || currentBatch?.receipt_status === 'partially_received'"
                            @click="goReturn"
                            class="flex items-center space-x-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-all duration-300 ease-in-out hover:border-gray-400 hover:bg-gray-100"
                            title="Tạo đơn hoàn trả"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 text-gray-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                />
                            </svg>
                            <span>Hoàn trả</span>
                        </button>
                        <button
                            @click="printOrder"
                            class="flex items-center space-x-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-800 transition hover:border-gray-300 hover:bg-gray-100"
                            title="In đơn"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 text-gray-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 9V4a2 2 0 012-2h8a2 2 0 012 2v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-6 0h4"
                                />
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
                                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Sản phẩm
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Số lượng
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Đơn giá
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Thành tiền
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="p in aggregatedProducts" :key="p.product_id">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center space-x-4">
                                                        <img
                                                            :src="p.product?.image_url || '/storage/piclumen-1747750187180.png'"
                                                            :alt="p.product_name"
                                                            class="h-12 w-12 rounded-lg border border-gray-200 object-cover"
                                                        />
                                                        <div>
                                                            <h4 class="font-medium text-gray-900">{{ p.product_name }}</h4>
                                                            <p class="text-sm text-gray-500">SKU: {{ p.product_sku }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex flex-col">
                                                        <span>{{ p.received_quantity }}</span>
                                                        <span v-if="p.rejected_quantity > 0" class="text-xs text-red-500">
                                                            {{ p.rejected_quantity }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                    <span>{{ formatPrice(p.purchase_price) }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm font-semibold whitespace-nowrap text-gray-900">
                                                    {{ formatPrice(p.total_amount) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 p-4">
                                <div class="flex items-center space-x-2">
                                    <component :is="paymentStatusInfo.icon" class="h-5 w-5" :class="paymentStatusInfo.class" />
                                    <span :class="paymentStatusInfo.class + ' text-base font-semibold lg:text-lg'">
                                        {{ paymentStatusInfo.label }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-4 p-4">
                                <div class="flex items-center justify-between text-sm text-gray-800">
                                    <span class="w-1/3 font-medium">Tổng tiền</span>
                                    <span class="w-1/3 text-center text-gray-600"> {{ totalActualQuantity }} sản phẩm </span>
                                    <span class="w-1/3 text-right font-semibold text-black">
                                        {{ formattedSubtotal }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-blue-600">Chiết khấu lô</span>
                                    <span class="text-right font-semibold text-red-600">
                                        {{ formattedDiscount }}
                                    </span>
                                </div>

                                <div class="border-t border-gray-200 pt-3">
                                    <div class="flex items-center justify-between text-base font-bold text-gray-900">
                                        <span>Tiền cần trả NCC</span>
                                        <span class="text-right">{{ totalAfterDiscount }}</span>
                                    </div>
                                </div>
                                <div
                                    v-if="isPaid || currentBatch?.payment_status === 'partially_paid'"
                                    class="space-y-2 border-t border-gray-200 pt-4"
                                >
                                    <div class="flex justify-between text-sm text-gray-800">
                                        <span class="font-medium">Đã trả</span>
                                        <span class="text-right">{{ formattedPaidAmount }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-800">
                                        <span class="font-medium">Còn phải trả</span>
                                        <span class="text-right text-red-600">{{ formattedRemainingAmount }}</span>
                                    </div>
                                </div>
                                <div v-if="isUnpaidOrPartial && remainingAmount > 0" class="border-t border-gray-200 pt-4">
                                    <div class="flex justify-end pr-2">
                                        <button
                                            @click="handlePayment"
                                            class="rounded-md bg-blue-600 px-2.5 py-1 text-xs text-white hover:bg-blue-700 focus:ring-1 focus:ring-blue-500 focus:ring-offset-1"
                                        >
                                            Xác nhận thanh toán
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="showPaymentForm"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm transition-all duration-300"
                        >
                            <div class="w-full max-w-2xl rounded-lg bg-white p-6">
                                <h2 class="mb-6 text-xl font-semibold">Xác nhận thanh toán</h2>
                                <form @submit.prevent="handlePaymentSubmit">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Hình thức thanh toán</label>
                                            <select
                                                v-model="paymentMethod"
                                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                            >
                                                <option value="cash">Tiền mặt</option>
                                                <option value="bank_transfer">Chuyển khoản</option>
                                                <option value="credit_card">Thẻ</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Số tiền thanh toán</label>
                                            <input
                                                v-model="paymentForm.paymentAmount"
                                                v-money="money"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                            />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày ghi
                                                nhận</label>
                                            <input type="date" v-model="paymentForm.paymentDate"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Tham chiếu</label>
                                            <input
                                                type="text"
                                                v-model="paymentForm.reference"
                                                placeholder="Nhập mã tham chiếu"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                            />
                                        </div>
                                    </div>

                                    <div class="mt-6 flex justify-end space-x-4">
                                        <button
                                            type="button"
                                            @click="cancelPayment"
                                            class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300"
                                        >
                                            Hủy
                                        </button>
                                        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
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
                                        <h3 class="text-black-900 font-bold">Thông tin nhà cung cấp</h3>
                                        <p v-if="selectedSupplier.email" class="text-black-400">{{ selectedSupplier.email }}</p>
                                        <p v-if="selectedSupplier.phone" class="text-black-400">{{ selectedSupplier.phone }}</p>
                                        <p v-if="selectedSupplier.address" class="text-black-400">{{ selectedSupplier.address }}</p>
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
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Nhân viên phụ trách</label>
                                    <input
                                        type="text"
                                        disabled
                                        :value="selectedUser ? selectedUser.name : ''"
                                        class="h-10 w-full rounded-md border border-gray-300 pl-4 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhận hàng</label>
                                    <input
                                        type="text"
                                        disabled
                                        :value="formattedReceivedDate"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
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
        </div>

        <div class="print-only">
            <div class="receipt-container">
                <div class="receipt-header">
                    <h1 class="receipt-title">ĐƠN NHẬP HÀNG</h1>
                </div>

                <div class="receipt-details" v-if="currentBatch && selectedSupplier && selectedUser">
                    <div class="flex-container">
                        <p class="col-6">
                            <span class="label">Nhà cung cấp:</span>
                            <span class="value">{{ selectedSupplier.name }}</span>
                        </p>
                        <p class="col-6">
                            <span class="label">Mã đơn nhập:</span>
                            <span class="value">{{ currentBatch.batch_number }}</span>
                        </p>
                    </div>
                    <div class="flex-container">
                        <p class="col-6">
                            <span class="label">Ngày nhập:</span>
                            <span class="value">{{ formattedReceivedDate }}</span>
                        </p>
                        <p class="col-6">
                            <span class="label">Ngày tạo:</span>
                            <span class="value">{{ new Date(currentBatch.created_at).toLocaleDateString('vi-VN')
                                }}</span>
                        </p>
                    </div>
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
                            <td style="text-align: left;">
                                {{ item.product_name }}
                            </td>
                            <td style="text-align: center;">{{ item.received_quantity }}</td>
                            <td style="text-align: right;">{{ formatPrice(item.purchase_price) }}</td>
                            <td style="text-align: right;">{{ formatPrice(item.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="receipt-summary" v-if="currentBatch">
                    <p>
                        <span class="label">Số lượng:</span>
                        <span class="value">{{ totalActualQuantity }}</span>
                    </p>
                    <p>
                        <span class="label">Tổng tiền:</span>
                        <span class="value">{{ formattedSubtotal }}</span>
                    </p>
                    <p>
                        <span class="label">Chiết khấu:</span>
                        <span class="value">{{ formattedDiscount.replace('-', '') }}</span>
                    </p>

                    <p>
                        <span class="label">Phí vận chuyển:</span>
                        <span class="value">{{ formatCurrency(currentBatch.shipping_fee) }}</span>
                    </p>
                    <div class="divider"></div>
                    <p class="total-line">
                        <span class="label">Tổng giá trị:</span>
                        <span class="value">{{ totalAfterDiscount }}</span>
                    </p>
                </div>

                <div class="receipt-footer">
                    <div class="signature-section">
                        <div class="signature">
                            <p><strong>Người nhập hàng</strong></p>
                            <p>(Ký, họ tên)</p>
                            <br /><br /><br />
                            <p>{{ selectedUser?.name || 'N/A' }}</p>
                        </div>
                        <div class="signature">
                            <p><strong>Thủ kho</strong></p>
                            <p>(Ký, họ tên)</p>
                            <br /><br /><br />
                            <p>.............................</p>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
    </AppLayout>
</template>
<style scoped>
/* Base styles for screen (hide print-only content) */
.print-only {
    display: none;
}

.no-print {
    display: block;
}

/* Styles to hide main content and show print-only content when printing */
@media print {
    .no-print {
        display: none !important;
    }

    .print-only {
        display: block !important;
        visibility: visible !important;
        width: 100%;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        background: white;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        -webkit-print-color-adjust: exact;
    }

    .receipt-container {
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        background: white;
        color: #000;
        font-size: 11pt;
        line-height: 1.6;
    }

    .receipt-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .receipt-title {
        font-size: 24pt;
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }

    .receipt-details {
        margin-bottom: 25px;
        font-size: 11pt;
    }

    .flex-container {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .flex-container .col-6 {
        width: 48%;
        /* Adjust for spacing */
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .receipt-details .label {
        font-weight: bold;
        min-width: 120px;
        text-align: left;
    }

    .receipt-details .value {
        text-align: right;
        flex-grow: 1;
    }

    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        margin-bottom: 20px;
        font-size: 10pt;
    }

    .receipt-table thead th {
        border-bottom: 1px solid #000;
        padding: 8px 5px;
        font-weight: bold;
        text-align: center;
    }

    .receipt-table tbody td {
        padding: 8px 5px;
        border-bottom: 1px dashed #ddd;
        vertical-align: top;
    }

    .receipt-table th:nth-child(2),
    .receipt-table td:nth-child(2) {
        text-align: left;
        padding-left: 2px;
    }

    .receipt-table th:nth-child(3),
    .receipt-table td:nth-child(3) {
        text-align: center;
    }

    .receipt-table th:nth-child(4),
    .receipt-table td:nth-child(4) {
        text-align: right;
    }

    .receipt-table th:nth-child(5),
    .receipt-table td:nth-child(5) {
        text-align: right;
    }

    .receipt-summary {
        margin-top: 20px;
        text-align: right;
        font-size: 11pt;
    }

    .receipt-summary p {
        margin: 0;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 20px;
    }

    .receipt-summary .label,
    .receipt-summary .value {
        width: 150px;
        display: inline-block;
        text-align: left;
    }

    .receipt-summary .value {
        text-align: right;
    }

    .receipt-summary p {
        padding-bottom: 5px;
    }

    .divider {
        border-top: 1px dashed #000;
        margin: 10px 0;
    }

    .receipt-summary .total-line {
        font-size: 12pt;
        font-weight: bold;
        padding-top: 10px;
        margin-top: 10px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 20px;
    }

    .receipt-summary .total-line .label,
    .receipt-summary .total-line .value {
        width: 150px;
        display: inline-block;
        text-align: left;
    }

    .receipt-summary .total-line .value {
        text-align: right;
    }

    .receipt-footer {
        margin-top: 50px;
        text-align: center;
        font-size: 10pt;
    }

    .signature-section {
        display: flex;
        justify-content: space-around;
        text-align: center;
    }

    .signature {
        width: 40%;
    }

    .signature p {
        margin: 0;
    }
}
</style>
