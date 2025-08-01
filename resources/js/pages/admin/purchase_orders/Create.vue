<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronLeft, ChevronUp, Search, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
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
    suppliers: Supplier[];
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

interface SelectedProduct extends Product {
    quantity: number;
    total: number;
}

interface ProductsResponse {
    data: Product[];
    total: number;
}

// Props from Laravel via Inertia
interface Props {
    products?: ProductsResponse;
    suppliers?: Supplier[];
    users?: User[];
}

const props = withDefaults(defineProps<Props>(), {
    products: () => ({
        data: [],
        total: 0,
    }),
    suppliers: () => [], // Add default value for suppliers
    users: () => [], // Add default value for users
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
function fetchProducts(search: string = '') {
    isLoading.value = true;

    router.get(
        route('admin.purchase-orders.create'),
        {
            search: search,
            per_page: 100, // hoặc số lớn để lấy hết sản phẩm
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
        fetchProducts(searchQuery.value);
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
            purchase_price: 0, // Đơn giá mặc định là 0
            quantity: 1,
            total: 0,
        });
    }

    searchQuery.value = '';
    closeDropdown();

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

const userDropdownRef = ref<HTMLElement>();

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

const expectedImportDate = ref('');
const orderCode = ref('');

const isPriceModalOpen = ref(false);
const editingProductId = ref<number | null>(null);
const editingPrice = ref(0);

function openPriceModal(product: SelectedProduct) {
    editingProductId.value = product.id;
    editingPrice.value = product.purchase_price;
    isPriceModalOpen.value = true;
}

function closePriceModal() {
    isPriceModalOpen.value = false;
    editingProductId.value = null;
}

function savePrice() {
    const product = selectedProducts.value.find((p) => p.id === editingProductId.value);
    if (product) {
        product.purchase_price = editingPrice.value;
        product.total = product.quantity * product.purchase_price;
    }
    closePriceModal();
}

// Chiết khấu đơn
const isDiscountModalOpen = ref(false);
const discount = ref<{ type: 'amount' | 'percent'; value: number }>({ type: 'amount', value: 0 });

// Thêm biến tạm cho modal
const modalDiscountType = ref<'amount' | 'percent'>('amount');
const modalDiscountInput = ref('');
const discountError = ref('');

const formattedDiscount = computed(() => {
    if (discount.value.value === 0) return '0₫';
    if (discount.value.type === 'amount') {
        return `-${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(discount.value.value)}`;
    }
    // percent
    return `-${discount.value.value}%`;
});

const discountAmount = computed(() => {
    if (discount.value.type === 'amount') {
        return Math.min(discount.value.value, subtotal.value);
    }
    // percent
    return Math.round((subtotal.value * discount.value.value) / 100);
});

const totalAfterDiscount = computed(() => {
    return Math.max(subtotal.value - discountAmount.value, 0);
});

function openDiscountModal() {
    isDiscountModalOpen.value = true;
    modalDiscountType.value = discount.value.type;
    modalDiscountInput.value = discount.value.value ? discount.value.value.toString() : '';
    discountError.value = '';
}

function closeDiscountModal() {
    isDiscountModalOpen.value = false;
    discountError.value = '';
}

function setDiscountType(type: 'amount' | 'percent') {
    modalDiscountType.value = type;
    modalDiscountInput.value = '';
    discountError.value = '';
}

function saveDiscount() {
    const val = Number(modalDiscountInput.value);
    if (modalDiscountType.value === 'amount') {
        if (isNaN(val) || val < 0) {
            discountError.value = 'Vui lòng nhập số tiền hợp lệ';
            return;
        }
        if (val > subtotal.value) {
            discountError.value = 'Không được lớn hơn tổng tiền';
            return;
        }
    } else {
        if (isNaN(val) || val < 0 || val > 100) {
            discountError.value = 'Phần trăm phải từ 0 đến 100';
            return;
        }
    }
    // Chỉ khi bấm Lưu mới cập nhật ra ngoài
    discount.value.type = modalDiscountType.value;
    discount.value.value = val;
    closeDiscountModal();
}

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    if (isDropdownOpen.value) {
        fetchProducts(newQuery);
    }
});

