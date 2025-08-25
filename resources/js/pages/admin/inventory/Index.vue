<script setup lang="ts">
import DeleteModal from '@/components/DeleteModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý tồn kho',
        href: '/admin/inventory',
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

// Cập nhật Supplier type để bao gồm pivot object
type Supplier = {
    id: number;
    name: string;
    pivot?: {
        // Đối tượng pivot chứa các trường từ bảng trung gian
        purchase_price?: number;
        // Các trường khác nếu có trên bảng pivot
    };
};

type Product = {
    id: number;
    name: string;
    sku: string;
    barcode: string;
    description: string;
    category_id: number;
    unit_id: number;
    stock_quantity: number;
    selling_price: number;
    image_url: string;
    min_stock_level: number;
    max_stock_level: number;
    is_active: boolean;
    category?: Category;
    unit?: ProductUnit;
    suppliers?: Supplier[]; // Danh sách nhà cung cấp của RIÊNG sản phẩm này
    batches?: Batch[];
    batch_items?: BatchItem[];
};

type Batch = {
    id: number;
    batch_number: string;
    supplier_id: number;
    receipt_status: 'pending' | 'completed';
    batch_items?: BatchItem[];
};

type BatchItem = {
    id: number;
    batch_id: number;
    product_id: number;
    current_quantity: number;
    inventory_status: 'active' | 'low_stock' | 'out_of_stock' | 'expired' | 'damaged';
};

type AvailableProduct = {
    product_id: number;
    name: string;
    value: number;
};

const page = usePage<SharedData>();
const categories = page.props.categories as Category[];
const units = page.props.units as ProductUnit[];
const products = page.props.products as Product[];
const availableProducts = page.props.availableProducts as AvailableProduct[];

const allSuppliers = (page.props.allSuppliers as Supplier[]) || [];

const isSidebarOpen = ref(false);

// Bộ lọc
const filterNameBarcode = ref('');
const filterCategory = ref<number | null>(null);
const filterStatus = ref<string>('all');
const filterMinSellingPrice = ref<number | null>(null); // Đổi tên để tránh nhầm lẫn với giá nhập
const filterMaxSellingPrice = ref<number | null>(null); // Đổi tên để tránh nhầm lẫn với giá nhập
const filterUnit = ref<number | null>(null);
const filterSuppliers = ref<number[]>([]);

const filteredProducts = computed(() => {
    return products.filter((product) => {
        const matchesNameBarcode =
            product.name?.toLowerCase().includes(filterNameBarcode.value.toLowerCase().trim()) ||
            product.barcode?.toLowerCase().includes(filterNameBarcode.value.toLowerCase().trim());
        const matchesCategory = filterCategory.value === null || product.category_id === filterCategory.value;
        const matchesStatus =
            filterStatus.value === 'all' ||
            (filterStatus.value === 'active' && product.is_active) ||
            (filterStatus.value === 'inactive' && !product.is_active);
        const matchesMinSellingPrice = filterMinSellingPrice.value === null || product.selling_price >= filterMinSellingPrice.value;
        const matchesMaxSellingPrice = filterMaxSellingPrice.value === null || product.selling_price <= filterMaxSellingPrice.value;
        const matchesUnit = filterUnit.value === null || product.unit_id === filterUnit.value;
        const matchesSuppliers =
            filterSuppliers.value.length === 0 ||
            (product.suppliers && product.suppliers.some((supplier) => filterSuppliers.value.includes(supplier.id)));

        return (
            matchesNameBarcode &&
            matchesCategory &&
            matchesStatus &&
            matchesMinSellingPrice &&
            matchesMaxSellingPrice &&
            matchesUnit &&
            matchesSuppliers
        );
    });
});

const perPageOptions = [5, 10, 25, 50];
const perPage = ref(5);
const currentPage = ref(1);

const total = computed(() => filteredProducts.value.length);
const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredProducts.value.slice(start, start + perPage.value);
});

function goToPage(page: number) {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
}

function prevPage() {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
}

function nextPage() {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
}

function changePerPage(event: Event) {
    const value = +(event.target as HTMLSelectElement).value;
    perPage.value = value;
    currentPage.value = 1;
}

function goToShowPage(id: number) {
    router.visit(route('admin.inventory.show', id));
}

const showDeleteModal = ref(false);
const productToDelete = ref<number | null>(null);

