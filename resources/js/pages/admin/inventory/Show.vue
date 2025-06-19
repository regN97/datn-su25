<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface Product {
    product_id: number;
    name: string;
    category: string | object;
    unit: string;
    image_url?: string | null;
    sku?: string | null;
    description?: string | null;
    price?: number | null;
    status?: string | null;
}

interface BatchItem {
    id: number;
    batch_id: number;
    batch_number: string;
    purchase_order_id: number | null;
    supplier_id: number | null;
    supplier_name: string;
    received_date: string | null;
    total_amount: number;
    payment_status: 'unpaid' | 'partially_paid' | 'paid';
    receipt_status: string;
    notes: string | null;
    ordered_quantity: number;
    received_quantity: number;
    current_quantity: number;
    purchase_price: number;
    total_item_amount: number;
    inventory_status: 'active' | 'low_stock' | 'out_of_stock' | 'expired';
    manufacturing_date: string | null;
    expiry_date: string | null;
}

const props = defineProps<{
    product: Product;
    batchItems: BatchItem[];
    totalInventory: number;
    lowStock: number;
    expired: number;
    suppliers: Record<number, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lý tồn kho', href: '/admin/inventory' },
    { title: `Chi tiết tồn kho: ${props.product.name}`, href: `/admin/inventory/${props.product.product_id}` },
];

const isLoading = ref(false);
const toastMessage = ref<string | null>(null);
const toastType = ref<'success' | 'error' | null>(null);

const editableBatchItems = ref<BatchItem[]>(
    props.batchItems.map(item => ({ ...item }))
);

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
        case 'active': return 'Đang hoạt động';
        case 'low_stock': return 'Sắp hết hàng';
        case 'out_of_stock': return 'Hết hàng';
        case 'expired': return 'Hết hạn';
        default: return status;
    }
};

const getPaymentStatusText = (status: BatchItem['payment_status']): string => {
    switch (status) {
        case 'unpaid': return 'Chưa thanh toán';
        case 'partially_paid': return 'Đã thanh toán một phần';
        case 'paid': return 'Đã thanh toán';
        default: return status;
    }
};

const handleQuantityChange = (item: BatchItem) => {
    if (item.current_quantity < 0 || isNaN(item.current_quantity)) {
        item.current_quantity = 0;
    } else if (item.current_quantity > item.received_quantity) {
        item.current_quantity = item.received_quantity; // Giới hạn current_quantity bằng received_quantity
        toastMessage.value = `Số lượng hiện tại không được lớn hơn số lượng nhập (${item.received_quantity})`;
        toastType.value = 'error';
        setTimeout(() => { toastMessage.value = null; toastType.value = null; }, 3000);
    }
};

