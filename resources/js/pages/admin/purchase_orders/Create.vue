<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronLeft, ChevronUp, CreditCard, Minus, Search, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
}

interface Supplier {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    address?: string;
}

interface SelectedProduct extends Product {
    quantity: number;
    total: number;
}

interface ProductsResponse {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

// Props from Laravel via Inertia
interface Props {
    products?: ProductsResponse;
    suppliers?: Supplier[];
}

const props = withDefaults(defineProps<Props>(), {
    products: () => ({
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 6,
        total: 0,
    }),
    suppliers: () => [], // Add default value for suppliers
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tạo đơn đặt hàng',
        href: '/admin/purchase-orders/create',
    },
];

// Reactive data
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const selectedProducts = ref<SelectedProduct[]>([]);
const isLoading = ref(false);
const searchInputRef = ref<HTMLInputElement>();
const dropdownRef = ref<HTMLElement>();

// Computed properties
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products.data;
    return props.products.data.filter(
        (product) =>
            product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            product.sku.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});

const currentPage = computed(() => props.products.current_page);
const totalPages = computed(() => props.products.last_page);

const subtotal = computed(() => {
    return selectedProducts.value.reduce((sum, product) => sum + product.total, 0);
});

const formattedSubtotal = computed(() => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(subtotal.value);
});

// Methods
function goBack() {
    window.history.back();
}

function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(price);
}

