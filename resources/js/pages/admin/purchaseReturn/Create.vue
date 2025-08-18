<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

// Định nghĩa props nhận từ controller
const props = defineProps<{
    purchaseOrder: {
        id: number | null;
        code: string;
        supplier_id: number;
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

// Dữ liệu cho form trả hàng
const returnForm = ref({
    purchase_order_id: props.purchaseOrder?.id || null,
    return_order_code: props.purchaseOrder ? `TRA-${props.purchaseOrder.code}` : '',
    reason: '',
    return_items: [] as any[],
    additional_cost: 0,
    deduction: 0,
    tags: [] as string[],
    payment_status: 'unpaid',
    payment_amount: 0,
    paid_at: null as string | null,
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

// Tính toán tổng giá trị sản phẩm trả lại
const totalReturnProductValue = computed(() => {
    return returnForm.value.return_items.reduce((sum, item) => {
        return sum + item.quantity_to_return * item.unit_cost;
    }, 0);
});

// Tính toán tổng giá trị hoàn lại sau chi phí và giảm trừ
const totalRefundValue = computed(() => {
    return totalReturnProductValue.value - returnForm.value.additional_cost - returnForm.value.deduction;
});

<<<<<<< HEAD
// Watch tổng giá trị hoàn trả và trạng thái thanh toán để cập nhật số tiền thanh toán
watch(
    () => [totalRefundValue.value, returnForm.value.payment_status],
    ([newTotal, newStatus]) => {
        if (newStatus === 'paid') {
            returnForm.value.payment_amount = newTotal;
        } else {
            returnForm.value.payment_amount = 0;
        }
    },
    { immediate: true },
);

// Khi chuyển trạng thái sang "paid", tự động cập nhật ngày giờ
watch(
    () => returnForm.value.payment_status,
    (newStatus) => {
        if (newStatus === 'paid') {
            const now = new Date();
            const vietnamTime = new Date(now.getTime() + 7 * 60 * 60000); // UTC+7
            returnForm.value.paid_at = vietnamTime.toISOString().slice(0, 16);
        } else {
            returnForm.value.paid_at = null;
        }
    },
);

// Định dạng thời gian đã chọn để hiển thị
const formattedPaymentTime = computed(() => {
    if (!returnForm.value.paid_at) {
        return 'Chưa có';
    }
    const date = new Date(returnForm.value.paid_at);
    return date.toLocaleString('vi-VN', {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
});

// Lấy thời gian hiện tại theo giờ Việt Nam để hiển thị
const getCurrentVietnamTime = () => {
    const now = new Date();
    const vietnamTime = new Date(now.getTime() + 7 * 60 * 60000); // UTC+7
    return vietnamTime.toLocaleString('vi-VN', {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
};
=======
// Tự động cập nhật số tiền thanh toán khi tổng giá trị hoặc trạng thái thanh toán thay đổi
watch(() => [totalRefundValue.value, returnForm.value.payment_status], ([newTotal, newStatus]) => {
    if (newStatus === 'paid') {
        returnForm.value.payment_amount = newTotal;
    } else {
        returnForm.value.payment_amount = 0;
    }
}, { immediate: true });

// Tự động cập nhật ngày giờ khi chuyển trạng thái sang "paid"
watch(() => returnForm.value.payment_status, (newStatus) => {
    if (newStatus === 'paid') {
        const now = new Date();
        const vietnamTime = new Date(now.getTime() + 7 * 60 * 60000); // UTC+7
        returnForm.value.paid_at = vietnamTime.toISOString().slice(0, 16);
    } else {
        returnForm.value.paid_at = null;
    }
});

>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d

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
    returnForm.value.return_items.forEach((groupedItem) => {
        let remainingQuantityToReturn = groupedItem.quantity_to_return;

        if (remainingQuantityToReturn > 0) {
            const originalItems = groupedItem.original_items;

            originalItems?.forEach((originalItem: any) => {
                if (remainingQuantityToReturn > 0) {
                    const quantityFromOriginalItem = Math.min(remainingQuantityToReturn, originalItem.quantity_received);

                    if (quantityFromOriginalItem > 0) {
                        itemsToReturn.push({
                            product_id: originalItem.product_id,
                            product_name: originalItem.product_name,
                            product_sku: originalItem.product_sku,
                            quantity_returned: quantityFromOriginalItem,
                            unit_cost: originalItem.unit_cost,
                            reason: groupedItem.reason ?? null,
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

    const postData = {
        supplier_id,
        purchase_order_id,
        return_date,
        reason: returnForm.value.reason,
        items: itemsToReturn,
        payment_status: returnForm.value.payment_status,
        payment_amount: returnForm.value.payment_amount,
        paid_at: returnForm.value.paid_at,
    };

    router.post(route('admin.purchaseReturn.store'), postData, {
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
        },
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

// Logic kiểm soát số lượng sản phẩm trả lại
const updateQuantity = (productId: number, newQuantity: string | number) => {
    const item = returnForm.value.return_items.find((i) => i.product_id === productId);
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
    const item = returnForm.value.return_items.find((i) => i.product_id === productId);
    if (item && item.quantity_to_return > 0) {
        item.quantity_to_return--;
    }
};

const increaseQuantity = (productId: number) => {
    const item = returnForm.value.return_items.find((i) => i.product_id === productId);
    if (item && item.quantity_to_return < item.max_quantity_can_return) {
        item.quantity_to_return++;
    }
};

const removeProduct = (productId: number) => {
    returnForm.value.return_items = returnForm.value.return_items.filter((item) => item.product_id !== productId);
};

<<<<<<< HEAD
// Watch for props changes
watch(
    () => props.purchaseOrder,
    (newPurchaseOrder) => {
        if (newPurchaseOrder) {
            returnForm.value.purchase_order_id = newPurchaseOrder.id;
            returnForm.value.return_order_code = `TRA-${newPurchaseOrder.code}`;
=======
// Theo dõi thay đổi của props và cập nhật form
watch(() => props.purchaseOrder, (newPurchaseOrder) => {
    if (newPurchaseOrder) {
        returnForm.value.purchase_order_id = newPurchaseOrder.id;
        returnForm.value.return_order_code = `TRA-${newPurchaseOrder.code}`;
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d

            const aggregatedItems: any[] = [];
            const itemMap = new Map();

            newPurchaseOrder.items.forEach((item) => {
                if (itemMap.has(item.product_id)) {
                    const existingItem = itemMap.get(item.product_id);
                    existingItem.max_quantity_can_return += item.quantity_received;
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
    },
    { immediate: true },
);
</script>

<template>
    <Head title="Tạo đơn trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 p-6">
            <div class="sticky top-0 z-10 mb-6 flex items-center justify-between rounded-lg bg-white p-4 shadow-sm">
                <div class="flex items-center">
                    <button @click="router.visit(route('admin.purchaseReturn.index'))" class="mr-4 text-gray-600 hover:text-gray-900">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="size-6"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Tạo đơn trả hàng
                        <span v-if="props.purchaseOrder"> cho lô {{ props.purchaseOrder.code }} </span>
                    </h1>
                </div>
                <div class="flex space-x-3">
                    <button
                        @click="cancelReturn"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Hủy
                    </button>
                    <button @click="createReturnOrder" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Tạo đơn trả hàng nhập
                    </button>
                </div>
            </div>

            <div v-if="props.error" class="relative mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700" role="alert">
                <strong class="font-bold">Lỗi!</strong>
                <span class="block sm:inline"> {{ props.error }}</span>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="col-span-2 space-y-6">
                    <div class="rounded-xl bg-white p-4 shadow">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-md font-semibold text-gray-700">Sản phẩm hoàn trả</h2>
                            <span class="flex items-center text-sm text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-1 size-4 text-blue-500">
                                    <path
                                        fill-rule="evenodd"
                                        d="m11.54 22.351.07.074a49.11 49.11 0 0 0 7.755-5.917c1.83-1.879 3.12-4.18 3.551-6.66.443-2.527-.47-5.002-2.38-6.76a.75.75 0 0 0-1.077-.074L17.7 4.908.941 18.271a.75.75 0 0 0 .074 1.077l2.84 2.839a.75.75 0 0 0 1.07-.077ZM9.5 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ props.currentLocationName }}
                            </span>
                        </div>
                        <div v-if="!props.purchaseOrder" class="py-4 text-center text-gray-600">Vui lòng chọn một đơn nhập để tạo đơn trả hàng.</div>
                        <table v-else class="w-full border-t border-gray-200 text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-left text-gray-500">
                                    <th class="px-2 py-2">Sản phẩm</th>
                                    <th class="px-2 py-2 text-center">Số lượng</th>
                                    <th class="px-2 py-2 text-center">Đơn giá</th>
                                    <th class="px-2 py-2 text-right">Thành tiền</th>
                                    <th class="px-2 py-2 text-center">Lý do trả</th>
                                    <th class="w-10 px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in returnForm.return_items" :key="index" class="border-b border-gray-100 last:border-b-0">
                                    <td class="flex items-center px-2 py-3">
                                        <img
                                            :src="getImage(item.product_image_url)"
                                            :alt="item.product_name"
                                            class="mr-2 h-10 w-10 rounded object-cover"
                                            @error="(e) => ((e.target as HTMLImageElement).src = '/apple-touch-icon.png')"
                                        />
                                        <div>
                                            <div class="font-medium text-gray-800">{{ item.product_name }}</div>
                                            <div class="text-xs text-gray-500">SKU: {{ item.product_sku }}</div>
                                        </div>
                                    </td>
                                    <td class="px-2 text-center">
                                        <div
                                            class="mx-auto flex items-center justify-center overflow-hidden rounded-md border border-gray-300"
                                            style="width: 100px"
                                        >
                                            <button
                                                @click="decreaseQuantity(item.product_id)"
                                                :disabled="item.quantity_to_return <= 0"
                                                class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                -
                                            </button>
<<<<<<< HEAD
                                            <input
                                                type="number"
                                                :value="item.quantity_to_return"
=======
                                            <input type="number" v-model="item.quantity_to_return"
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                                                @input="updateQuantity(item.product_id, ($event.target as HTMLInputElement).value)"
                                                @blur="updateQuantity(item.product_id, ($event.target as HTMLInputElement).value)"
                                                min="0"
                                                :max="item.max_quantity_can_return"
                                                class="w-10 border-x border-gray-300 text-center text-gray-800 transition duration-150 ease-in-out focus:outline-none"
                                            />
                                            <button
                                                @click="increaseQuantity(item.product_id)"
                                                :disabled="item.quantity_to_return >= item.max_quantity_can_return"
                                                class="px-2 py-1 text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                +
                                            </button>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">{{ item.quantity_to_return }}/{{ item.max_quantity_can_return }}</div>
                                    </td>
                                    <td class="px-2 text-center">
                                        <input
                                            type="number"
                                            :value="item.unit_cost"
                                            readonly
                                            class="w-24 cursor-not-allowed rounded-md border border-gray-300 bg-gray-100 p-1 text-center text-gray-800"
                                        />
                                    </td>
                                    <td class="px-2 text-right font-medium text-gray-800">
                                        {{ formatMoney(item.quantity_to_return * item.unit_cost) }}
                                    </td>
                                    <td class="px-2 text-center">
                                        <input
                                            v-model="item.reason"
                                            type="text"
                                            placeholder="Nhập lý do trả"
                                            class="w-32 rounded-md border border-gray-300 p-1 text-sm"
                                        />
                                    </td>
                                    <td class="px-2">
                                        <button
                                            v-if="returnForm.return_items.length > 1"
                                            @click="removeProduct(item.product_id)"
                                            class="text-gray-400 hover:text-red-600"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="size-5"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="returnForm.return_items.length === 0 && props.purchaseOrder">
                                    <td colspan="5" class="py-4 text-center text-gray-500">Đơn nhập này không có sản phẩm nào để hoàn trả.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-3 rounded-xl bg-white p-4 shadow">
                        <h2 class="text-md border-b border-gray-200 pb-3 font-semibold text-gray-700">Hoàn tiền</h2>
                        <div class="flex items-center justify-between text-sm text-gray-700">
                            <span>Giá trị hoàn trả</span>
                            <span>{{ returnForm.return_items.filter((item) => item.quantity_to_return > 0).length }} sản phẩm</span>
                            <span class="font-medium text-gray-800">{{ formatMoney(totalReturnProductValue) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-700">
                            <span>Chi phí</span>
                            <span class="font-medium text-gray-800">0₫</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-700">
                            <span>Giảm trừ trả hàng</span>
                            <span class="font-medium text-red-600">-0₫</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3 text-lg font-semibold text-gray-800">
                            <span>Giá trị nhận hoàn</span>
                            <span class="text-blue-600">{{ formatMoney(totalRefundValue) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 space-y-6">
                    <div class="space-y-3 rounded-xl bg-white p-4 shadow">
                        <h2 class="text-md border-b border-gray-200 pb-3 font-semibold text-gray-700">Nhà cung cấp</h2>
                        <div v-if="props.purchaseOrder">
                            <div class="flex items-center space-x-3">
                                <img
                                    v-if="props.purchaseOrder.supplier_avatar_url"
                                    :src="getImage(props.purchaseOrder.supplier_avatar_url)"
                                    alt="Supplier Avatar"
                                    class="h-10 w-10 rounded-full border border-gray-200 object-cover"
                                />
                                <div v-else class="flex size-10 items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                                        <path
                                            fill-rule="evenodd"
                                            d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <p class="font-medium text-gray-800">{{ props.purchaseOrder.supplier_name }}</p>
                            </div>
                            <p v-if="props.purchaseOrder.supplier_email" class="text-sm text-gray-600">
                                Email: {{ props.purchaseOrder.supplier_email }}
                            </p>
                            <p v-if="props.purchaseOrder.supplier_phone" class="text-sm text-gray-600">
                                Điện thoại: {{ props.purchaseOrder.supplier_phone }}
                            </p>
                            <p v-if="props.purchaseOrder.supplier_address" class="text-sm text-gray-600">
                                Địa chỉ: {{ props.purchaseOrder.supplier_address }}
                            </p>
                        </div>
                        <div v-else class="text-sm text-gray-500">Không có thông tin nhà cung cấp (chưa chọn đơn nhập).</div>
                    </div>

                    <div class="space-y-3 rounded-xl bg-white p-4 shadow">
                        <h2 class="text-md border-b border-gray-200 pb-3 font-semibold text-gray-700">Thông tin bổ sung</h2>
                        <div class="form-group">
                            <label for="returnCode" class="mb-1 block text-sm font-medium text-gray-700">Mã đơn hoàn trả</label>
                            <input
                                type="text"
                                id="returnCode"
                                v-model="returnForm.return_order_code"
                                placeholder="Nhập mã đơn"
                                class="w-full rounded-md border border-gray-300 p-2 text-sm text-gray-800 transition duration-150 ease-in-out focus:border-blue-400 focus:outline-none"
                            />
                        </div>
                        <div class="form-group">
                            <label for="returnReason" class="mb-1 block text-sm font-medium text-gray-700">Lý do hoàn trả</label>
                            <textarea
                                id="returnReason"
                                v-model="returnForm.reason"
                                placeholder="Nhập lý do hoàn trả"
                                rows="3"
                                class="w-full resize-y rounded-md border border-gray-300 p-2 text-sm text-gray-800 transition duration-150 ease-in-out focus:border-blue-400 focus:outline-none"
                            ></textarea>
                        </div>
                    </div>

                    <div class="space-y-3 rounded-xl bg-white p-4 shadow">
                        <h2 class="text-md border-b border-gray-200 pb-3 font-semibold text-gray-700">Thông tin thanh toán</h2>

                        <div class="form-group">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Trạng thái thanh toán</label>
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <input
                                        type="radio"
                                        id="unpaid"
                                        value="unpaid"
                                        v-model="returnForm.payment_status"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label for="unpaid" class="ml-2 block text-sm text-gray-700">Chưa nhận hoàn tiền</label>
                                </div>
                                <div class="flex items-center">
                                    <input
                                        type="radio"
                                        id="paid"
                                        value="paid"
                                        v-model="returnForm.payment_status"
                                        class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label for="paid" class="ml-2 block text-sm text-gray-700">Đã nhận hoàn tiền</label>
                                </div>
                            </div>
                        </div>

                        <div v-if="returnForm.payment_status === 'paid'" class="space-y-3">
                            <div class="form-group">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Số tiền đã thanh toán</label>
                                <input
                                    type="text"
                                    :value="formatMoney(returnForm.payment_amount)"
                                    readonly
                                    class="w-full cursor-not-allowed rounded-md border border-gray-300 bg-gray-100 p-2 text-sm text-gray-800"
                                />
                            </div>

                            <div class="form-group">
<<<<<<< HEAD
                                <label class="mb-1 block text-sm font-medium text-gray-700">Ngày giờ thanh toán</label>
                                <input
                                    type="datetime-local"
                                    :value="returnForm.paid_at"
                                    readonly
                                    class="w-full cursor-not-allowed rounded-md border border-gray-300 bg-gray-100 p-2 text-sm text-gray-800"
                                />
                                <!-- <div class="text-xs text-gray-500 mt-1 space-y-1">
                                    <div v-if="returnForm.paid_at">
                                        Thời gian đã chọn: {{ formattedPaymentTime }}
                                    </div>
                                    <div>
                                        Thời gian hiện tại: {{ getCurrentVietnamTime() }}
                                    </div>
                                </div> -->
                            </div>
=======
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày giờ thanh toán</label>
                                <input type="datetime-local" :value="returnForm.paid_at" readonly
                                    class="w-full p-2 border border-gray-300 rounded-md text-sm text-gray-800 bg-gray-100 cursor-not-allowed" />
                                </div>
>>>>>>> bde3e6a249962476a9f9b507f4d894ab7bce0e2d
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
input[type='number']::-webkit-outer-spin-button,
input[type='number']::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type='number'] {
    -moz-appearance: textfield;
}
</style>