function handleDeleteProduct() {
    if (!productToDelete.value) return;

    router.delete(`/admin/products/${productToDelete.value}`, {
        onSuccess: () => {
            const idx = products.findIndex((p) => p.id === productToDelete.value);
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

function resetFilters() {
    filterNameBarcode.value = '';
    filterCategory.value = null;
    filterStatus.value = 'all';
    filterMinSellingPrice.value = null;
    filterMaxSellingPrice.value = null;
    filterSuppliers.value = [];
    currentPage.value = 1;
    filterUnit.value = null;
}

function imageSrc(url: string): string {
    // Nếu URL bắt đầu bằng http hoặc https thì trả nguyên
    if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
    }
    // Nếu không thì coi là ảnh trong public (vd: /storage/...)
    return '/' + url.replace(/^\/+/, '');
}

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
}

function syncInventory() {
    router.post(route('admin.inventory.sync'));
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Quản lý kho</h1>
                        <div class="flex items-center space-x-4">
                            <button @click="toggleSidebar" class="rounded-3xl bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                                <Filter class="h-5 w-5" />
                            </button>
                            <button
                                @click="syncInventory"
                                class="rounded-3xl bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:cursor-not-allowed disabled:bg-green-300"
                            >
                                Đồng bộ tồn kho
                            </button>
                        </div>
                    </div>

                    <div
                        :class="[
                            'fixed inset-y-0 right-0 z-50 w-full transform bg-white p-6 shadow-xl transition-transform duration-300 ease-in-out md:w-96',
                            isSidebarOpen ? 'translate-x-0' : 'translate-x-full',
                        ]"
                    >
                        <div class="flex items-center justify-between border-b pb-4">
                            <h2 class="text-lg font-semibold">Bộ lọc nâng cao</h2>
                            <button @click="toggleSidebar" class="text-gray-500 hover:text-gray-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-4 space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tên sản phẩm, barcode</label>
                                <input
                                    v-model="filterNameBarcode"
                                    type="text"
                                    placeholder="Nhập tên sản phẩm, barcode..."
                                    class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Danh mục</label>
                                <select
                                    v-model="filterCategory"
                                    class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500"
                                >
                                    <option :value="null">Tất cả danh mục</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Trạng thái</label>
                                <select
                                    v-model="filterStatus"
                                    class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500"
                                >
                                    <option value="all">Tất cả</option>
                                    <option value="active">Hiện</option>
                                    <option value="inactive">Ẩn</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Giá bán (VNĐ)</label>
                                <div class="flex space-x-2">
                                    <input
                                        v-model.number="filterMinSellingPrice"
                                        type="number"
                                        placeholder="Từ"
                                        class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500"
                                    />
                                    <input
                                        v-model.number="filterMaxSellingPrice"
                                        type="number"
                                        placeholder="Đến"
                                        class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Đơn vị tính</label>
                                <select
                                    v-model="filterUnit"
                                    class="focus:ring-opacity-50 mt-1 block w-full rounded-md border-gray-300 p-2 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500"
                                >
                                    <option :value="null">Tất cả đơn vị</option>
                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                        {{ unit.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nhà cung cấp</label>
                                <div class="mt-1 max-h-40 overflow-y-auto rounded-md border border-gray-300 p-2">
                                    <template v-if="allSuppliers && allSuppliers.length > 0">
                                        <label v-for="supplier in allSuppliers" :key="supplier.id" class="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                :value="supplier.id"
                                                v-model="filterSuppliers"
                                                class="rounded border-gray-300 text-blue-500 focus:ring-blue-500"
                                            />
                                            <span>{{ supplier.name }}</span>
                                        </label>
                                    </template>
                                    <template v-else>
                                        <p class="text-sm text-gray-500">Không có nhà cung cấp nào</p>
                                    </template>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button @click="resetFilters" class="rounded-md bg-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-400">
                                    Xóa bộ lọc
                                </button>
                                <button @click="toggleSidebar" class="rounded-md bg-blue-500 px-4 py-2 text-sm text-white hover:bg-blue-600">
                                    Áp dụng
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="isSidebarOpen" class="bg-opacity-50 fixed inset-0 z-40 bg-black md:hidden" @click="toggleSidebar"></div>

                    <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-200 text-left">
                                    <th class="p-3 text-center text-sm font-semibold">Ảnh</th>
                                    <th class="p-3 text-sm font-semibold">Sản phẩm</th>
                                    <th class="p-3 text-sm font-semibold">Danh mục</th>
                                    <th class="p-3 text-sm font-semibold">SKU</th>
                                    <th class="p-3 text-sm font-semibold">Barcode</th>
                                    <th class="p-3 text-sm font-semibold">Đơn vị tính</th>
                                    <th class="p-3 text-sm font-semibold">Tồn kho</th>
                                    <th class="p-3 text-sm font-semibold">Có thể bán</th>
                                    <th class="p-3 text-sm font-semibold">Giá bán</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="product in paginatedProducts" :key="product.id">
                                    <tr>
                                        <td class="p-3">
                                            <div class="flex items-center justify-center">
                                                <img
                                                    v-if="product.image_url"
                                                    :src="imageSrc(product.image_url)"
                                                    class="h-10 w-10 rounded-md object-cover"
                                                />
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <button @click="goToShowPage(product.id)" class="cursor-pointer hover:text-blue-500">
                                                {{ product.name }}
                                            </button>
                                        </td>
                                        <td class="p-3">
                                            {{ product.category?.name }}
                                        </td>
                                        <td class="p-3">
                                            {{ product.sku }}
                                        </td>
                                        <td class="p-3">
                                            {{ product.barcode }}
                                        </td>
                                        <td class="p-3">
                                            {{ product.unit ? product.unit.name : 'Không có' }}
                                        </td>
                                        <td class="p-3">
                                            {{ product.stock_quantity }}
                                        </td>
                                        <td class="p-3">
                                            {{ availableProducts.find((ap) => ap.product_id === product.id)?.value || 0 }}
                                        </td>
                                        <td class="p-3">
                                            {{ product.selling_price }}
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
                                ← Trang trước
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
                                Trang sau →
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
            @confirm="handleDeleteProduct"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
