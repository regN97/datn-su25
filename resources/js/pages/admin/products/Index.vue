<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý sản phẩm',
        href: '/admin/products',
    },
];
type Category = {
    id: number;
    name: string;
};

type ProductUnit = {
    id: number;
    name: string;
};



// Lấy tên category dựa trên category_id
function getCategoryName(category_id: number) {
    const category = categories.find(c => c.id === category_id);
    return category ? category.name : 'Không có';
}

// Lấy tên unit dựa trên unit_id
function getUnitName(unit_id: number) {
    const unit = units.find(u => u.id === unit_id);
    return unit ? unit.name : 'Không có';
}
type Product = {
    id: number;
    name: string;
    sku: string;
    barcode: string;
    description: string;
    category_id: number;
    unit_id: number;
    purchase_price: number;
    selling_price: number;
    image_url: string;
    min_stock_level: number;
    max_stock_level: number;
    is_active: boolean;
    category?: Category;
    unit?: ProductUnit;
};

const page = usePage<SharedData>();
const categories = page.props.categories as Category[];
const units = page.props.units as ProductUnit[];
const products = page.props.products as Product[];

// Cấu hình phân trang
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

// Tổng sản phẩm & tổng số trang
const total = computed(() => products.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

// Danh sách sản phẩm theo trang hiện tại
const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return products.slice(start, start + perPage.value);
});

// Chuyển đến trang cụ thể
function goToPage(page: number) {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
}

// Trang trước
function prevPage() {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
}

// Trang tiếp theo
function nextPage() {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
}

// Đổi số lượng sản phẩm mỗi trang
function changePerPage(event: Event) {
    const value = +(event.target as HTMLSelectElement).value;
    perPage.value = value;
    currentPage.value = 1; // Reset về trang đầu
}
</script>


<template>

    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <!-- Tiêu đề và nút Thêm mới -->
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Danh mục sản phẩm</h1>
                        <button class="rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600">Thêm
                            mới</button>
                    </div>

                    <!-- Bảng danh mục -->
                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-3 text-sm font-semibold">STT</th>
                                    <th class="p-3 text-sm font-semibold">Tên sản phẩm</th>
                                    <th class="p-3 text-sm font-semibold">SKU</th>
                                    <th class="p-3 text-sm font-semibold">Loại danh mục</th>
                                    <th class="p-3 text-sm font-semibold">Đơn vị</th>
                                    <th class="p-3 text-sm font-semibold">Giá bán</th>
                                    <th class="p-3 text-sm font-semibold">Trạng thái</th>
                                    <th class="p-3 text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(product, idx) in paginatedProducts" :key="product.id" class="border-t">
                                    <td class="p-3 text-sm">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                    <td class="p-3 text-sm">{{ product.name || 'Không có' }}</td>
                                    <td class="p-3 text-sm">{{ product.sku || 'Không có' }}</td>
                                    <td class="p-3 text-sm">{{ getCategoryName(product.category_id) }}</td>
                                    <td class="p-3 text-sm">{{ getUnitName(product.unit_id) }}</td>


                                    <td class="p-3 text-sm">{{ product.selling_price ? product.selling_price + '₫' :
                                        'Không có' }}</td>

                                    <td class="p-3 text-sm">
                                        <span
                                            :class="product.is_active ? 'text-green-600 font-medium' : 'text-red-500 font-medium'">
                                            {{ product.is_active ? 'Hiện' : 'Ẩn' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <button class="text-blue-500 hover:underline">Sửa</button>
                                        |
                                        <button class="text-red-500 hover:underline">Xóa</button>
                                    </td>
                                </tr>

                                <tr v-if="paginatedProducts.length === 0">
                                    <td colspan="7" class="p-3 text-center text-sm">Không có dữ liệu</td>
                                </tr>
                            </tbody>

                        </table>
                    </div>

                    <!-- Phân trang -->
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

<style lang="scss" scoped></style>
