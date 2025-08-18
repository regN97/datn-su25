<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
<<<<<<< HEAD
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
=======
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chi tiết tồn kho',
        href: '/admin/inventory',
    },
];

// Khai báo kiểu dữ liệu cho product
interface Product {
    id: number;
    name: string;
    image_url: string;
    sku: string | null;
    barcode: number;
    stock_quantity: number;
    selling_price: number;
    description: string | null;
    category?: { id: number; name: string };
    unit?: { id: number; name: string };
    suppliers?: { id: number; name: string; pivot?: { purchase_price?: number } }[];
}

interface Unit {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
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

interface Batch {
    id: number;
    batch_number: string;
    purchase_order_id: number;
    supplier_id: number | null;
    supplier?: Supplier | null;
    received_date: string;
    invoice_number: string | null;
    total_amount: number;
    payment_status: 'unpaid' | 'partially_paid' | 'paid';
    paid_amount: number;
    receipt_status: 'partially_received' | 'completed' | 'cancelled';
    notes: string | null;
    created_by: number;
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
// Nhận prop product từ Inertia
const props = defineProps<{
    product: Product;
    unit: Unit;
    category: Category;
    batch: Batch[];
    batchItems: BatchItem[];
}>();

function formatCurrency(value: number | null): string {
    if (value === null || isNaN(value)) return 'N/A';
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
}

function getBatchNumber(batchId: number): string {
    const foundBatch = props.batch.find(b => b.id === batchId);
    return foundBatch ? foundBatch.batch_number : `#${batchId}`;
}

<<<<<<< HEAD
// Initialize Inertia form
const form = useForm({
    batchItems: props.batchItems.map((item) => ({
        id: item.id,
        current_quantity: item.current_quantity,
    })),
});

const editableBatchItems = ref<BatchItem[]>(props.batchItems.map((item) => ({ ...item })));

const parsedCategoryName = computed(() => {
    try {
        if (typeof props.product.category === 'string') {
            const categoryObj = JSON.parse(props.product.category);
            return categoryObj?.name || 'Không xác định';
        } else if (typeof props.product.category === 'object' && props.product.category !== null) {
            return (props.product.category as any)?.name || 'Không xác định';
        }
    } catch (e) {
        console.error('Lỗi phân tích danh mục:', e);
    }
    return 'Không xác định';
});

const formatDate = (date: string | null): string => {
    if (!date) return '-';
    const d = new Date(date);
    return d && !isNaN(d.getTime()) ? d.toLocaleDateString('vi-VN') : '-';
};

const getInventoryStatusText = (status: BatchItem['inventory_status']): string => {
    switch (status) {
        case 'active':
            return 'Đang hoạt động';
        case 'low_stock':
            return 'Sắp hết hàng';
        case 'out_of_stock':
            return 'Hết hàng';
        case 'expired':
            return 'Hết hạn';
        default:
            return status;
    }
};

const getPaymentStatusText = (status: BatchItem['payment_status']): string => {
    switch (status) {
        case 'unpaid':
            return 'Chưa thanh toán';
        case 'partially_paid':
            return 'Đã thanh toán một phần';
        case 'paid':
            return 'Đã thanh toán';
        default:
            return status;
    }
};

const handleQuantityChange = (item: BatchItem) => {
    if (item.current_quantity < 0 || isNaN(item.current_quantity)) {
        item.current_quantity = 0;
        toastMessage.value = 'Số lượng hiện tại không được nhỏ hơn 0';
        toastType.value = 'error';
    } else if (item.current_quantity > item.ordered_quantity) {
        item.current_quantity = item.ordered_quantity;
        toastMessage.value = `Số lượng hiện tại không được lớn hơn số lượng đặt (${item.ordered_quantity})`;
        toastType.value = 'error';
    } else if (item.current_quantity > item.received_quantity) {
        item.current_quantity = item.received_quantity;
        toastMessage.value = `Số lượng hiện tại không được lớn hơn số lượng nhập (${item.received_quantity})`;
        toastType.value = 'error';
    }

    // Sync form data with editableBatchItems
    const formItem = form.batchItems.find((formItem) => formItem.id === item.id);
    if (formItem) {
        formItem.current_quantity = item.current_quantity;
    }

    if (toastMessage.value) {
        setTimeout(() => {
            toastMessage.value = null;
            toastType.value = null;
        }, 3000);
    }
};

const updateInventory = async () => {
    const invalidItems = editableBatchItems.value.filter(
        (item) =>
            item.current_quantity < 0 ||
            isNaN(item.current_quantity) ||
            item.current_quantity > item.ordered_quantity ||
            item.current_quantity > item.received_quantity,
    );
    if (invalidItems.length > 0) {
        toastMessage.value = 'Vui lòng nhập số lượng hợp lệ (không âm, không lớn hơn số lượng đặt hoặc số lượng nhập) cho tất cả các lô.';
        toastType.value = 'error';
        setTimeout(() => {
            toastMessage.value = null;
            toastType.value = null;
        }, 3000);
        return;
    }

    if (!confirm('Bạn có chắc muốn cập nhật tồn kho?')) return;

    isLoading.value = true;
    form.put(`/admin/inventory/${props.product.product_id}`, {
        onSuccess: () => {
            toastMessage.value = 'Cập nhật tồn kho thành công!';
            toastType.value = 'success';
            // Reset form errors
            form.reset();
        },
        onError: (errors) => {
            toastMessage.value = Object.values(errors).join(' ') || 'Cập nhật tồn kho thất bại. Vui lòng thử lại.';
            toastType.value = 'error';
        },
        onFinish: () => {
            isLoading.value = false;
            setTimeout(() => {
                toastMessage.value = null;
                toastType.value = null;
            }, 3000);
        },
    });
};

const firstBatchItemDetails = computed(() => {
    if (editableBatchItems.value.length > 0) {
        const firstItem = editableBatchItems.value[0];
        return {
            supplierName: firstItem.supplier_name || 'Không xác định',
            paymentStatus: getPaymentStatusText(firstItem.payment_status),
            purchasePrice: firstItem.purchase_price.toLocaleString('vi-VN') + ' VNĐ',
            totalItemAmount: firstItem.total_item_amount.toLocaleString('vi-VN') + ' VNĐ',
            notes: firstItem.notes || '-',
        };
    }
    return {
        supplierName: 'Không có thông tin',
        paymentStatus: 'Không có thông tin',
        purchasePrice: 'N/A',
        totalItemAmount: 'Không có thông tin',
        notes: 'N/A',
    };
});
</script>

<template>
    <Head :title="`Chi tiết tồn kho: ${product.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            v-if="toastMessage"
            :class="{
                'bg-green-500 text-white dark:bg-green-600': toastType === 'success',
                'bg-red-500 text-white dark:bg-red-600': toastType === 'error',
            }"
            class="fixed top-4 right-4 z-50 rounded-lg p-4 shadow-lg"
        >
            {{ toastMessage }}
        </div>

