<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// Định nghĩa props để nhận dữ liệu từ Inertia
const props = defineProps<{
    purchaseReturn: {
        id: number; // Thêm id để có thể dùng cho route chi tiết đơn nhập
        return_number: string;
        purchase_order_id: number; // <--- Thêm purchase_order_id nếu có
        purchase_order_code: string;
        batch_number: string;
        batch_id: number;
        supplier_name: string;
        supplier_email?: string;
        supplier_phone?: string;
        supplier_address?: string;
        supplier_avatar_url?: string;
        reason: string | null;
        return_date: string; // Giả sử đây là định dạng YYYY-MM-DD HH:MM:SS
        status: string;
        created_by: string;
        total_items_returned: number;
        total_value_returned: number;
        return_history: { time: string; action: string; user: string }[];
        items: {
            product_name: string;
            batch_number: string | null;
            product_sku: string;
            manufacturing_date: string | null;
            expiry_date: string | null;
            quantity_returned: number;
            unit_cost: number;
            subtotal: number;
            reason: string | null;
            product_image_url?: string | null;
        }[];
    };
}>();

// Sử dụng prop được truyền vào
const purchaseReturnData = ref(props.purchaseReturn);

// Cập nhật hàm formatDate để hiển thị cả ngày và giờ
const formatDate = (dateString: string, includeTime: boolean = false) => {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
        // Kiểm tra ngày không hợp lệ
        return dateString; // Trả về nguyên gốc nếu không phải ngày hợp lệ
    }
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    };
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
        options.second = '2-digit';
        options.hour12 = false; // Sử dụng định dạng 24 giờ
    }
    return date.toLocaleDateString('vi-VN', options);
};

// Thêm hàm để đi tới trang chi tiết đơn nhập

function goToBatchDetails(id: number) {
    router.visit(`admin/batches/${id}`);
}

// Tạo bản sao sâu của props ban đầu để so sánh và chỉnh sửa form
const initialForm = ref({
    reason: purchaseReturnData.value.reason,
    items: purchaseReturnData.value.items.map((item) => ({ ...item })),
});

const form = ref({
    reason: purchaseReturnData.value.reason,
    items: purchaseReturnData.value.items.map((item) => ({ ...item })),
});

// Flag để kiểm soát hiển thị nút Save/Cancel
const hasChanges = ref(false);

// Theo dõi thay đổi trong dữ liệu form
watch(
    form,
    (newVal) => {
        hasChanges.value = JSON.stringify(newVal) !== JSON.stringify(initialForm.value);
    },
    { deep: true },
); // Deep watch để phát hiện thay đổi bên trong đối tượng/mảng lồng nhau

function saveChanges() {
    router.put(
        route('admin.purchaseReturn.update', purchaseReturnData.value.id),
        {
            reason: form.value.reason, // ✅ sửa lại cho khớp với backend
        },
        {
            onSuccess: () => {
                alert('Cập nhật thành công!');
            },
            onError: (err) => {
                console.error('Lỗi:', err);
            },
        },
    );
}

function cancelChanges() {
    // Hoàn nguyên form về trạng thái ban đầu
    form.value = JSON.parse(JSON.stringify(initialForm.value));
    hasChanges.value = false;
}

function formatMoney(amount: number) {
    return amount.toLocaleString('vi-VN') + 'đ';
}

