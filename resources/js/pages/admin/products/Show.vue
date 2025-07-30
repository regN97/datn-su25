<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

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
    name: string
}

interface Category {
    id: number;
    name: string
}
// Nhận prop product từ Inertia
const props = defineProps<{
    product: Product;
    unit: Unit;
    category: Category;
}>();

function formatCurrency(value: number | null): string {
    if (value === null || isNaN(value)) return 'N/A';
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
}

function goToInventoryHistory() {
    router.visit(`/admin/products/id/inventory-history`);
}

function goBack() {
    router.visit('/admin/products');
}
</script>

<template>

    <Head title="Chi tiết Sản phẩm" />
    <AppLayout>
        <div class="bg-gray-50 min-h-screen p-6 no-print">
            <div class="mx-auto max-w-7xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button @click="goBack"
                            class="h-9 w-9 rounded border border-gray-300 bg-white text-gray-700 hover:border-gray-400">
                            ←
                        </button>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">{{ props.product.name }}</h1>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thông tin sản phẩm</h2>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tên sản phẩm <span
                                            class="text-red-500">*</span></label>
                                    <input type="text"
                                        class="mt-4 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2"
                                        :value="props.product.name" disabled>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tên sản phẩm <span
                                            class="text-red-500">*</span></label>
                                    <input type="text"
                                        class="mt-4 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2"
                                        :value="props.product.name" disabled>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mã SKU</label>
                                    <input type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2"
                                        :value="props.product.sku" disabled>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mã vạch/Barcode</label>
                                    <input type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2 px-2 py-2"
                                        :value="props.product.barcode" disabled>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nhập đơn vị tính</label>
                                    <input type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2"
                                        :value="props.unit.name" disabled>
                                </div>
                                <div class="md:col-span-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mô tả</label>
                                        <textarea
                                            class="mt-2 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2"
                                            :value="props.product.description" rows="4" disabled></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-6">
                        <!-- Ảnh sản phẩm + danh mục -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ảnh sản phẩm</label>
                                    <div v-if="props.product.image_url">
                                        <img :src="props.product.image_url" alt="Ảnh sản phẩm"
                                            class="w-20 h-20 object-cover" />
                                    </div>
                                    <div v-else class="text-sm text-gray-500">Không có ảnh</div>
                                </div>

                                <!-- Danh mục -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                                    <input type="text" :value="props.category.name" disabled
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2 bg-gray-50" />
                                </div>
                            </div>
                        </div>

                        <!-- Giá bán và giá vốn -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold text-gray-800">Thông tin giá</h2>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Giá bán</label>
                                    <input type="text" :value="formatCurrency(props.product.selling_price)" disabled
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2 bg-gray-50" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Giá vốn</label>
                                    <input type="text" value="Đang cập nhật" disabled
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-2 py-2 bg-gray-50 text-gray-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4 flex items-center justify-between">
                                <h2 class="text-lg font-semibold">Thông tin kho</h2>
                                <button @click="router.visit(`/admin/products/${props.product.id}/inventory-history`)"
                                    class="text-sm text-blue-600 hover:underline">
                                    Xem lịch sử tồn kho
                                </button>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tồn kho</label>
                                    <input type="number"
                                        class="block w-full rounded-md border-gray-300 shadow-sm px-2 py-2"
                                        :value="props.product.stock_quantity" disabled>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Có thể bán</label>
                                    <input type="text"
                                        class="block w-full rounded-md border-gray-300 shadow-sm px-2 py-2"
                                        :value="props.product.stock_quantity" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>