// Function to fetch products via Inertia
function fetchProducts(page: number = 1, search: string = '') {
    isLoading.value = true;

    router.get(
        route('admin.purchase-orders.create'),
        {
            page: page,
            search: search,
            per_page: 6,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['products'],
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
}

function openDropdown() {
    isDropdownOpen.value = true;
    if (props.products.data.length === 0) {
        fetchProducts(1, searchQuery.value);
    }
}

function closeDropdown() {
    isDropdownOpen.value = false;
}

function selectProduct(product: Product) {
    const existingProduct = selectedProducts.value.find((p) => p.id === product.id);

    if (existingProduct) {
        existingProduct.quantity += 1;
        existingProduct.total = existingProduct.quantity * existingProduct.purchase_price;
    } else {
        selectedProducts.value.push({
            ...product,
            quantity: 1,
            total: product.purchase_price,
        });
    }

    searchQuery.value = '';
    closeDropdown();

    // Xóa query parameter search khỏi URL
    router.get(
        route('admin.purchase-orders.create'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function removeProduct(productId: number) {
    selectedProducts.value = selectedProducts.value.filter((p) => p.id !== productId);
}

function updateQuantity(productId: number, quantity: number) {
    const product = selectedProducts.value.find((p) => p.id === productId);
    if (product && quantity > 0) {
        product.quantity = quantity;
        product.total = product.quantity * product.purchase_price;
    }
}

function nextPage() {
    if (currentPage.value < totalPages.value) {
        fetchProducts(currentPage.value + 1, searchQuery.value);
    }
}

function prevPage() {
    if (currentPage.value > 1) {
        fetchProducts(currentPage.value - 1, searchQuery.value);
    }
}

// Add these reactive refs after existing refs
const supplierSearchQuery = ref('');
const isSupplierDropdownOpen = ref(false);
const selectedSupplier = ref<Supplier | null>(null);
const supplierDropdownRef = ref<HTMLElement>();

// Add computed property for filtered suppliers
const filteredSuppliers = computed(() => {
    if (!supplierSearchQuery.value) return props.suppliers;
    return props.suppliers.filter(
        (supplier) =>
            supplier.name.toLowerCase().includes(supplierSearchQuery.value.toLowerCase()) ||
            (supplier.email && supplier.email.toLowerCase().includes(supplierSearchQuery.value.toLowerCase())) ||
            (supplier.phone && supplier.phone.includes(supplierSearchQuery.value)),
    );
});

function openSupplierDropdown() {
    isSupplierDropdownOpen.value = true;
}

function closeSupplierDropdown() {
    isSupplierDropdownOpen.value = false;
}

function selectSupplier(supplier: Supplier) {
    selectedSupplier.value = supplier;
    supplierSearchQuery.value = ''; // Clear the search query
    closeSupplierDropdown();
}

function unselectSupplier() {
    selectedSupplier.value = null;
    supplierSearchQuery.value = '';
}

// Handle click outside to close dropdown
function handleClickOutside(event: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown();
    }
    if (supplierDropdownRef.value && !supplierDropdownRef.value.contains(event.target as Node)) {
        closeSupplierDropdown();
    }
}

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    if (isDropdownOpen.value) {
        fetchProducts(1, newQuery);
    }
});

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Create PO" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 p-4">
            <div class="mx-auto max-w-7xl">
                <!-- Header -->
                <div class="mb-6">
                    <button @click="goBack" class="mb-4 flex cursor-pointer items-center text-gray-600 hover:text-gray-800">
                        <ChevronLeft class="mr-1 h-4 w-4" />
                        Quay lại
                    </button>
                    <h1 class="text-3xl font-bold text-gray-900">Tạo đơn hàng nhập</h1>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left Section - Order Details -->
                    <div class="lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-6">
                                <h2 class="text-lg font-semibold">Chi tiết đơn hàng</h2>
                            </div>
                            <div class="space-y-6 p-6">
                                <!-- Selected Products -->
                                <div v-if="selectedProducts.length > 0" class="space-y-3">
                                    <h3 class="text-sm font-medium text-gray-700">Sản phẩm đã chọn</h3>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Sản phẩm
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Số lượng
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Đơn giá
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Thành tiền
                                                </th>
                                                <th scope="col" class="relative px-6 py-3">
                                                    <span class="sr-only">Actions</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="product in selectedProducts" :key="product.id">
                                                <!-- Trong phần Selected Products table, thay đổi cột đầu tiên: -->
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center space-x-4">
                                                        <img
                                                            :src="product.image_url || '/storage/piclumen-1747750187180.png'"
                                                            :alt="product.name"
                                                            class="h-12 w-12 rounded-lg border border-gray-200 object-cover"
                                                        />
                                                        <div>
                                                            <h4 class="font-medium text-gray-900">{{ product.name }}</h4>
                                                            <p class="text-sm text-gray-500">SKU: {{ product.sku }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        :value="product.quantity"
                                                        @input="updateQuantity(product.id, parseInt(($event.target as HTMLInputElement).value))"
                                                        class="w-16 rounded border border-gray-300 px-2 py-1 text-center text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                                    />
                                                </td>
                                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                    {{ formatPrice(product.purchase_price) }}
                                                </td>
                                                <td class="px-6 py-4 text-sm font-semibold whitespace-nowrap text-gray-900">
                                                    {{ formatPrice(product.total) }}
                                                </td>
                                                <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                                    <button @click="removeProduct(product.id)" class="text-red-500 hover:text-red-700">
                                                        <X class="h-4 w-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Product Search -->
                                <div class="relative" ref="dropdownRef">
                                    <div class="relative">
                                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                        <input
                                            ref="searchInputRef"
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Tìm kiếm sản phẩm..."
                                            @focus="openDropdown"
                                            @keydown.escape="closeDropdown"
                                            class="h-12 w-full rounded-md border border-gray-300 pr-4 pl-10 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        />
                                        <button
                                            v-if="isDropdownOpen"
                                            @click="closeDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                        >
                                            <ChevronUp class="h-4 w-4" />
                                        </button>
                                        <button
                                            v-else
                                            @click="openDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                        >
                                            <ChevronDown class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <!-- Dropdown -->
                                    <div v-if="isDropdownOpen" class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                        <div class="max-h-80 overflow-y-auto">
                                            <!-- Loading state -->
                                            <div v-if="isLoading" class="p-4 text-center text-gray-500">
                                                <div class="mx-auto h-6 w-6 animate-spin rounded-full border-b-2 border-blue-500"></div>
                                                <p class="mt-2 text-sm">Đang tải...</p>
                                            </div>

                                            <!-- Product list -->
                                            <div v-else-if="filteredProducts.length > 0">
                                                <button
                                                    v-for="product in filteredProducts"
                                                    :key="product.id"
                                                    @click="selectProduct(product)"
                                                    class="w-full border-b border-gray-100 p-4 text-left last:border-b-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                                                >
                                                    <div class="flex items-center space-x-4">
                                                        <img
                                                            :src="product.image_url || '/storage/piclumen-1747750187180.png'"
                                                            :alt="product.name"
                                                            class="h-16 w-16 rounded-lg border border-gray-200 object-cover"
                                                        />
                                                        <div class="flex-1">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <h4 class="font-medium text-gray-900">{{ product.name }}</h4>
                                                                    <p class="text-sm text-gray-500">{{ product.sku }}</p>
                                                                    <p v-if="product.description" class="line-clamp-1 text-sm text-gray-400">
                                                                        {{ product.description }}
                                                                    </p>
                                                                </div>
                                                                <div class="text-right">
                                                                    <p class="font-semibold text-blue-600">
                                                                        {{ formatPrice(product.purchase_price) }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </button>

                                                <!-- Pagination -->
                                                <div
                                                    v-if="totalPages > 1"
                                                    class="flex items-center justify-between border-t border-gray-200 px-4 py-3"
                                                >
                                                    <div class="text-sm text-gray-500">Trang {{ currentPage }} / {{ totalPages }}</div>
                                                    <div class="flex space-x-2">
                                                        <button
                                                            @click="prevPage"
                                                            :disabled="currentPage === 1"
                                                            class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                        >
                                                            Trước
                                                        </button>
                                                        <button
                                                            @click="nextPage"
                                                            :disabled="currentPage === totalPages"
                                                            class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                        >
                                                            Sau
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- No results -->
                                            <div v-else class="p-4 text-center text-gray-500">
                                                <p class="text-sm">Không tìm thấy sản phẩm nào</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes Section -->
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Ghi chú</label>
                                    <textarea
                                        placeholder="Thêm ghi chú..."
                                        class="min-h-[80px] w-full rounded-md border border-gray-300 p-2 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    ></textarea>
                                </div>

                                <!-- Pricing Section -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-700">Tạm tính</span>
                                        <span class="font-medium">{{ formattedSubtotal }}</span>
                                    </div>

                                    <div class="space-y-2">
                                        <button class="flex items-center text-sm text-blue-600 hover:text-blue-700">
                                            <Minus class="mr-1 h-4 w-4" />
                                            Thêm khuyến mãi
                                        </button>
                                        <button class="flex items-center text-sm text-blue-600 hover:text-blue-700">
                                            <Minus class="mr-1 h-4 w-4" />
                                            Thêm phương thức vận chuyển
                                        </button>
                                    </div>

                                    <div class="border-t pt-4">
                                        <div class="flex items-center justify-between text-lg font-semibold">
                                            <span>Tổng cộng</span>
                                            <span>{{ formattedSubtotal }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Section -->
                                <div class="border-t pt-6">
                                    <div class="mb-4 flex items-center">
                                        <CreditCard class="mr-2 h-5 w-5 text-blue-600" />
                                        <span class="font-medium">Xác nhận thanh toán</span>
                                    </div>

                                    <div class="flex gap-3">
                                        <button class="h-10 flex-1 rounded-md border border-gray-300 px-4 py-2 hover:bg-gray-50">
                                            Đã thanh toán
                                        </button>
                                        <button class="h-10 flex-1 rounded-md border border-gray-300 px-4 py-2 hover:bg-gray-50">
                                            Thanh toán sau
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div class="space-y-6">
                        <!-- Suppliers Search -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-6">
                                <h2 class="text-lg font-semibold">Tìm kiếm hay thêm mới nhà cung cấp</h2>
                            </div>
                            <div class="p-6">
                                <!-- Show search input only when no supplier is selected -->
                                <div v-if="!selectedSupplier" class="relative" ref="supplierDropdownRef">
                                    <div class="relative">
                                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                        <input
                                            v-model="supplierSearchQuery"
                                            type="text"
                                            placeholder="Tìm kiếm nhà cung cấp..."
                                            @focus="openSupplierDropdown"
                                            @keydown.escape="closeSupplierDropdown"
                                            class="h-12 w-full rounded-md border border-gray-300 pr-4 pl-10 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        />
                                        <button
                                            v-if="isSupplierDropdownOpen"
                                            @click="closeSupplierDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                        >
                                            <ChevronUp class="h-4 w-4" />
                                        </button>
                                        <button
                                            v-else
                                            @click="openSupplierDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                        >
                                            <ChevronDown class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <!-- Supplier Dropdown -->
                                    <div
                                        v-if="isSupplierDropdownOpen"
                                        class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg"
                                    >
                                        <div class="max-h-80 overflow-y-auto">
                                            <div v-if="filteredSuppliers.length > 0">
                                                <button
                                                    v-for="supplier in filteredSuppliers"
                                                    :key="supplier.id"
                                                    @click="selectSupplier(supplier)"
                                                    class="w-full border-b border-gray-100 p-4 text-left last:border-b-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                                                >
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-gray-900">{{ supplier.name }}</span>
                                                        <span v-if="supplier.email" class="text-sm text-gray-500">{{ supplier.email }}</span>
                                                        <span v-if="supplier.phone" class="text-sm text-gray-500">{{ supplier.phone }}</span>
                                                    </div>
                                                </button>
                                            </div>
                                            <div v-else class="p-4 text-center text-gray-500">
                                                <p class="text-sm">Không tìm thấy nhà cung cấp nào</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Show supplier info when selected -->
                                <div v-if="selectedSupplier" class="rounded-md border border-gray-200 bg-gray-50 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h3 class="font-medium text-gray-900">{{ selectedSupplier.name }}</h3>
                                        <button @click="unselectSupplier" class="text-gray-400 hover:text-gray-600">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div class="space-y-1 text-sm">
                                        <h3 class="font-bold text-black-900">Thông tin nhà cung cấp</h3>
                                        <p v-if="selectedSupplier.email" class="text-black-400">{{ selectedSupplier.email }}</p>
                                        <p v-if="selectedSupplier.phone" class="text-black-400">{{ selectedSupplier.phone }}</p>
                                        <p v-if="selectedSupplier.address" class="text-black-400">{{ selectedSupplier.address }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Draft Button -->
                        <button class="h-12 w-full rounded-md bg-blue-500 font-medium text-white hover:bg-blue-600">Lưu đơn hàng nháp</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="css" scoped></style>