const note = ref('');
const supplierError = ref('');

function submitOrder() {
    if (!selectedSupplier.value) {
        supplierError.value = 'Nhà cung cấp không được để trống';
        if (supplierError.value.length > 0) {
            return Swal.fire({
                icon: 'error',
                title: 'Có lỗi xảy ra, vui lòng kiểm tra lại',
                showConfirmButton: false,
                timer: 2000,
            });
        }
    } else {
        supplierError.value = '';
    }

    router.post(route('admin.purchase-orders.store'), {
        products: selectedProducts.value.map((p) => ({
            id: p.id,
            name: p.name,
            sku: p.sku,
            quantity: p.quantity,
            purchase_price: p.purchase_price,
            sub_total: p.total,
        })),
        discount: {
            type: discount.value.type,
            value: discount.value.value,
        },
        total_amount: totalAfterDiscount.value,
        supplier_id: selectedSupplier.value ? selectedSupplier.value.id : null,
        user_id: props.users.id,
        expected_import_date: expectedImportDate.value,
        order_code: orderCode.value,
        note: note.value,
    });
}

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
                <div class="mb-4 flex items-center">
                    <button
                        @click="goBack"
                        class="mb-0 flex h-10 w-10 items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:border-gray-400 hover:text-gray-800"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <h1 class="ml-4 text-3xl font-bold text-gray-900">Tạo đơn đặt hàng</h1>
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
                                                    <button
                                                        class="text-blue-600 underline hover:text-blue-800"
                                                        @click="openPriceModal(product)"
                                                        type="button"
                                                    >
                                                        {{ formatPrice(product.purchase_price) }}
                                                    </button>
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
                                                                        {{ formatPrice(0) }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>

                                            <!-- No results -->
                                            <div v-else class="p-4 text-center text-gray-500">
                                                <p class="text-sm">Không tìm thấy sản phẩm nào</p>
                                            </div>
                                        </div>
                                    </div>
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
                                        <button
                                            class="flex items-center text-sm text-blue-600 hover:text-blue-700"
                                            @click="openDiscountModal"
                                            type="button"
                                        >
                                            Chiết khấu đơn
                                        </button>
                                        <span class="min-w-[80px] text-right font-medium text-red-600">{{ formattedDiscount }}</span>
                                    </div>
                                </div>

                                <div class="border-t pt-4">
                                    <div class="flex items-center justify-between text-lg font-semibold">
                                        <span>Tiền cần trả NCC</span>
                                        <span>{{
                                            new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalAfterDiscount)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div class="space-y-6">
                        <!-- Suppliers Search -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Tìm kiếm hay thêm mới nhà cung cấp</h2>
                                <div v-if="supplierError.length > 0" class="text-sm text-red-500">
                                    {{ supplierError }}
                                </div>
                            </div>
                            <div class="p-4">
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
                                        <h3 class="text-black-900 font-bold">Thông tin nhà cung cấp</h3>
                                        <p v-if="selectedSupplier.email" class="text-black-400">{{ selectedSupplier.email }}</p>
                                        <p v-if="selectedSupplier.phone" class="text-black-400">{{ selectedSupplier.phone }}</p>
                                        <p v-if="selectedSupplier.address" class="text-black-400">{{ selectedSupplier.address }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Search -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thông tin bổ sung</h2>
                            </div>
                            <div class="space-y-4 p-4">
                                <!-- Nhân viên phụ trách -->
                                <div class="relative" ref="userDropdownRef">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Nhân viên phụ trách</label>
                                    <div class="relative">
                                        <input
                                            type="text"
                                            :value="props.users.name"
                                            disabled
                                            class="h-10 w-full rounded-md border border-gray-300 pr-4 pl-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                                <!-- Ngày nhập dự kiến -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhập dự kiến</label>
                                    <input
                                        type="datetime-local"
                                        v-model="expectedImportDate"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <!-- Mã đơn đặt hàng nhập -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Mã đơn đặt hàng nhập</label>
                                    <input
                                        type="text"
                                        v-model="orderCode"
                                        placeholder="Nhập mã đơn hàng"
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
                                    v-model="note"
                                    placeholder="Thêm ghi chú..."
                                    class="min-h-[80px] w-full rounded-md border border-gray-300 p-2 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                ></textarea>
                            </div>
                        </div>

                        <button
                            class="mt-4 h-12 w-full rounded-md bg-blue-500 font-medium text-white hover:bg-blue-600"
                            @click="submitOrder"
                            v-if="selectedProducts.length > 0"
                        >
                            Lưu đơn hàng
                        </button>
                        <button
                            class="mt-4 h-12 w-full rounded-md bg-gray-500 font-medium text-white hover:bg-gray-600"
                            @click="submitOrder"
                            v-else
                            disabled
                        >
                            Lưu đơn hàng
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isPriceModalOpen" class="fixed inset-0 z-50">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
            <!-- Modal content -->
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="mb-4 text-lg font-semibold">Chỉnh sửa đơn giá</h3>
                    <input type="number" min="0" v-model.number="editingPrice" class="mb-4 w-full rounded border border-gray-300 px-3 py-2" />
                    <div class="flex justify-end space-x-2">
                        <button @click="closePriceModal" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Hủy</button>
                        <button @click="savePrice" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lưu</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discount Modal -->
        <div v-if="isDiscountModalOpen" class="fixed inset-0 z-50">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-md rounded-lg bg-white p-4 shadow-xl">
                    <!-- Header -->
                    <h3 class="mb-6 border-b text-lg font-semibold">Thêm chiết khấu</h3>
                    <!-- Content -->
                    <div class="mb-6 flex items-center gap-2 border-b pb-6">
                        <label class="font-medium whitespace-nowrap text-gray-700">Loại chiết khấu:</label>
                        <div class="flex items-center">
                            <button
                                :class="[
                                    'h-10 rounded-l border border-gray-300 px-4 whitespace-nowrap',
                                    modalDiscountType === 'amount' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                                ]"
                                @click="setDiscountType('amount')"
                                type="button"
                            >
                                Giá trị
                            </button>
                            <button
                                :class="[
                                    'h-10 rounded-r border-t border-r border-b border-gray-300 px-4',
                                    modalDiscountType === 'percent' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                                ]"
                                @click="setDiscountType('percent')"
                                type="button"
                            >
                                %
                            </button>
                        </div>
                        <div class="flex items-center">
                            <input
                                v-model="modalDiscountInput"
                                placeholder="0"
                                type="text"
                                min="0"
                                :max="modalDiscountType === 'percent' ? 100 : subtotal"
                                class="h-10 w-32 rounded-l border border-r-0 border-gray-300 px-2 focus:outline-none"
                            />
                            <button class="h-10 w-[36px] rounded-r border border-l-0 border-gray-300 px-2" type="button">
                                <span v-if="modalDiscountType === 'amount'" class="text-gray-500">₫</span>
                                <span v-else class="text-gray-500">%</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="discountError" class="mb-2 text-sm text-red-600">{{ discountError }}</div>
                    <!-- Footer -->
                    <div class="flex justify-end space-x-2">
                        <button
                            @click="closeDiscountModal"
                            class="bg-white-200 rounded border-1 border-red-500 px-4 py-1 font-semibold text-red-500 hover:bg-red-100"
                        >
                            Xóa
                        </button>
                        <button @click="saveDiscount" class="rounded bg-blue-500 px-4 py-1 font-semibold text-white hover:bg-blue-400">Lưu</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="css" scoped></style>
