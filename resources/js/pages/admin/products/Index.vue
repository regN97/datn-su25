<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
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

type Supplier = {
    id: number;
    name: string;
};

// Lấy tên category dựa trên category_id
function getCategoryName(category_id: number) {
    const category = categories.find((c) => c.id === category_id);
    return category ? category.name : 'Không có';
}

// Lấy tên unit dựa trên unit_id
function getUnitName(unit_id: number) {
    const unit = units.find((u) => u.id === unit_id);
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
    suppliers?: Supplier[];
};

const page = usePage<SharedData>();
const categories = page.props.categories as Category[];
const units = page.props.units as ProductUnit[];
const products = page.props.products as Product[];

// Cấu hình phân trang
const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const openProductDetailsId = ref<number | null>(null);

function toggleDetails(productId: number) {
    if (openProductDetailsId.value === productId) {
        openProductDetailsId.value = null;
    } else {
        openProductDetailsId.value = productId;
    }
}

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
    currentPage.value = 1;
}

function goToCreatePage() {
    router.visit('/admin/products/create');
}

function goToEditPage(id: number) {
    router.visit(`/admin/products/${id}/edit`);
}

const showDeleteModal = ref(false);
const productToDelete = ref<number | null>(null);

function confirmDelete(id: number) {
    productToDelete.value = id;
    showDeleteModal.value = true;
}