        <div class="flex flex-col gap-6 p-6">
            <div class="rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                <div class="rounded-t-lg bg-gray-100 p-4 dark:bg-gray-900">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Thông tin sản phẩm</h2>
                </div>
                <div class="grid grid-cols-1 items-start gap-4 p-6 text-sm md:grid-cols-3">
                    <div class="flex justify-center md:col-span-1">
                        <div v-if="product.image_url" class="rounded-md border p-2 shadow-sm dark:border-gray-700">
                            <img :src="product.image_url" :alt="product.name" class="h-32 w-32 rounded-md object-contain" />
                        </div>
                        <div
                            v-else
                            class="flex h-32 w-32 items-center justify-center rounded-md border border-gray-300 bg-gray-100 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400"
                        >
                            Không có ảnh
=======
function goBack() {
    router.visit('/admin/inventory');
}
</script>

<template>
    <Head title="Chi tiết tồn kho" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="no-print min-h-screen bg-gray-50 p-6">
            <div class="mx-auto max-w-7xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button @click="goBack" class="h-9 w-9 rounded border border-gray-300 bg-white text-gray-700 hover:border-gray-400">←</button>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">{{ props.product.name }}</h1>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                        </div>
                    </div>
                </div>

<<<<<<< HEAD
                    <div class="space-y-2 md:col-span-1">
                        <p><strong class="mr-2">Tên sản phẩm:</strong> {{ product.name }}</p>
                        <p><strong class="mr-2">Danh mục:</strong> {{ parsedCategoryName }}</p>
                        <p><strong class="mr-2">Đơn vị:</strong> {{ product.unit }}</p>
                        <p><strong class="mr-2">Nhà cung cấp:</strong> {{ firstBatchItemDetails.supplierName }}</p>
                        <p>
                            <strong class="mr-2">Trạng thái thanh toán:</strong>
                            <span
                                :class="{
                                    'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200':
                                        firstBatchItemDetails.paymentStatus === 'Chưa thanh toán',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100':
                                        firstBatchItemDetails.paymentStatus === 'Đã thanh toán một phần',
                                    'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100':
                                        firstBatchItemDetails.paymentStatus === 'Đã thanh toán',
                                }"
                                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                            >
                                {{ firstBatchItemDetails.paymentStatus }}
                            </span>
                        </p>
