<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import Swal from 'sweetalert2'; // <--- Dòng này đã được thêm vào

// Define props
const props = defineProps<{
    purchaseOrder: {
        id: number | null;
        code: string;
        supplier_name: string;
        supplier_email?: string;
        supplier_phone?: string;
        supplier_address?: string;
        supplier_avatar_url?: string;
        items: {
            product_id: number;
            product_name: string;
            product_sku: string;
            quantity_received: number;
            unit_cost: number;
            product_image_url?: string | null;
            batch_id?: number | null;
            purchase_order_item_id?: number | null;
            reason?: string | null;
            batch_number?: string | null;
            manufacturing_date?: string | null;
            expiry_date?: string | null;
        }[];
    } | null;
    currentLocationName: string;
    error?: string;
}>();

// Data for the return form
const returnForm = ref({
    purchase_order_id: props.purchaseOrder?.id || null,
    return_order_code: props.purchaseOrder ? `TRA-${props.purchaseOrder.code}` : '',
    reason: '',
    return_items: [] as any[],
    additional_cost: 0,
    deduction: 0,
    tags: [] as string[],
});

const formatMoney = (amount: number) => {
    return amount.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
};

const getImage = (url?: string | null) => {
    if (!url) return '/apple-touch-icon.png';
    if (url.startsWith('http')) return url;
    if (url.startsWith('/storage')) return url;
    return `/storage/${url}`;
};

const totalReturnProductValue = computed(() => {
    return returnForm.value.return_items.reduce((sum, item) => {
        return sum + (item.quantity_to_return * item.unit_cost);
    }, 0);
});

const totalRefundValue = computed(() => {
    return totalReturnProductValue.value - returnForm.value.additional_cost - returnForm.value.deduction;
});

const cancelReturn = () => {
    if (confirm('Bạn có chắc muốn hủy tạo đơn trả hàng? Các thay đổi sẽ không được lưu.')) {
        router.visit(route('admin.purchaseReturn.index'));
    }
};

const createReturnOrder = () => {
    const supplier_id = props.purchaseOrder?.supplier_id;
    const purchase_order_id = props.purchaseOrder?.id;
    const return_date = new Date().toISOString().slice(0, 10);

    if (!supplier_id || !purchase_order_id) {
        alert('Không có thông tin đơn nhập hoặc nhà cung cấp!');
        return;
    }

    const itemsToReturn: any[] = [];
    returnForm.value.return_items.forEach(groupedItem => {
        let remainingQuantityToReturn = groupedItem.quantity_to_return;

        if (remainingQuantityToReturn > 0) {
            // Lấy tất cả các item gốc từ mảng đã lưu trong groupedItem
            const originalItems = groupedItem.original_items;

            originalItems?.forEach((originalItem: any) => {
                if (remainingQuantityToReturn > 0) {
                    const quantityFromOriginalItem = Math.min(
                        remainingQuantityToReturn,
                        originalItem.quantity_received
                    );

                    if (quantityFromOriginalItem > 0) {
                        itemsToReturn.push({
                            product_id: originalItem.product_id,
                            product_name: originalItem.product_name,
                            product_sku: originalItem.product_sku,
                            quantity_returned: quantityFromOriginalItem,
                            unit_cost: originalItem.unit_cost,
                            reason: groupedItem.reason ?? null,

                            // Gán các thông tin chi tiết về lô hàng từ item gốc
                            purchase_order_item_id: originalItem.purchase_order_item_id,
                            batch_id: originalItem.batch_id,
                            batch_number: originalItem.batch_number,
                            manufacturing_date: originalItem.manufacturing_date,
                            expiry_date: originalItem.expiry_date,
                        });
                        remainingQuantityToReturn -= quantityFromOriginalItem;
                    }
                }
            });
        }
    });

    if (itemsToReturn.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cảnh báo!',
            text: 'Vui lòng chọn ít nhất một sản phẩm để trả hàng.',
        });
        return;
    }

    router.post(route('admin.purchaseReturn.store'), {
        supplier_id,
        purchase_order_id,
        return_date,
        reason: returnForm.value.reason,
        items: itemsToReturn,
    }, {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: 'Đơn trả hàng đã được tạo thành công!',
            });
            router.visit(route('admin.purchaseReturn.index'));
        },
        onError: (errors) => {
            console.error('Lỗi khi tạo đơn trả hàng:', errors);
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Có lỗi xảy ra khi tạo đơn trả hàng. Vui lòng kiểm tra console để biết chi tiết.',
            });
        }
    });
};

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu trả hàng',
        href: route('admin.purchaseReturn.index'),
    },
    {
        title: 'Tạo đơn trả hàng',
        href: '#',
    },
];