function handleDeleteCategory() {
    if (!productToDelete.value) return;

    router.delete(`/admin/products/${productToDelete.value}`, {
        onSuccess: () => {
            const idx = products.findIndex((cat) => cat.id === productToDelete.value);
            if (idx !== -1) products.splice(idx, 1);
            showDeleteModal.value = false;
            productToDelete.value = null;
        },
        preserveState: true,
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    productToDelete.value = null;
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Danh mục sản phẩm</h1>
                        <button @click="goToCreatePage" class="rounded-3xl bg-green-500 px-8 py-2 text-white hover:bg-green-600">
                            <PackagePlus />
                        </button>
                    </div>

                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="w-[15%] p-3 text-center text-sm font-semibold">Hình ảnh</th>
                                    <th class="w-[25%] p-3 text-left text-sm font-semibold">Tên sản phẩm</th>
                                    <th class="w-[20%] p-3 text-center text-sm font-semibold">SKU</th>
                                    <th class="w-[20%] p-3 text-center text-sm font-semibold">Trạng thái</th>
                                    <th class="w-[20%] p-3 text-center text-sm font-semibold">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="product in paginatedProducts" :key="product.id">
                                    <tr class="border-t">
                                        <td class="w-[15%] p-3 text-center text-sm">
                                            <img
                                                :src="product.image_url"
                                                alt="Product Image"
                                                class="mx-auto h-16 w-16 rounded object-cover"
                                                onerror="this.onerror=null;this.src='/storage/piclumen-1747750187180.png';"
                                            />
                                        </td>
                                        <td class="w-[25%] p-3 text-left text-sm">{{ product.name || 'Không có' }}</td>
                                        <td class="w-[20%] p-3 text-center text-sm">{{ product.sku || 'Không có' }}</td>
                                        <td class="w-[20%] p-3 text-center text-sm">
                                            <span :class="product.is_active ? 'font-medium text-green-600' : 'font-medium text-red-500'">
                                                {{ product.is_active ? 'Hiện' : 'Ẩn' }}
                                            </span>
                                        </td>
                                        <td class="w-[20%] p-3 text-left text-sm whitespace-nowrap">
                                            <div class="flex items-center justify-center space-x-2 text-center">
                                                <button
                                                    @click="toggleDetails(product.id)"
                                                    class="flex items-center gap-1 rounded-md bg-gray-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                                                >
                                                    <component :is="openProductDetailsId === product.id ? EyeOff : Eye" class="h-4 w-4" />
                                                    {{ openProductDetailsId === product.id ? '' : '' }}
                                                </button>
                                                <button
                                                    class="rounded-md bg-blue-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                                                    @click="goToEditPage(product.id)"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </button>
                                                <button
                                                    @click="confirmDelete(product.id)"
                                                    class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="openProductDetailsId === product.id">
                                        <td
                                            :colspan="Object.keys(product).length > 0 ? 8 : 1"
                                            class="border-t border-b border-gray-200 bg-gray-50 p-4"
                                        >
                                            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                                                <h4 class="mb-4 text-xl font-bold text-gray-800">
                                                    📝 Thông tin chi tiết sản phẩm - {{ product.name || 'Không có' }}
                                                </h4>
                                                <div class="grid grid-cols-1 gap-6 text-sm text-gray-700 md:grid-cols-2">
                                                    <div class="space-y-3">
                                                        <div>
                                                            <span class="font-semibold text-gray-900">📦 Mã vạch:</span>
                                                            {{ product.barcode || 'Không có' }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">📁 Danh mục:</span>
                                                            {{ getCategoryName(product.category_id) }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">📏 Đơn vị:</span>
                                                            {{ getUnitName(product.unit_id) }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">🖋️ Mô tả:</span>
                                                            {{ product.description || 'Không có' }}
                                                        </div>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <div>
                                                            <span class="font-semibold text-gray-900">💰 Giá nhập:</span>
                                                            {{
                                                                product.purchase_price
                                                                    ? product.purchase_price.toLocaleString('vi-VN') + '₫'
                                                                    : 'Không có'
                                                            }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">🛒 Giá bán:</span>
                                                            {{
                                                                product.selling_price
                                                                    ? product.selling_price.toLocaleString('vi-VN') + '₫'
                                                                    : 'Không có'
                                                            }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">📉 Tồn kho tối thiểu:</span>
                                                            {{ product.min_stock_level }}
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold text-gray-900">📈 Tồn kho tối đa:</span>
                                                            {{ product.max_stock_level }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="font-semibold text-gray-900">🗓️ Nhà cung cấp:</span>
                                                        <span>
                                                            <template v-if="product.suppliers && product.suppliers.length > 0">
                                                                <span v-for="(supplier, index) in product.suppliers" :key="supplier.id">
                                                                    {{ supplier.name }}
                                                                    <span v-if="index < product.suppliers.length - 1">, </span>
                                                                </span>
                                                            </template>
                                                            <template v-else> Không có </template>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="paginatedProducts.length === 0">
                                    <td colspan="8" class="p-3 text-center text-sm">Không có dữ liệu</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm">
                            Hiển thị kết quả từ
                            <span class="font-semibold">{{ (currentPage - 1) * perPage + 1 }}</span>
                            -
                            <span class="font-semibold">{{ Math.min(currentPage * perPage, total) }}</span>
                            trên tổng <span class="font-semibold">{{ total }}</span>
                        </p>
                        <div class="flex items-center space-x-2">
                            <button class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700" :disabled="currentPage === 1" @click="prevPage">
                                &larr; Trang trước
                            </button>
                            <template v-for="page in totalPages" :key="page">
                                <button
                                    class="rounded px-3 py-1 text-sm"
                                    :class="page === currentPage ? 'bg-gray-200 font-bold' : 'text-gray-500 hover:text-gray-700'"
                                    @click="goToPage(page)"
                                >
                                    {{ page }}
                                </button>
                            </template>
                            <button
                                class="px-2 py-1 text-sm text-gray-500 hover:text-gray-700"
                                :disabled="currentPage === totalPages"
                                @click="nextPage"
                            >
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

        <DeleteModal
            :is-open="showDeleteModal"
            title="Xóa sản phẩm"
            message="Bạn có chắc chắn muốn xóa sản phẩm này?"
            @confirm="handleDeleteCategory"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>

<style lang="scss" scoped></style>