=======
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thông tin sản phẩm</h2>
                            </div>
                            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tên sản phẩm <span class="text-red-500">*</span></label>
                                    <input
                                        type="text"
                                        class="mt-4 block w-full rounded-md border-gray-300 px-2 py-2 shadow-sm"
                                        :value="props.product.name"
                                        disabled
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mã SKU</label>
                                    <input
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-2 py-2 shadow-sm"
                                        :value="props.product.sku"
                                        disabled
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mã vạch/Barcode</label>
                                    <input
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-2 py-2 shadow-sm"
                                        :value="props.product.barcode"
                                        disabled
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nhập đơn vị tính</label>
                                    <input
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 px-2 py-2 shadow-sm"
                                        :value="props.unit.name"
                                        disabled
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Giá bán</label>
                                    <input
                                        type="text"
                                        :value="formatCurrency(props.product.selling_price)"
                                        disabled
                                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 px-2 py-2 shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Giá vốn</label>
                                    <input
                                        type="text"
                                        value="Đang cập nhật"
                                        disabled
                                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 px-2 py-2 text-gray-400 shadow-sm"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mô tả</label>
                                        <textarea
                                            class="mt-2 block w-full rounded-md border-gray-300 px-2 py-2 shadow-sm"
                                            :value="props.product.description"
                                            rows="4"
                                            disabled
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                    </div>
                    <div class="flex flex-col gap-6">
                        <!-- Ảnh sản phẩm + danh mục -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="space-y-4 p-6">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ảnh sản phẩm</label>
                                    <div v-if="props.product">
                                        <img :src="props.product.image_url" alt="Ảnh sản phẩm" class="h-20 w-20 object-cover" />
                                    </div>
                                    <div v-else class="text-sm text-gray-500">Không có ảnh</div>
                                </div>

<<<<<<< HEAD
                    <div class="space-y-2 md:col-span-1">
                        <p><strong class="mr-2">Tổng tồn kho:</strong> {{ totalInventory.toLocaleString('vi-VN') }} {{ product.unit }}</p>

                        <p v-if="product.sku"><strong class="mr-2">SKU:</strong> {{ product.sku }}</p>
                        <p v-if="product.status"><strong class="mr-2">Trạng thái:</strong> {{ product.status }}</p>
                        <p v-if="product.price"><strong class="mr-2">Giá bán:</strong> {{ product.price.toLocaleString('vi-VN') }} VNĐ</p>
                        <p><strong class="mr-2">Giá mua:</strong> {{ firstBatchItemDetails.purchasePrice }}</p>
                        <p><strong class="mr-2">Tổng giá trị:</strong> {{ firstBatchItemDetails.totalItemAmount }}</p>
                    </div>

                    <div v-if="product.description" class="mt-4 space-y-2 border-t pt-4 md:col-span-3 dark:border-gray-700">
                        <p><strong class="mr-2">Mô tả sản phẩm:</strong> {{ product.description }}</p>
                    </div>
                    <div class="space-y-2 md:col-span-3" :class="{ 'mt-4 border-t pt-4 dark:border-gray-700': !product.description }">
                        <p><strong class="mr-2">Ghi chú:</strong> {{ firstBatchItemDetails.notes }}</p>
