<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
<<<<<<< HEAD
import { Head, usePage, Link } from '@inertiajs/vue3';

=======
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
>>>>>>> 8100e850df8901d2111d48f7d321f47bb5aaa9c1
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

<<<<<<< HEAD
type Product = { id: number; name: string; price?: number; description?: string };
=======
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
>>>>>>> 8100e850df8901d2111d48f7d321f47bb5aaa9c1

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
<<<<<<< HEAD
        <div class="container mx-auto max-w-5xl mt-10">
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-blue-100">
                <div class="mb-8 flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-blue-700 flex items-center gap-2">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/>
                        </svg>
                        Danh sách sản phẩm
                    </h1>
                    <Link
                        href="/admin/products/create"
                        class="rounded-xl bg-gradient-to-r from-green-400 to-blue-500 px-6 py-2 text-white font-semibold shadow hover:from-green-500 hover:to-blue-600 transition"
                    >
                        + Thêm mới
                    </Link>
                </div>
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full text-left border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-blue-50 text-blue-700">
                                <th class="p-3 font-semibold rounded-l-xl">ID</th>
                                <th class="p-3 font-semibold">Tên sản phẩm</th>
                                <th class="p-3 font-semibold">Giá</th>
                                <th class="p-3 font-semibold">Mô tả</th>
                                <th class="p-3 font-semibold rounded-r-xl">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in products"
                                :key="product.id"
                                class="bg-white hover:bg-blue-50 transition rounded-xl shadow"
                            >
                                <td class="p-3 rounded-l-xl">{{ product.id }}</td>
                                <td class="p-3">
                                    <Link
                                        :href="`/admin/products/${product.id}/edit`"
                                        class="text-blue-700 font-semibold hover:underline"
                                    >
                                        {{ product.name }}
                                    </Link>
                                </td>
                                <td class="p-3">{{ product.price ? product.price.toLocaleString() + ' ₫' : '—' }}</td>
                                <td class="p-3 truncate max-w-xs" :title="product.description ?? ''">
                                    {{ product.description ?? '—' }}
                                </td>
                                <td class="p-3 rounded-r-xl">
                                    <Link
                                        :href="`/admin/products/${product.id}/edit`"
                                        class="inline-flex items-center gap-1 text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded-lg transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6-6m2 2l-6 6m-2 2h6"/>
                                        </svg>
                                        Xóa
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td colspan="5" class="text-center text-gray-500 py-6 bg-white rounded-xl shadow">
                                    Không có sản phẩm nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
=======
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
>>>>>>> 8100e850df8901d2111d48f7d321f47bb5aaa9c1
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