// Logic for product quantity
const updateQuantity = (productId: number, newQuantity: string | number) => {
    const item = returnForm.value.return_items.find(i => i.product_id === productId);
    if (item) {
        let quantity = Number(newQuantity);
        if (isNaN(quantity) || quantity < 0) {
            quantity = 0;
        } else if (quantity > item.max_quantity_can_return) {
            quantity = item.max_quantity_can_return;
        }
        item.quantity_to_return = quantity;
    }
};

const decreaseQuantity = (productId: number) => {
    const item = returnForm.value.return_items.find(i => i.product_id === productId);
    if (item && item.quantity_to_return > 0) {
        item.quantity_to_return--;
    }
};

const increaseQuantity = (productId: number) => {
    const item = returnForm.value.return_items.find(i => i.product_id === productId);
    if (item && item.quantity_to_return < item.max_quantity_can_return) {
        item.quantity_to_return++;
    }
};

const removeProduct = (productId: number) => {
    returnForm.value.return_items = returnForm.value.return_items.filter(item => item.product_id !== productId);
};

// Watch for props changes
watch(() => props.purchaseOrder, (newPurchaseOrder) => {
    if (newPurchaseOrder) {
        returnForm.value.purchase_order_id = newPurchaseOrder.id;
        returnForm.value.return_order_code = `TRA-${newPurchaseOrder.code}`;

        const aggregatedItems: any[] = [];
        const itemMap = new Map();

        newPurchaseOrder.items.forEach(item => {
            if (itemMap.has(item.product_id)) {
                const existingItem = itemMap.get(item.product_id);
                existingItem.max_quantity_can_return += item.quantity_received;
                // Lưu item gốc vào mảng original_items
                existingItem.original_items.push(item);
            } else {
                const newItem = {
                    product_id: item.product_id,
                    product_name: item.product_name,
                    product_sku: item.product_sku,
                    product_image_url: item.product_image_url,
                    quantity_to_return: 0,
                    max_quantity_can_return: item.quantity_received,
                    unit_cost: item.unit_cost,
                    reason: null,
                    // Lưu item gốc vào mảng original_items
                    original_items: [item],
                };
                aggregatedItems.push(newItem);
                itemMap.set(item.product_id, newItem);
            }
        });

        returnForm.value.return_items = aggregatedItems;
    } else {
        returnForm.value.purchase_order_id = null;
        returnForm.value.return_order_code = '';
        returnForm.value.return_items = [];
    }
}, { immediate: true });
</script>