const updateInventory = async () => {
    const invalidItems = editableBatchItems.value.filter(
        item => item.current_quantity < 0 ||
            isNaN(item.current_quantity) ||
            item.current_quantity > item.received_quantity
    );
    if (invalidItems.length > 0) {
        toastMessage.value = 'Vui lòng nhập số lượng hợp lệ (không âm và không lớn hơn số lượng nhập) cho tất cả các lô.';
        toastType.value = 'error';
        setTimeout(() => { toastMessage.value = null; toastType.value = null; }, 3000);
        return;
    }

    if (!confirm('Bạn có chắc muốn cập nhật tồn kho?')) return;

    isLoading.value = true;
    try {
        const payload = editableBatchItems.value.map(item => ({
            id: item.id,
            current_quantity: item.current_quantity,
        }));

        await router.post(`/admin/inventory/${props.product.product_id}`, {
            batchItems: payload,
        });
        toastMessage.value = 'Cập nhật tồn kho thành công!';
        toastType.value = 'success';
    } catch (error) {
        console.error('Cập nhật thất bại:', error);
        toastMessage.value = 'Cập nhật tồn kho thất bại. Vui lòng thử lại.';
        toastType.value = 'error';
    } finally {
        isLoading.value = false;
        setTimeout(() => { toastMessage.value = null; toastType.value = null; }, 3000);
    }
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
        <div v-if="toastMessage" :class="{
            'bg-green-500 text-white dark:bg-green-600': toastType === 'success',
            'bg-red-500 text-white dark:bg-red-600': toastType === 'error'
        }" class="fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50">
            {{ toastMessage }}
        </div>

        <div class="flex flex-col gap-6 p-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-t-lg">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                        Thông tin sản phẩm
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 items-start text-sm">
                    <div class="flex justify-center md:col-span-1">
                        <div v-if="product.image_url" class="p-2 border rounded-md shadow-sm dark:border-gray-700">
                            <img :src="product.image_url" :alt="product.name"
                                class="w-32 h-32 object-contain rounded-md" />
                        </div>
                        <div v-else
                            class="w-32 h-32 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-md text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600">
                            Không có ảnh
                        </div>
                    </div>

                    <div class="space-y-2 md:col-span-1">
                        <p><strong class="mr-2">Tên sản phẩm:</strong> {{ product.name }}</p>
                        <p><strong class="mr-2">Danh mục:</strong> {{ parsedCategoryName }}</p>
                        <p><strong class="mr-2">Đơn vị:</strong> {{ product.unit }}</p>
                        <p v-if="product.sku"><strong class="mr-2">SKU:</strong> {{ product.sku }}</p>
                        <p v-if="product.status"><strong class="mr-2">Trạng thái:</strong> {{ product.status }}</p>
                        <p><strong class="mr-2">Nhà cung cấp:</strong> {{ firstBatchItemDetails.supplierName }}</p>
                        <p><strong class="mr-2">Trạng thái thanh toán:</strong>
                            <span :class="{
                                'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200': firstBatchItemDetails.paymentStatus === 'Chưa thanh toán',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100': firstBatchItemDetails.paymentStatus === 'Đã thanh toán một phần',
                                'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100': firstBatchItemDetails.paymentStatus === 'Đã thanh toán'
                            }" class="inline-flex px-2 py-1 text-xs font-medium rounded-full">
                                {{ firstBatchItemDetails.paymentStatus }}
                            </span>
                        </p>
                    </div>

                    <div class="space-y-2 md:col-span-1">
                        <p><strong class="mr-2">Tổng tồn kho:</strong> {{ totalInventory.toLocaleString('vi-VN') }} {{
                            product.unit }}</p>
                        <p><strong class="mr-2">Số lượng thấp:</strong> {{ lowStock.toLocaleString('vi-VN') }} {{
                            product.unit }}</p>
                        <p><strong class="mr-2">Hết hạn:</strong> {{ expired.toLocaleString('vi-VN') }} {{ product.unit
                            }}</p>
                        <p v-if="product.price"><strong class="mr-2">Giá bán:</strong> {{
                            product.price.toLocaleString('vi-VN') }} VNĐ</p>
                        <p><strong class="mr-2">Giá mua:</strong> {{ firstBatchItemDetails.purchasePrice }}</p>
                        <p><strong class="mr-2">Tổng giá trị:</strong> {{ firstBatchItemDetails.totalItemAmount }}</p>
                    </div>

                    <div v-if="product.description"
                        class="md:col-span-3 space-y-2 pt-4 border-t dark:border-gray-700 mt-4">
                        <p><strong class="mr-2">Mô tả sản phẩm:</strong> {{ product.description }}</p>
                    </div>
                    <div class="md:col-span-3 space-y-2"
                        :class="{ 'pt-4 border-t dark:border-gray-700 mt-4': !product.description }">
                        <p><strong class="mr-2">Ghi chú:</strong> {{ firstBatchItemDetails.notes }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded-t-lg">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                        Chi tiết tồn kho theo lô
                    </h2>
                </div>
                <div class="p-4 overflow-x-auto text-sm">
                    <table class="w-full text-left table-auto">
                        <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200">
                            <tr>
                                <th class="p-3">Số lô</th>
                                <th class="p-3">Ngày nhận</th>
                                <th class="p-3">Số lượng hiện tại</th>
                                <th class="p-3">Trạng thái tồn kho</th>
                                <th class="p-3">Ngày sản xuất</th>
                                <th class="p-3">Ngày hết hạn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in editableBatchItems" :key="item.id"
                                class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900">
                                <td class="p-3">{{ item.batch_number }}</td>
                                <td class="p-3">{{ formatDate(item.received_date) }}</td>
                                <td class="p-3">
                                    <input type="number" v-model.number="item.current_quantity"
                                        @input="handleQuantityChange(item)" min="0" :max="item.received_quantity"
                                        class="w-full p-1 border rounded text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-blue-500 focus:border-blue-500 text-sm" />
                                </td>
                                <td class="p-3">
                                    <span :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100': item.inventory_status === 'active',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100': item.inventory_status === 'low_stock',
                                        'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100': item.inventory_status === 'out_of_stock' || item.inventory_status === 'expired'
                                    }" class="inline-flex px-2 py-1 text-xs font-medium rounded-full">
                                        {{ getInventoryStatusText(item.inventory_status) }}
                                    </span>
                                </td>
                                <td class="p-3">{{ formatDate(item.manufacturing_date) }}</td>
                                <td class="p-3">{{ formatDate(item.expiry_date) }}</td>
                            </tr>
                            <tr v-if="!editableBatchItems.length" class="border-b dark:border-gray-700">
                                <td class="p-3 text-center" colspan="6">Không có dữ liệu tồn kho</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-center gap-4 mt-4">
                <a :href="`/admin/inventory`"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition duration-150 ease-in-out">
                    Quay lại danh sách
                </a>
                <button @click="updateInventory" :disabled="isLoading"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out">
                    <span v-if="isLoading" class="mr-2">
                        <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                fill="none" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                    </span>
                    {{ isLoading ? 'Đang cập nhật...' : 'Cập nhật tồn kho' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>

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