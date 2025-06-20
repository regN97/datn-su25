<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, CircleCheckBig, CircleX, PackagePlus, PencilLine, Printer } from 'lucide-vue-next';
import Swal from 'sweetalert2';
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

interface PurchaseOrder {
    id: number;
    po_number: string;
    supplier_id: number;
    status_id: number;
    order_date: string;
    expected_delivery_date?: string;
    actual_delivery_date?: string;
    discount_amount: number;
    discount_type: string;
    total_amount: number;
    created_by: number;
    notes?: string;
    status: {
        id: number;
        name: string;
        code: string;
    };
}

interface PurchaseOrderItem {
    id: number;
    purchase_order_id: number;
    product_id: number;
    product_name: string;
    product_sku: string;
    ordered_quantity: number;
    unit_cost: number;
    subtotal: number;
    discount_amount: number;
    discount_type: string;
    product: Product;
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

// Props from Laravel via Inertia
interface Props {
    suppliers?: Supplier[];
    users?: User[];
    purchaseOrder: PurchaseOrder[];
    purchaseOrderItem: PurchaseOrderItem[];
}

const props = withDefaults(defineProps<Props>(), {
    suppliers: () => [],
    users: () => [],
    purchaseOrder: () => [],
    purchaseOrderItem: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chi tiết đơn đặt hàng',
        href: '/admin/purchase-orders/show',
    },
];

const po_products = ref<PurchaseOrderItem[]>(props.purchaseOrderItem);
const purchase_order = ref<PurchaseOrder[]>(props.purchaseOrder);

function getStatusClass(status_id: number) {
    switch (status_id) {
        case 1:
            return 'bg-green-100 text-green-600';
        case 2:
            return 'bg-yellow-100 text-yellow-600';
        case 3:
            return 'bg-blue-100 text-blue-600';
        case 4:
            return 'bg-gray-100 text-gray-600';
        case 5:
            return 'bg-red-100 text-red-600';
        default:
            return 'bg-gray-100 text-gray-600';
    }
}

const formattedSubtotal = computed(() => {
    const total = po_products.value.reduce((sum, item) => sum + (item.subtotal || 0), 0);
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(total);
});

function goBack() {
    router.visit(route('admin.purchase-orders.index'));
}

function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(price);
}

const selectedUser = props.users.find((u) => u.id === props.purchaseOrder[0]?.created_by) || null;
const selectedSupplier = props.suppliers.find((s) => s.id === props.purchaseOrder[0]?.supplier_id) || null;

const expectedImportDate = props.purchaseOrder[0]?.expected_delivery_date;
const formattedExpectedImportDate = expectedImportDate ? new Date(expectedImportDate).toLocaleDateString('vi-VN') : 'Chưa xác định';

const orderCode = props.purchaseOrder[0]?.po_number || 'Chưa có mã đơn';

// Chiết khấu đơn
const discount = props.purchaseOrder;

const formattedDiscount = computed(() => {
    if (discount[0].discount_amount === 0) return '0₫';
    if (discount[0].discount_type === 'amount') {
        return `-${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(discount[0].discount_amount)}`;
    }
    // percent
    return `-${discount[0].discount_amount}%`;
});

const totalAfterDiscount = props.purchaseOrder[0].total_amount;

const note = props.purchaseOrder[0]?.notes || '';

// Các hàm xử lý nút chức năng
function editOrder() {
    router.get(route('admin.purchase-orders.edit', { id: props.purchaseOrder[0].id }));
}

function approveOrder() {
    router.post(
        route('admin.purchase-orders.approve', { id: props.purchaseOrder[0].id }),
        {},
        {
            onSuccess: () => {
                purchase_order.value[0].status_id = 2;
                purchase_order.value[0].status.name = 'Chờ nhập';
                purchase_order.value[0].status.code = 'pending';
            },
            onError: () => {
                Swal.fire('Lỗi', 'Không thể duyệt đơn hàng.', 'error');
            },
        },
    );
}

function importOrder() {
    router.post(
        route('admin.purchase-orders.import', { id: props.purchaseOrder[0].id }),
        {},
        {
            onSuccess: () => {
                Swal.fire('Thành công', 'Đơn hàng đã được nhập!', 'success');
            },
            onError: () => {
                Swal.fire('Lỗi', 'Không thể nhập đơn hàng.', 'error');
            },
        },
    );
}

function cancelOrder() {
    Swal.fire({
        title: 'Bạn chắc chắn muốn hủy đơn này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Hủy đơn',
        cancelButtonText: 'Đóng',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(
                route('admin.purchase-orders.cancel', { id: props.purchaseOrder[0].id }),
                {},
                {
                    onSuccess: () => {
                        // Cập nhật trạng thái trực tiếp trong Vue
                        purchase_order.value[0].status_id = 5;
                        purchase_order.value[0].status.name = 'Đã hủy';
                        purchase_order.value[0].status.code = 'cancelled';
                    },
                },
            );
        }
    });
}