<template>

    <Head title="Tạo đơn trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 bg-gray-50 min-h-screen">
            <div class="flex justify-between items-center bg-white shadow-sm p-4 rounded-lg mb-6 sticky top-0 z-10">
                <div class="flex items-center">
                    <button @click="router.visit(route('admin.purchaseReturn.index'))"
                        class="text-gray-600 hover:text-gray-900 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-900">Tạo đơn trả hàng
                        <span v-if="props.purchaseOrder">
                            cho lô {{ props.purchaseOrder.code }}
                        </span>
                    </h1>
                </div>
                <div class="flex space-x-3">
                    <button @click="cancelReturn"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium">
                        Hủy
                    </button>
                    <button @click="createReturnOrder"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                        Tạo đơn trả hàng nhập
                    </button>
                </div>
            </div>

            <div v-if="props.error"
                class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Lỗi!</strong>
                <span class="block sm:inline"> {{ props.error }}</span>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-md font-semibold text-gray-700">Sản phẩm hoàn trả</h2>
                            <span class="text-sm text-gray-500 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="size-4 text-blue-500 mr-1">
                                    <path fill-rule="evenodd"
                                        d="m11.54 22.351.07.074a49.11 49.11 0 0 0 7.755-5.917c1.83-1.879 3.12-4.18 3.551-6.66.443-2.527-.47-5.002-2.38-6.76a.75.75 0 0 0-1.077-.074L17.7 4.908.941 18.271a.75.75 0 0 0 .074 1.077l2.84 2.839a.75.75 0 0 0 1.07-.077ZM9.5 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ props.currentLocationName }}
                            </span>
                        </div>
                        <div v-if="!props.purchaseOrder" class="text-center py-4 text-gray-600">
                            Vui lòng chọn một đơn nhập để tạo đơn trả hàng.
                        </div>
                        <table v-else class="w-full text-sm border-t border-gray-200">
                            <thead>
                                <tr class="text-left text-gray-500 bg-gray-50">
                                    <th class="py-2 px-2">Sản phẩm</th>
                                    <th class="py-2 px-2 text-center">Số lượng</th>
                                    <th class="py-2 px-2 text-center">Đơn giá</th>
                                    <th class="py-2 px-2 text-right">Thành tiền</th>
                                    <th class="py-2 px-2 text-center">Lý do trả</th>
                                    <th class="py-2 px-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in returnForm.return_items" :key="index"
                                    class="border-b border-gray-100 last:border-b-0">
                                    <td class="py-3 px-2 flex items-center">
                                        <img :src="getImage(item.product_image_url)" :alt="item.product_name"
                                            class="w-10 h-10 rounded mr-2 object-cover"
                                            @error="(e) => (e.target as HTMLImageElement).src = '/apple-touch-icon.png'" />
                                        <div>
                                            <div class="text-gray-800 font-medium">{{ item.product_name }}</div>
                                            <div class="text-xs text-gray-500">SKU: {{ item.product_sku }}</div>
                                        </div>
                                    </td>
                                    <td class="px-2 text-center">
                                        <div class="flex items-center justify-center border border-gray-300 rounded-md overflow-hidden mx-auto"
                                            style="width: 100px;">
                                            <button @click="decreaseQuantity(item.product_id)"
                                                :disabled="item.quantity_to_return <= 0"
                                                class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                                -
                                            </button>
                                            <input type="number" :value="item.quantity_to_return"
                                                @input="updateQuantity(item.product_id, ($event.target as HTMLInputElement).value)"
                                                @blur="updateQuantity(item.product_id, ($event.target as HTMLInputElement).value)"
                                                min="0" :max="item.max_quantity_can_return"
                                                class="w-10 text-center border-x border-gray-300 text-gray-800 focus:outline-none transition duration-150 ease-in-out" />
                                            <button @click="increaseQuantity(item.product_id)"
                                                :disabled="item.quantity_to_return >= item.max_quantity_can_return"
                                                class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                                +
                                            </button>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ item.quantity_to_return }}/{{ item.max_quantity_can_return }}
                                        </div>
                                    </td>
                                    <td class="px-2 text-center">
                                        <input type="number" :value="item.unit_cost" readonly
                                            class="w-24 p-1 text-center border border-gray-300 rounded-md text-gray-800 bg-gray-100 cursor-not-allowed" />
                                    </td>
                                    <td class="px-2 text-right font-medium text-gray-800">
                                        {{ formatMoney(item.quantity_to_return * item.unit_cost) }}
                                    </td>
                                    <td class="px-2 text-center">
                                        <input v-model="item.reason" type="text" placeholder="Nhập lý do trả"
                                            class="w-32 p-1 border border-gray-300 rounded-md text-sm" />
                                    </td>
                                    <td class="px-2">
                                        <button v-if="returnForm.return_items.length > 1"
                                            @click="removeProduct(item.product_id)"
                                            class="text-gray-400 hover:text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="returnForm.return_items.length === 0 && props.purchaseOrder">
                                    <td colspan="5" class="text-center py-4 text-gray-500">Đơn nhập này không có sản
                                        phẩm nào để hoàn trả.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-white rounded-xl shadow p-4 space-y-3">
                        <h2 class="text-md font-semibold text-gray-700 pb-3 border-b border-gray-200">Hoàn tiền</h2>
                        <div class="flex justify-between items-center text-sm text-gray-700">
                            <span>Giá trị hoàn trả</span>
                            <span>{{returnForm.return_items.filter(item => item.quantity_to_return > 0).length}} sản
                                phẩm</span>
                            <span class="font-medium text-gray-800">{{ formatMoney(totalReturnProductValue) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-gray-700">
                            <span>Chi phí</span>
                            <span class="font-medium text-gray-800">0₫</span>
                        </div>
                        <div class="flex justify-between items-center text-sm text-gray-700">
                            <span>Giảm trừ trả hàng</span>
                            <span class="font-medium text-red-600">-0₫</span>
                        </div>
                        <div
                            class="flex justify-between items-center font-semibold text-lg text-gray-800 border-t border-gray-200 pt-3 mt-3">
                            <span>Giá trị nhận hoàn</span>
                            <span class="text-blue-600">{{ formatMoney(totalRefundValue) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow p-4 space-y-3">
                        <h2 class="text-md font-semibold text-gray-700 pb-3 border-b border-gray-200">Nhà cung cấp</h2>
                        <div v-if="props.purchaseOrder">
                            <div class="flex items-center space-x-3">
                                <img v-if="props.purchaseOrder.supplier_avatar_url"
                                    :src="getImage(props.purchaseOrder.supplier_avatar_url)" alt="Supplier Avatar"
                                    class="w-10 h-10 rounded-full object-cover border border-gray-200" />
                                <div v-else
                                    class="size-10 flex items-center justify-center bg-blue-100 text-blue-500 rounded-full">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="size-6">
                                        <path fill-rule="evenodd"
                                            d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-gray-800 font-medium">{{ props.purchaseOrder.supplier_name }}</p>
                            </div>
                            <p v-if="props.purchaseOrder.supplier_email" class="text-sm text-gray-600">
                                Email: {{ props.purchaseOrder.supplier_email }}</p>
                            <p v-if="props.purchaseOrder.supplier_phone" class="text-sm text-gray-600">
                                Điện thoại: {{ props.purchaseOrder.supplier_phone }}</p>
                            <p v-if="props.purchaseOrder.supplier_address" class="text-sm text-gray-600">
                                Địa chỉ: {{ props.purchaseOrder.supplier_address }}</p>
                        </div>
                        <div v-else class="text-sm text-gray-500">
                            Không có thông tin nhà cung cấp (chưa chọn đơn nhập).
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-4 space-y-3">
                        <h2 class="text-md font-semibold text-gray-700 pb-3 border-b border-gray-200">Thông tin bổ sung
                        </h2>
                        <div class="form-group">
                            <label for="returnCode" class="block text-sm font-medium text-gray-700 mb-1">Mã đơn hoàn
                                trả</label>
                            <input type="text" id="returnCode" v-model="returnForm.return_order_code"
                                placeholder="Nhập mã đơn"
                                class="w-full p-2 border border-gray-300 rounded-md text-sm text-gray-800 focus:outline-none focus:border-blue-400 transition duration-150 ease-in-out" />
                        </div>
                        <div class="form-group">
                            <label for="returnReason" class="block text-sm font-medium text-gray-700 mb-1">Lý do hoàn
                                trả</label>
                            <textarea id="returnReason" v-model="returnForm.reason" placeholder="Nhập lý do hoàn trả"
                                rows="3"
                                class="w-full p-2 border border-gray-300 rounded-md text-sm text-gray-800 focus:outline-none focus:border-blue-400 resize-y transition duration-150 ease-in-out"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="number"] {
    -moz-appearance: textfield;
}
</style>