function getImage(url?: string | null) {
    if (!url) return '/apple-touch-icon.png';

    // Nếu đã là URL đầy đủ (http...) thì dùng luôn
    if (url.startsWith('http')) return url;

    // Nếu đã bắt đầu bằng /storage thì giữ nguyên
    if (url.startsWith('/storage')) return url;

    // Nếu chỉ là tên file thì thêm /storage phía trước
    return `/storage/${url}`;
}
function goBack() {
    router.visit(route('admin.purchaseReturn.show', purchaseReturnData.value.id));
}
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu trả hàng',
        // Corrected to use the named route for the index page
        href: route('admin.purchaseReturn.index'),
    },
];
</script>
<template>
    <Head title="Chi tiết phiếu trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid min-h-screen grid-cols-3 gap-6 bg-gray-50 p-6 pb-20">
            <div class="col-span-2 space-y-6">
                <div class="flex items-center text-xl font-semibold text-gray-700">
                    <button
                        @click="goBack"
                        class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition-all duration-200 ease-in-out hover:bg-gray-100 hover:text-gray-800 focus:ring-2 focus:ring-gray-200 focus:outline-none"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <span class="text-gray-900">{{ purchaseReturnData.return_number }}</span>
                    <span class="ml-2 text-base text-gray-400">
                        {{ formatDate(purchaseReturnData.return_date) }}
                    </span>
                </div>

                <div class="space-y-3 rounded-xl bg-white p-4 shadow">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-gray-700">Đã hoàn trả</h2>
                        <span class="flex items-center text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-1 size-4 text-blue-500">
                                <path
                                    fill-rule="evenodd"
                                    d="m11.54 22.351.07.074a49.11 49.11 0 0 0 7.755-5.917c1.83-1.879 3.12-4.18 3.551-6.66.443-2.527-.47-5.002-2.38-6.76a.75.75 0 0 0-1.077-.074L17.7 4.908.941 18.271a.75.75 0 0 0 .074 1.077l2.84 2.839a.75.75 0 0 0 1.07-.077ZM9.5 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            Cửa hàng chính
                        </span>
                    </div>
                    <table class="w-full border-t text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Sản phẩm</th>
                                <th class="py-2 text-center">Số lượng</th>
                                <th class="py-2 text-center">Đơn giá trả</th>
                                <th class="py-2 text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index" class="border-t">
                                <td class="flex items-center py-2">
                                    <img
                                        :src="getImage(item.product_image_url)"
                                        :alt="item.product_name"
                                        class="mr-2 h-12 w-12 rounded object-cover"
                                        @error="(e) => (e.target.src = '/apple-touch-icon.png')"
                                    />

                                    <div>
                                        <div class="font-medium text-blue-600">{{ item.product_name }}</div>
                                        <div class="text-xs text-gray-500">SKU: {{ item.product_sku }}</div>
                                    </div>
                                </td>
                                <td class="text-center">{{ item.quantity_returned }}</td>
                                <td class="text-center">{{ formatMoney(item.unit_cost) }}</td>
                                <td class="text-right">{{ formatMoney(item.quantity_returned * item.unit_cost) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-2 rounded-xl bg-white p-4 shadow">
                    <h2 class="flex items-center font-semibold text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-1 size-5">
                            <path
                                fill-rule="evenodd"
                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Đã nhận hoàn tiền
                    </h2>
                    <div class="flex justify-between text-sm text-gray-700">
                        <span>Giá trị hàng trả</span>
                        <span>{{ purchaseReturnData.total_items_returned }} sản phẩm</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-700">
                        <span>Chi phí</span>
                        <span>{{ formatMoney(0) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-700">
                        <span>Giảm trừ trả hàng</span>
                        <span>-{{ formatMoney(0) }}</span>
                    </div>
                    <div class="mt-2 flex justify-between border-t pt-2 text-lg font-semibold text-gray-800">
                        <span>Giá trị nhận hoàn</span>
                        <span>{{ formatMoney(purchaseReturnData.total_value_returned) }}</span>
                    </div>
                </div>

            </div>

            <div class="col-span-1 space-y-6">
                <div class="space-y-2 rounded-xl bg-white p-4 shadow">
                    <h2 class="font-semibold text-gray-700">Hoàn trả từ mã lô</h2>
                    <button
                        @click="goToBatchDetails(purchaseReturnData.batch_id)"
                        class="cursor-pointer border-none bg-transparent p-0 text-lg font-medium text-blue-600 hover:underline"
                    >
                        {{ purchaseReturnData.batch_number }}
                    </button>
                </div>

                <div class="space-y-2 rounded-xl bg-white p-4 shadow">
                    <h2 class="font-semibold text-gray-700">Nhà cung cấp</h2>
                    <div class="flex items-center space-x-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-10 rounded-full bg-blue-100 p-1 text-blue-500"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <p class="font-medium text-blue-700">{{ purchaseReturnData.supplier_name }}</p>
                    </div>
                    <p class="text-sm text-gray-600">{{ purchaseReturnData.supplier_email }}</p>
                    <p class="text-sm text-gray-600">{{ purchaseReturnData.supplier_phone }}</p>
                    <p class="text-sm text-gray-600">{{ purchaseReturnData.supplier_address }}</p>
                </div>

                <div class="space-y-2 rounded-xl bg-white p-4 shadow">
                    <h2 class="font-semibold text-gray-700">Thông tin bổ sung</h2>
                    <label class="text-sm text-gray-600">Lý do trả hàng</label>
                    <input
                        type="text"
                        v-model="form.reason"
                        class="w-full rounded border border-gray-300 bg-gray-100 p-2 text-sm focus:border-blue-400 focus:outline-none"
                    />
                </div>
            </div>
        </div>

        <div v-if="hasChanges" class="fixed right-0 bottom-0 left-64 z-50 flex justify-end space-x-4 border-t bg-white p-4 shadow-lg">
            <button @click="cancelChanges" class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-100">Hủy</button>
            <button @click="saveChanges" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lưu</button>
        </div>
    </AppLayout>
</template>