function printOrder() {
    window.open(route('admin.purchase-orders.print', { id: props.purchaseOrder[0].id }), '_blank');
}
</script>

<template>
    <Head title="Create PO" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 p-4">
            <div class="mx-auto max-w-7xl">
                <!-- Header -->
                <div class="mb-4 flex items-center justify-between">
                    <!-- Nhóm bên trái: Button Quay lại + Tiêu đề -->
                    <div class="flex items-center">
                        <button
                            @click="goBack"
                            class="mb-0 flex h-10 w-10 items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:border-gray-400 hover:text-gray-800"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <h1 class="ml-4 text-3xl font-bold text-gray-900">{{ orderCode }}</h1>
                        <span
                            :class="[
                                'text-1xl font-regular ml-3 inline-flex rounded-full px-3.5 py-1 leading-5',
                                getStatusClass(purchase_order[0].status_id),
                            ]"
                            >{{ purchase_order[0].status.name }}</span
                        >
                    </div>
                    <!-- Nhóm bên phải: 4 nút chức năng -->
                    <div class="flex space-x-2">
                        <button
                            v-if="purchase_order[0].status_id != 5 && purchase_order[0].status_id != 3 && purchase_order[0].status_id != 4"
                            @click="editOrder"
                            class="flex h-10 items-center rounded px-4 font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                        >
                            <PencilLine class="mr-1 h-4 w-4" />
                            Sửa đơn
                        </button>

                        <button
                            v-if="purchase_order[0].status_id == 1"
                            @click="approveOrder"
                            class="flex h-10 items-center rounded px-4 font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                        >
                            <CircleCheckBig class="mr-1 h-4 w-4" />
                            Duyệt đơn
                        </button>

                        <button
                            v-if="purchase_order[0].status_id == 2 || purchase_order[0].status_id == 3"
                            @click="importOrder"
                            class="flex h-10 items-center rounded px-4 font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                        >
                            <PackagePlus class="mr-1 h-4 w-4" />
                            Nhập hàng
                        </button>

                        <button
                            v-if="purchase_order[0].status_id != 5 && purchase_order[0].status_id != 3 && purchase_order[0].status_id != 4"
                            @click="cancelOrder"
                            class="flex h-10 items-center rounded px-4 font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                        >
                            <CircleX class="mr-1 h-4 w-4" />
                            Hủy đơn
                        </button>
                        <button
                            @click="printOrder"
                            class="flex h-10 items-center rounded px-4 font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-800"
                        >
                            <Printer class="mr-1 h-4 w-4" />
                            In đơn
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <!-- Left Section - Order Details -->
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Chi tiết đơn hàng</h2>
                            </div>
                            <div class="space-y-6 p-6">
                                <!-- Selected Products -->
                                <div v-if="po_products.length > 0" class="space-y-3">
                                    <h3 class="text-sm font-medium text-gray-700">Sản phẩm đã chọn</h3>
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
                                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="p in po_products" :key="p.id">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center space-x-4">
                                                        <img
                                                            :src="p.product.image_url || '/storage/piclumen-1747750187180.png'"
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
                                                    <span>{{ p.ordered_quantity }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                    <span>{{ formatPrice(p.unit_cost) }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm font-semibold whitespace-nowrap text-gray-900">
                                                    {{ formatPrice(p.subtotal) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Pricing Section chuyển xuống dưới -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thanh toán</h2>
                            </div>
                            <div class="space-y-3 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Tổng tiền</span>
                                    <span class="font-medium">{{ formattedSubtotal }}</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center text-sm text-blue-600">Chiết khấu đơn</span>
                                        <span class="min-w-[80px] text-right font-medium text-red-600">{{ formattedDiscount }}</span>
                                    </div>
                                </div>
                                <div class="border-t pt-4">
                                    <div class="flex items-center justify-between text-lg font-semibold">
                                        <span>Tiền cần trả NCC</span>
                                        <span class="ml-2">{{
                                            new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalAfterDiscount)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div class="space-y-6">
                        <!-- Suppliers Info (static) -->
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

                        <!-- Users Info (static) -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thông tin bổ sung</h2>
                            </div>
                            <div class="space-y-4 p-4">
                                <!-- Nhân viên phụ trách -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Nhân viên phụ trách</label>
                                    <div class="relative">
                                        <input
                                            type="text"
                                            disabled
                                            :value="selectedUser ? selectedUser.name : ''"
                                            class="h-10 w-full rounded-md border border-gray-300 pl-4 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                                <!-- Ngày nhập dự kiến -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhập dự kiến</label>
                                    <input
                                        type="text"
                                        disabled
                                        :value="formattedExpectedImportDate"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <!-- Mã đơn đặt hàng nhập -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Mã đơn đặt hàng nhập</label>
                                    <input
                                        type="text"
                                        disabled
                                        :value="orderCode"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Ghi chú -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Ghi chú</h2>
                            </div>
                            <div class="p-4">
                                <textarea
                                    :value="note"
                                    disabled
                                    class="min-h-[80px] w-full rounded-md border border-gray-300 p-2 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="css" scoped></style>