=======
                                <!-- Danh mục -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                                    <input
                                        type="text"
                                        :value="props.category.name"
                                        disabled
                                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 px-2 py-2 shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Giá bán và giá vốn -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold text-gray-800">Thông tin tồn kho</h2>
                            </div>
                            <div class="space-y-4 p-6">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Tồn kho</label>
                                    <input
                                        type="number"
                                        class="block w-full rounded-md border-gray-300 px-2 py-2 shadow-sm"
                                        :value="props.product.stock_quantity"
                                        disabled
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Có thể bán</label>
                                    <input
                                        type="text"
                                        class="block w-full rounded-md border-gray-300 px-2 py-2 shadow-sm"
                                        :value="props.product.stock_quantity"
                                        disabled
                                    />
                                </div>
                            </div>
                        </div>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thông tin lô hàng</h2>
                            </div>
                            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã lô</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày sản xuất</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hạn sử dụng</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tồn kho</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-if="!Array.isArray(props.batchItems) || props.batchItems.length === 0">
                                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Không có dữ liệu lô hàng</td>
                                                </tr>
                                                <template v-else>
                                                    <tr v-for="item in props.batchItems" :key="item.id" class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ getBatchNumber(item.batch_id) }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <span :class="{
                                                                'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                                                'bg-green-100 text-green-800': item.inventory_status === 'active',
                                                                'bg-yellow-100 text-yellow-800': item.inventory_status === 'low_stock',
                                                                'bg-red-100 text-red-800': item.inventory_status === 'out_of_stock' || item.inventory_status === 'expired',
                                                                'bg-gray-100 text-gray-800': item.inventory_status === 'damaged'
                                                            }">
                                                                {{ item.inventory_status === 'active' ? 'Còn hàng' :
                                                                item.inventory_status === 'low_stock' ? 'Sắp hết' :
                                                                item.inventory_status === 'out_of_stock' ? 'Hết hàng' :
                                                                item.inventory_status === 'expired' ? 'Hết hạn' : 'Hư hỏng' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.manufacturing_date ? new Date(item.manufacturing_date).toLocaleDateString('vi-VN') : 'N/A' }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.expiry_date ? new Date(item.expiry_date).toLocaleDateString('vi-VN') : 'N/A' }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.current_quantity }}</td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
<<<<<<< HEAD
                <div class="overflow-x-auto p-4 text-sm">
                    <table class="w-full table-auto text-left">
                        <thead class="bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <tr>
                                <th class="p-3">Số lô</th>
                                <th class="p-3">Ngày nhận</th>
                                <th class="p-3">Số lượng đặt</th>
                                <th class="p-3">Số lượng nhập</th>
                                <th class="p-3">Số lượng hiện tại</th>
                                <th class="p-3">Trạng thái tồn kho</th>
                                <th class="p-3">Ngày sản xuất</th>
                                <th class="p-3">Ngày hết hạn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in editableBatchItems"
                                :key="item.id"
                                class="border-b hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900"
                            >
                                <td class="p-3">{{ item.batch_number }}</td>
                                <td class="p-3">{{ formatDate(item.received_date) }}</td>
                                <td class="p-3">{{ item.ordered_quantity }}</td>
                                <td class="p-3">{{ item.received_quantity }}</td>
                                <td class="p-3">
                                    <input
                                        type="number"
                                        v-model.number="item.current_quantity"
                                        @input="handleQuantityChange(item)"
                                        min="0"
                                        :max="Math.min(item.ordered_quantity, item.received_quantity)"
                                        class="w-full rounded border border-gray-300 bg-gray-50 p-1 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </td>
                                <td class="p-3">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100': item.inventory_status === 'active',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100':
                                                item.inventory_status === 'low_stock',
                                            'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100':
                                                item.inventory_status === 'out_of_stock' || item.inventory_status === 'expired',
                                        }"
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                    >
                                        {{ getInventoryStatusText(item.inventory_status) }}
                                    </span>
                                </td>
                                <td class="p-3">{{ formatDate(item.manufacturing_date) }}</td>
                                <td class="p-3">{{ formatDate(item.expiry_date) }}</td>
                            </tr>
                            <tr v-if="!editableBatchItems.length" class="border-b dark:border-gray-700">
                                <td class="p-3 text-center" colspan="8">Không có dữ liệu tồn kho</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex justify-center gap-4">
                <a
                    :href="`/admin/inventory`"
                    class="inline-flex items-center rounded bg-gray-200 px-4 py-2 text-gray-800 transition duration-150 ease-in-out hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    Quay lại danh sách
                </a>
                <button
                    @click="updateInventory"
                    :disabled="isLoading"
                    class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-white transition duration-150 ease-in-out hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span v-if="isLoading" class="mr-2">
                        <svg class="h-5 w-5 animate-spin text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                    </span>
                    {{ isLoading ? 'Đang cập nhật...' : 'Cập nhật tồn kho' }}
                </button>
=======
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
            </div>
        </div>
    </AppLayout>
</template>
<<<<<<< HEAD

<style scoped>
.table-auto {
    min-width: 100%;
    table-layout: auto;
}

.dark .text-gray-900 {
    color: #e5e7eb;
}

.dark .bg-gray-50 {
    background-color: #374151;
}

.dark .border-gray-300 {
    border-color: #4b5563;
}
</style>
=======
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
