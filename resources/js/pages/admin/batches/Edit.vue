<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import type { VisitOptions } from '@inertiajs/core';
import { ChevronDown, ChevronLeft, ChevronUp, Search, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Product {
    id: number;
    name: string;
    sku: string;
    description?: string;
    image_url?: string;
    purchase_price: number;
    quantity: number;
    total: number;
}

interface Supplier {
    id: number;
    name: string;
    email?: string;
    phone?: string;
    address?: string;
}

interface User {
    id: number;
    name: string;
}

interface SelectedProduct extends Product {
    quantity: number;
    purchase_price: number;
    total: number;
    total_amount: number;
    manufacturing_date?: string; 
    expiry_date?: string; 
}

interface BatchItem {
    id: number;
    product_id: number;
    name: string;
    sku: string;
    received_quantity: number;
    purchase_price: number;
    total_amount: number;
    manufacturing_date?: string;
    expiry_date?: string;
    notes: string | null;
    product: Product;
}

interface Batch {
    id: number;
    batch_number: string;
    supplier_id: number | null;
    total_amount: number;
    discount: { type: 'amount' | 'percent'; value: number };
    import_date: string;
    reference_code: string;
    payment_status: 'paid' | 'partially_paid' | 'unpaid';
    paid_amount: number;
    payment_date: string;
    payment_method: 'cash' | 'bank_transfer' | 'credit_card' | '';
    status: 'draft' | 'pending' | 'completed';
    discount_amount: number;
    discount_type: string;
    notes: string | null;
    created_by: number;
    received_date: string;
}

interface Props {
    batchItem: BatchItem[];
    batch: Batch;
    products: {
        data: Product[];
    };
    suppliers: Supplier[];
    users: User[];
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Chỉnh sửa đơn nhập hàng', href: route('admin.batches.edit', props.batch.id) }];

// Lấy thông tin đơn hàng hiện tại
const currentBatch = ref<Batch>(props.batch);

// Khởi tạo danh sách sản phẩm đã chọn từ BatchItem
const selectedProducts = ref<SelectedProduct[]>([]);

// Khởi tạo các biến cho tìm kiếm sản phẩm
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const isLoading = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLElement | null>(null);

// Khởi tạo các biến cho nhà cung cấp
const selectedSupplier = ref<Supplier | null>(null);
const supplierSearchQuery = ref('');
const isSupplierDropdownOpen = ref(false);
const supplierDropdownRef = ref<HTMLElement | null>(null);
const supplierError = ref('');

// Khởi tạo các biến cho người dùng
const selectedUser = ref<User | null>(null);
const userSearchQuery = ref('');
const isUserDropdownOpen = ref(false);
const userDropdownRef = ref<HTMLElement | null>(null);

// Khởi tạo các biến cho ngày nhập dự kiến và mã đơn hàng
const importDate = ref<string>('');
const batchCode = ref<string | null>(null);

// Khởi tạo biến cho ghi chú
const notes = ref<string | null>(null);

// Khởi tạo các biến cho modal chỉnh sửa giá
const isPriceModalOpen = ref(false);
const editingProductId = ref<number | null>(null);
const editingPrice = ref<number>(0);

// Khởi tạo các biến cho chiết khấu
const isDiscountModalOpen = ref(false);
const discount = ref<{ type: string; value: number } | null>(null);
const modalDiscountType = ref<'amount' | 'percent'>('amount');
const modalDiscountInput = ref<string>('');
const discountError = ref<string>('');

// Khởi tạo dữ liệu từ đơn hàng hiện tại
onMounted(() => {
    // Thêm event listener cho click outside
    document.addEventListener('click', handleClickOutside);

    // Khởi tạo dữ liệu từ đơn hàng hiện tại
    initializeFromBatch();
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Hàm khởi tạo dữ liệu từ đơn hàng hiện tại
function initializeFromBatch() {
    // Khởi tạo danh sách sản phẩm đã chọn
    selectedProducts.value = props.batchItem.map((item) => ({
        id: item.product_id,
        name: item.product.name,
        sku: item.product.sku,
        purchase_price: item.purchase_price,
        quantity: item.received_quantity,
        total: item.total_amount,
        total_amount: item.total_amount,
        image_url: item.product.image_url,
        description: item.product.description,
        manufacturing_date: item.manufacturing_date,
        expiry_date: item.expiry_date,
    }));

    // Tìm và thiết lập nhà cung cấp
    const supplier = props.suppliers.find((s) => s.id === currentBatch.value.supplier_id);
    if (supplier) {
        selectedSupplier.value = supplier;
    }

    // Tìm và thiết lập người dùng phụ trách
    const user = props.users.find((u) => u.id === currentBatch.value.created_by);
    if (user) {
        selectedUser.value = user;
    }

    // Thiết lập ngày nhập dự kiến
if (currentBatch.value.received_date) { // Use received_date or import_date based on your database
        const date = new Date(currentBatch.value.received_date);
        // Format to YYYY-MM-DDTHH:mm for datetime-local input
        importDate.value = date.toISOString().substring(0, 16);
    } else {
        importDate.value = ''; // Set to empty string if no date
    }

    // Thiết lập mã đơn hàng
    batchCode.value = currentBatch.value.batch_number;

    // Thiết lập ghi chú
    notes.value = currentBatch.value.notes;

    // Thiết lập chiết khấu
    if (currentBatch.value.discount_amount && currentBatch.value.discount_type) {
        discount.value = {
            type: currentBatch.value.discount_type,
            value: currentBatch.value.discount_amount,
        };
        modalDiscountType.value = currentBatch.value.discount_type as 'amount' | 'percent';
        modalDiscountInput.value = currentBatch.value.discount_amount.toString();
    }
}

// Computed properties
const filteredProducts = computed(() => {
    if (!searchQuery.value.trim()) return props.products.data;

    const query = searchQuery.value.toLowerCase();
    return props.products.data.filter((product) => product.name.toLowerCase().includes(query) || product.sku.toLowerCase().includes(query));
});

const subtotal = computed(() => {
    return selectedProducts.value.reduce((sum, product) => sum + product.total, 0);
});

const formattedSubtotal = computed(() => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(subtotal.value);
});

const discountAmount = computed(() => {
    if (!discount.value) return 0;

    if (discount.value.type === 'amount') {
        return discount.value.value;
    } else {
        return (subtotal.value * discount.value.value) / 100;
    }
});

const formattedDiscount = computed(() => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(discountAmount.value);
});

const totalAfterDiscount = computed(() => {
    return subtotal.value - discountAmount.value;
});

const filteredSuppliers = computed(() => {
    if (!supplierSearchQuery.value.trim()) return props.suppliers;

    const query = supplierSearchQuery.value.toLowerCase();
    return props.suppliers.filter(
        (supplier) =>
            supplier.name.toLowerCase().includes(query) ||
            (supplier.email && supplier.email.toLowerCase().includes(query)) ||
            (supplier.phone && supplier.phone.includes(query)),
    );
});

const filteredUsers = computed(() => {
    if (!userSearchQuery.value.trim()) return props.users;

    const query = userSearchQuery.value.toLowerCase();
    return props.users.filter((user) => user.name.toLowerCase().includes(query));
});

// Utility functions
function goBack() {
    window.history.back();
}

function formatPrice(price: number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
}

function handleClickOutside(event: MouseEvent) {
    // Xử lý dropdown sản phẩm
    if (
        dropdownRef.value &&
        !dropdownRef.value.contains(event.target as Node) &&
        searchInputRef.value &&
        !searchInputRef.value.contains(event.target as Node)
    ) {
        isDropdownOpen.value = false;
    }

    // Xử lý dropdown nhà cung cấp
    if (supplierDropdownRef.value && !supplierDropdownRef.value.contains(event.target as Node)) {
        isSupplierDropdownOpen.value = false;
    }

    // Xử lý dropdown người dùng
    if (userDropdownRef.value && !userDropdownRef.value.contains(event.target as Node)) {
        isUserDropdownOpen.value = false;
    }
}

// Product dropdown functions
function openDropdown() {
    isDropdownOpen.value = true;
}

function closeDropdown() {
    isDropdownOpen.value = false;
}

function selectProduct(product: Product) {
    // Kiểm tra xem sản phẩm đã được chọn chưa
    const existingProduct = selectedProducts.value.find((p) => p.id === product.id);

    if (existingProduct) {
        // Nếu đã chọn, tăng số lượng lên 1
        existingProduct.quantity += 1;
        existingProduct.total = existingProduct.quantity * existingProduct.purchase_price;
        existingProduct.total_amount = existingProduct.total;
    } else {
        // Nếu chưa chọn, thêm vào danh sách với số lượng là 1
        const newProduct: SelectedProduct = {
            ...product,
            quantity: 1,
            purchase_price: product.purchase_price || 0,
            total: product.purchase_price || 0,
            total_amount: product.purchase_price || 0,
        };
        selectedProducts.value.push(newProduct);
    }

    // Đóng dropdown
    closeDropdown();
    // Xóa query tìm kiếm
    searchQuery.value = '';
}

function removeProduct(productId: number) {
    selectedProducts.value = selectedProducts.value.filter((p) => p.id !== productId);
}

function updateQuantity(productId: number, quantity: number) {
    if (quantity < 1) return;

    const product = selectedProducts.value.find((p) => p.id === productId);
    if (product) {
        product.quantity = quantity;
        product.total = product.quantity * product.purchase_price;
        product.total_amount = product.total;
    }
}

// Supplier dropdown functions
function openSupplierDropdown() {
    isSupplierDropdownOpen.value = true;
}

function closeSupplierDropdown() {
    isSupplierDropdownOpen.value = false;
}

function selectSupplier(supplier: Supplier) {
    selectedSupplier.value = supplier;
    supplierError.value = '';
    closeSupplierDropdown();
    supplierSearchQuery.value = '';
}

function unselectSupplier() {
    selectedSupplier.value = null;
}

// User dropdown functions
function openUserDropdown() {
    isUserDropdownOpen.value = true;
}

function closeUserDropdown() {
    isUserDropdownOpen.value = false;
}

function selectUser(user: User) {
    selectedUser.value = user;
    closeUserDropdown();
    userSearchQuery.value = '';
}

// Price modal functions
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
    if (editingProductId.value === null) return;

    const product = selectedProducts.value.find((p) => p.id === editingProductId.value);
    if (product && editingPrice.value >= 0) {
        product.purchase_price = editingPrice.value;
        product.total = product.quantity * product.purchase_price;
        product.total_amount = product.total;
    }

    closePriceModal();
}

// Discount modal functions
function openDiscountModal() {
    if (discount.value) {
        modalDiscountType.value = discount.value.type as 'amount' | 'percent';
        modalDiscountInput.value = discount.value.value.toString();
    } else {
        modalDiscountType.value = 'amount';
        modalDiscountInput.value = '';
    }

    isDiscountModalOpen.value = true;
}

function closeDiscountModal() {
    isDiscountModalOpen.value = false;
    discount.value = null;
    modalDiscountInput.value = '';
    discountError.value = '';
}

function setDiscountType(type: 'amount' | 'percent') {
    modalDiscountType.value = type;
}

function saveDiscount() {
    discountError.value = '';

    const value = parseFloat(modalDiscountInput.value);

    if (isNaN(value) || value < 0) {
        discountError.value = 'Vui lòng nhập giá trị hợp lệ';
        return;
    }

    if (modalDiscountType.value === 'percent' && value > 100) {
        discountError.value = 'Phần trăm chiết khấu không thể vượt quá 100%';
        return;
    }

    if (modalDiscountType.value === 'amount' && value > subtotal.value) {
        discountError.value = 'Giá trị chiết khấu không thể vượt quá tổng tiền';
        return;
    }

    discount.value = {
        type: modalDiscountType.value,
        value: value,
    };

    isDiscountModalOpen.value = false;
}

// Watch for changes in search query
watch(searchQuery, (newValue) => {
    if (newValue.trim().length > 0) {
        isLoading.value = true;
        // Simulate API call
        setTimeout(() => {
            isLoading.value = false;
        }, 300);
    }
});

// Submit function
function submitBatch() {
    // Kiểm tra nhà cung cấp
    if (!selectedSupplier.value) {
        supplierError.value = 'Vui lòng chọn nhà cung cấp';
        return;
    }

const payload = {
    batch_items: selectedProducts.value.map((product) => ({
        product_id: product.id,
        received_quantity: product.quantity,
        purchase_price: product.purchase_price,
        total_amount: product.total_amount,
        manufacturing_date: product.manufacturing_date,
        expiry_date: product.expiry_date,
    })),
    supplier_id: selectedSupplier.value.id,
    discount_type: discount.value ? discount.value.type : null,
    discount_amount: discount.value ? discount.value.value : null,
    total_amount: totalAfterDiscount.value,
    created_by: selectedUser.value ? selectedUser.value.id : null,
    import_date: importDate.value,
    batch_number: batchCode.value,
    notes: notes.value,
};

    const options: VisitOptions = {
        onSuccess: () => {
            // Sau khi update thành công thì chuyển về trang show
            router.visit(route('admin.batches.show', currentBatch.value.id));
        },
        onError: (errors) => {
            console.error('Lỗi khi update batch:', errors);
        },
    };

    router.put(route('admin.batches.update', currentBatch.value.id), payload, options);
}

</script>

<template>

    <Head title="Edit Batch" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 p-4">
            <div class="mx-auto max-w-7xl">
                <!-- Header -->
                <div class="mb-4 flex items-center">
                    <button @click="goBack"
                        class="mb-0 flex h-10 w-10 items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:border-gray-400 hover:text-gray-800">
                        <ChevronLeft class="h-5 w-5" />
                    </button>
                    <h1 class="ml-4 text-3xl font-bold text-gray-900">Chỉnh sửa đơn nhập hàng</h1>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <!-- Left Section - Order Details -->
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Chi tiết nhập hàng</h2>
                            </div>
                            <div class="space-y-6 p-6">
                                <!-- Selected Products -->
                                <div v-if="selectedProducts.length > 0" class="space-y-3">
                                    <h3 class="text-sm font-medium text-gray-700">Sản phẩm đã chọn</h3>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Sản phẩm
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Số lượng
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                                                    Đơn giá
                                                </th>
                                                <th scope="col"
                                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
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
                                                        <img :src="product.image_url || '/storage/piclumen-1747750187180.png'"
                                                            :alt="product.name"
                                                            class="h-12 w-12 rounded-lg border border-gray-200 object-cover" />
                                                        <div>
                                                            <h4 class="font-medium text-gray-900">{{ product.name }}
                                                            </h4>
                                                            <p class="text-sm text-gray-500">SKU: {{ product.sku }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="number" min="1" :value="product.quantity"
                                                        @input="updateQuantity(product.id, parseInt(($event.target as HTMLInputElement).value))"
                                                        class="w-16 rounded border border-gray-300 px-2 py-1 text-center text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                                                </td>
                                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                                    <button class="text-blue-600 underline hover:text-blue-800"
                                                        @click="openPriceModal(product)" type="button">
                                                        {{ formatPrice(product.purchase_price) }}
                                                    </button>
                                                </td>
                                                <td
                                                    class="px-6 py-4 text-sm font-semibold whitespace-nowrap text-gray-900">
                                                    {{ formatPrice(product.total) }}
                                                </td>
                                                <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                                                    <button @click="removeProduct(product.id)"
                                                        class="text-red-500 hover:text-red-700">
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
                                        <Search
                                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                        <input ref="searchInputRef" v-model="searchQuery" type="text"
                                            placeholder="Tìm kiếm sản phẩm..." @focus="openDropdown"
                                            @keydown.escape="closeDropdown"
                                            class="h-12 w-full rounded-md border border-gray-300 pr-4 pl-10 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                        <button v-if="isDropdownOpen" @click="closeDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                            <ChevronUp class="h-4 w-4" />
                                        </button>
                                        <button v-else @click="openDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                            <ChevronDown class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <!-- Dropdown -->
                                    <div v-if="isDropdownOpen"
                                        class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                        <div class="max-h-80 overflow-y-auto">
                                            <!-- Loading state -->
                                            <div v-if="isLoading" class="p-4 text-center text-gray-500">
                                                <div
                                                    class="mx-auto h-6 w-6 animate-spin rounded-full border-b-2 border-blue-500">
                                                </div>
                                                <p class="mt-2 text-sm">Đang tải...</p>
                                            </div>

                                            <!-- Product list -->
                                            <div v-else-if="filteredProducts.length > 0">
                                                <button v-for="product in filteredProducts" :key="product.id"
                                                    @click="selectProduct(product)"
                                                    class="w-full border-b border-gray-100 p-4 text-left last:border-b-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none">
                                                    <div class="flex items-center space-x-4">
                                                        <img :src="product.image_url || '/storage/piclumen-1747750187180.png'"
                                                            :alt="product.name"
                                                            class="h-16 w-16 rounded-lg border border-gray-200 object-cover" />
                                                        <div class="flex-1">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <h4 class="font-medium text-gray-900">{{
                                                                        product.name }}</h4>
                                                                    <p class="text-sm text-gray-500">{{ product.sku }}
                                                                    </p>
                                                                    <p v-if="product.description"
                                                                        class="line-clamp-1 text-sm text-gray-400">
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
                                        <button class="flex items-center text-sm text-blue-600 hover:text-blue-700"
                                            @click="openDiscountModal" type="button">
                                            Chiết khấu đơn
                                        </button>
                                        <span class="min-w-[80px] text-right font-medium text-red-600">{{
                                            formattedDiscount }}</span>
                                    </div>
                                </div>

                                <div class="border-t pt-4">
                                    <div class="flex items-center justify-between text-lg font-semibold">
                                        <span>Tiền cần trả NCC</span>
                                        <span>{{
                                            new Intl.NumberFormat('vi-VN', {
                                                style: 'currency', currency: 'VND'
                                            }).format(totalAfterDiscount)
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
                                        <Search
                                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                        <input v-model="supplierSearchQuery" type="text"
                                            placeholder="Tìm kiếm nhà cung cấp..." @focus="openSupplierDropdown"
                                            @keydown.escape="closeSupplierDropdown"
                                            class="h-12 w-full rounded-md border border-gray-300 pr-4 pl-10 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                        <button v-if="isSupplierDropdownOpen" @click="closeSupplierDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                            <ChevronUp class="h-4 w-4" />
                                        </button>
                                        <button v-else @click="openSupplierDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                            <ChevronDown class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <!-- Supplier Dropdown -->
                                    <div v-if="isSupplierDropdownOpen"
                                        class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                        <div class="max-h-80 overflow-y-auto">
                                            <div v-if="filteredSuppliers.length > 0">
                                                <button v-for="supplier in filteredSuppliers" :key="supplier.id"
                                                    @click="selectSupplier(supplier)"
                                                    class="w-full border-b border-gray-100 p-4 text-left last:border-b-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none">
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-gray-900">{{ supplier.name
                                                        }}</span>
                                                        <span v-if="supplier.email" class="text-sm text-gray-500">{{
                                                            supplier.email }}</span>
                                                        <span v-if="supplier.phone" class="text-sm text-gray-500">{{
                                                            supplier.phone }}</span>
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
                                        <p v-if="selectedSupplier.email" class="text-black-400">{{
                                            selectedSupplier.email }}</p>
                                        <p v-if="selectedSupplier.phone" class="text-black-400">{{
                                            selectedSupplier.phone }}</p>
                                        <p v-if="selectedSupplier.address" class="text-black-400">{{
                                            selectedSupplier.address }}</p>
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
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Nhân viên phụ
                                        trách</label>
                                    <!-- Hiển thị nhân viên đã chọn -->
                                    <div v-if="selectedUser"
                                        class="mb-2 flex items-center justify-between rounded-md border border-gray-300 bg-blue-50 p-2">
                                        <div class="flex items-center">
                                            <span class="font-medium text-gray-900">{{ selectedUser.name }}</span>
                                        </div>
                                        <button @click="selectedUser = null"
                                            class="ml-2 rounded-full p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600">
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <!-- Input tìm kiếm -->
                                    <div class="relative">
                                        <Search
                                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                        <input type="text" placeholder="Tìm kiếm" v-model="userSearchQuery"
                                            @focus="openUserDropdown" @keydown.escape="closeUserDropdown"
                                            class="h-10 w-full rounded-md border border-gray-300 pr-4 pl-10 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                        <button v-if="isUserDropdownOpen" @click="closeUserDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                            <ChevronUp class="h-4 w-4" />
                                        </button>
                                        <button v-else @click="openUserDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                            <ChevronDown class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <!-- User Dropdown -->
                                    <div v-if="isUserDropdownOpen"
                                        class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                        <div class="max-h-80 overflow-y-auto">
                                            <div v-if="filteredUsers.length > 0">
                                                <button v-for="user in filteredUsers" :key="user.id"
                                                    @click="selectUser(user)"
                                                    class="w-full border-b border-gray-100 p-4 text-left last:border-b-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none">
                                                    <div class="flex flex-col">
                                                        <span class="font-medium text-gray-900">{{ user.name }}</span>
                                                    </div>
                                                </button>
                                            </div>
                                            <div v-else class="p-4 text-center text-gray-500">
                                                <p class="text-sm">Không tìm thấy tài khoản nào</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Ngày nhập dự kiến -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhập dự
                                        kiến</label>
                                    <input type="datetime-local" v-model="importDate"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <!-- Mã đơn đặt hàng nhập -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Mã đơn nhập hàng</label>
                                    <input type="text" v-model="batchCode" placeholder="Nhập mã đơn nhập hàng"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Ghi chú -->
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Ghi chú</h2>
                            </div>
                            <div class="p-4">
                                <textarea v-model="notes" placeholder="Thêm ghi chú..."
                                    class="min-h-[80px] w-full rounded-md border border-gray-300 p-2 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                        </div>

                        <button class="mt-4 h-12 w-full rounded-md bg-blue-500 font-medium text-white hover:bg-blue-600"
                            @click="submitBatch" v-if="selectedProducts.length > 0">
                            Cập nhật đơn nhập hàng
                        </button>
                        <button class="mt-4 h-12 w-full rounded-md bg-gray-500 font-medium text-white hover:bg-gray-600"
                            @click="submitBatch" v-else disabled>
                            Cập nhật đơn nhập hàng
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
                    <input type="number" min="0" v-model.number="editingPrice"
                        class="mb-4 w-full rounded border border-gray-300 px-3 py-2" />
                    <div class="flex justify-end space-x-2">
                        <button @click="closePriceModal"
                            class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Hủy</button>
                        <button @click="savePrice"
                            class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Lưu</button>
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
                            <button :class="[
                                'h-10 rounded-l border border-gray-300 px-4 whitespace-nowrap',
                                modalDiscountType === 'amount' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                            ]" @click="setDiscountType('amount')" type="button">
                                Giá trị
                            </button>
                            <button :class="[
                                'h-10 rounded-r border-t border-r border-b border-gray-300 px-4',
                                modalDiscountType === 'percent' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                            ]" @click="setDiscountType('percent')" type="button">
                                %
                            </button>
                        </div>
                        <div class="flex items-center">
                            <input v-model="modalDiscountInput" placeholder="0" type="text" min="0"
                                :max="modalDiscountType === 'percent' ? 100 : subtotal"
                                class="h-10 w-32 rounded-l border border-r-0 border-gray-300 px-2 focus:outline-none" />
                            <button class="h-10 w-[36px] rounded-r border border-l-0 border-gray-300 px-2"
                                type="button">
                                <span v-if="modalDiscountType === 'amount'" class="text-gray-500">₫</span>
                                <span v-else class="text-gray-500">%</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="discountError" class="mb-2 text-sm text-red-600">{{ discountError }}</div>
                    <!-- Footer -->
                    <div class="flex justify-end space-x-2">
                        <button @click="closeDiscountModal"
                            class="bg-white-200 rounded border-1 border-red-500 px-4 py-1 font-semibold text-red-500 hover:bg-red-100">
                            Xóa
                        </button>
                        <button @click="saveDiscount"
                            class="rounded bg-blue-500 px-4 py-1 font-semibold text-white hover:bg-blue-400">Lưu</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="css" scoped></style>
