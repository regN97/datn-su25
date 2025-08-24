<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
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
    manufacturing_date?: string;
    expiry_date?: string;
}

interface ProductsResponse {
    data: Product[];
    total: number;
}

// Props from Laravel via Inertia
interface Props {
    products?: ProductsResponse;
    suppliers?: Supplier[];
    user?: User;
    batch?: {
        id: number;
        supplier_id: number | null;
        total_amount: number;
        discount: { type: 'amount' | 'percent'; value: number };
        batch_items: Array<{
            product_id: number;
            received_quantity: number;
            purchase_price: number;
            total_amount: number;
            manufacturing_date?: string;
            expiry_date?: string;
        }>;
        import_date: string;
        reference_code: string;
        note: string;
        payment_status: 'paid' | 'partially_paid' | 'unpaid';
        paid_amount: number;
        payment_date: string;
        payment_method: 'cash' | 'bank_transfer' | 'credit_card' | '';
        status: 'draft' | 'pending' | 'completed';
    } | null;
}

const props = withDefaults(defineProps<Props>(), {
    products: () => ({
        data: [],
        total: 0,
    }),
    suppliers: () => [],
    user: () => ({
        id: 0,
        name: '',
    }),
    batch: null
});

// Thêm computed để check edit mode
const isEditMode = computed(() => !!props.batch?.id);

// Load dữ liệu batch khi vào edit mode
onMounted(() => {
    if (props.batch) {
        form.supplier_id = props.batch.supplier_id;
        form.discount = props.batch.discount;
        form.total_amount = props.batch.total_amount;
        form.batch_items = props.batch.batch_items.map(item => ({
            product_id: item.product_id,
            received_quantity: item.received_quantity,
            purchase_price: item.purchase_price,
            total_amount: item.total_amount,
            manufacturing_date: item.manufacturing_date,
            expiry_date: item.expiry_date,
        }));
        form.import_date = props.batch.import_date;
        form.reference_code = props.batch.reference_code;
        form.note = props.batch.note;
        form.payment_status = props.batch.payment_status;
        form.paid_amount = props.batch.paid_amount;
        form.payment_date = props.batch.payment_date;
        form.payment_method = props.batch.payment_method;
        selectedSupplier.value = props.batch ? props.suppliers.find(s => s.id === props.batch?.supplier_id) || null : null;
    }
    document.addEventListener('click', handleClickOutside);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tạo đơn nhập hàng',
        href: '/admin/batches/create',
    },
];

// Initialize form with useForm
const form = useForm({
    batch_items: [] as Array<{
        product_id: number;
        manufacturing_date?: string;
        expiry_date?: string;
        received_quantity: number;
        purchase_price: number;
        total_amount: number;
    }>,
    discount: { type: 'amount' as 'amount' | 'percent', value: 0 },
    total_amount: 0,
    supplier_id: null as number | null,
    user_id: props.user.id,
    import_date: '',
    reference_code: '',
    note: '',
    payment_status: 'unpaid' as 'paid' | 'partially_paid' | 'unpaid',
    paid_amount: 0,
    payment_date: '',
    payment_method: '' as 'cash' | 'bank_transfer' | 'credit_card' | '',
});

// Reactive data
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const isLoading = ref(false);
const searchInputRef = ref<HTMLInputElement>();
const dropdownRef = ref<HTMLElement>();
const supplierSearchQuery = ref('');
const isSupplierDropdownOpen = ref(false);
const selectedSupplier = ref<Supplier | null>(null);
const supplierDropdownRef = ref<HTMLElement>();
const isPriceModalOpen = ref(false);
const editingProductId = ref<number | null>(null);
const editingPrice = ref(0);
const isDiscountModalOpen = ref(false);
const modalDiscountType = ref<'amount' | 'percent'>('amount');
const modalDiscountInput = ref('');
const discountError = ref('');
const supplierError = ref('');

// Computed properties
const selectedProducts = computed(() => {
    return form.batch_items.map((item) => {
        const product = props.products.data.find((p) => p.id === item.product_id);
        return {
            ...product,
            quantity: item.received_quantity,
            total: item.total_amount,
            manufacturing_date: item.manufacturing_date,
            expiry_date: item.expiry_date,
            purchase_price: item.purchase_price,
        } as SelectedProduct;
    });
});

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products.data;
    return props.products.data.filter(
        (product) =>
            product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            product.sku.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});

const subtotal = computed(() => {
    return form.batch_items.reduce((sum, item) => sum + item.total_amount, 0);
});

const formattedSubtotal = computed(() => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(subtotal.value);
});

const formattedDiscount = computed(() => {
    if (form.discount.value === 0) return '0₫';
    if (form.discount.type === 'amount') {
        return `-${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(form.discount.value)}`;
    }
    return `-${form.discount.value}%`;
});

const discountAmount = computed(() => {
    if (form.discount.type === 'amount') {
        return Math.min(form.discount.value, subtotal.value);
    }
    return Math.round((subtotal.value * form.discount.value) / 100);
});

const totalAfterDiscount = computed(() => {
    return Math.max(subtotal.value - discountAmount.value, 0);
});

const formattedPaidAmount = computed(() => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(form.paid_amount);
});

const filteredSuppliers = computed(() => {
    if (!supplierSearchQuery.value) return props.suppliers;
    return props.suppliers.filter(
        (supplier) =>
            supplier.name.toLowerCase().includes(supplierSearchQuery.value.toLowerCase()) ||
            (supplier.email && supplier.email.toLowerCase().includes(supplierSearchQuery.value.toLowerCase())) ||
            (supplier.phone && supplier.phone.includes(supplierSearchQuery.value)),
    );
});

// Methods
function goBack() {
    window.history.back();
}

const now = () => {
  const d = new Date();
const pad = (n: number) => n.toString().padStart(2, "0");
  return (
    d.getFullYear() +
    "-" +
    pad(d.getMonth() + 1) +
    "-" +
    pad(d.getDate()) +
    "T" +
    pad(d.getHours()) +
    ":" +
    pad(d.getMinutes())
  );
};

function formatPrice(price: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(price);
}

function fetchProducts(search: string = '') {
    isLoading.value = true;
    router.get(
        route('admin.batches.create'),
        {
            search: search,
            per_page: 100,
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
    const existingItem = form.batch_items.find((item) => item.product_id === product.id);
    if (existingItem) {
        existingItem.received_quantity += 1;
        existingItem.total_amount = existingItem.received_quantity * existingItem.purchase_price;
    } else {
        form.batch_items.push({
            product_id: product.id,
            received_quantity: 1,
            purchase_price: 0,
            total_amount: 0,
            manufacturing_date: '',
            expiry_date: '',
        });
    }
    searchQuery.value = '';
    closeDropdown();
    router.get(
        route('admin.batches.create'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function updateDate(productId: number, dateType: 'manufacturing_date' | 'expiry_date', value: string) {
    const item = form.batch_items.find(item => item.product_id === productId);
    if (item) {
        item[dateType] = value;
    }
}

function updateQuantity(productId: number, quantity: number) {
    const item = form.batch_items.find((item) => item.product_id === productId);
    if (item && quantity > 0) {
        item.received_quantity = quantity;
        item.total_amount = item.received_quantity * item.purchase_price;
    }
}

function openSupplierDropdown() {
    isSupplierDropdownOpen.value = true;
}

function closeSupplierDropdown() {
    isSupplierDropdownOpen.value = false;
}

function selectSupplier(supplier: Supplier) {
    selectedSupplier.value = supplier;
    form.supplier_id = supplier.id;
    supplierSearchQuery.value = '';
    closeSupplierDropdown();
}

function unselectSupplier() {
    selectedSupplier.value = null;
    form.supplier_id = null;
    supplierSearchQuery.value = '';
}

function handleClickOutside(event: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown();
    }
    if (supplierDropdownRef.value && !supplierDropdownRef.value.contains(event.target as Node)) {
        closeSupplierDropdown();
    }
}

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
    const item = form.batch_items.find((item) => item.product_id === editingProductId.value);
    if (item) {
        item.purchase_price = editingPrice.value;
        item.total_amount = item.received_quantity * item.purchase_price;
    }
    closePriceModal();
}

function openDiscountModal() {
    isDiscountModalOpen.value = true;
    modalDiscountType.value = form.discount.type;
    modalDiscountInput.value = form.discount.value ? form.discount.value.toString() : '';
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
    form.discount.type = modalDiscountType.value;
    form.discount.value = val;
    closeDiscountModal();
}

function handleFormattedInput(event: Event) {
    const target = event.target as HTMLInputElement | null;
    if (!target) return;
    const rawValue = target.value.replace(/[^\d]/g, '');
    const numericValue = parseFloat(rawValue);
    let newPaidAmount = isNaN(numericValue) ? 0 : numericValue;
    if (newPaidAmount > totalAfterDiscount.value) {
        newPaidAmount = totalAfterDiscount.value;
    }
    form.paid_amount = newPaidAmount;
    target.value = new Intl.NumberFormat('vi-VN').format(newPaidAmount);
}

const todayStr = new Date().toISOString().split('T')[0];

function validateProductDates(item: typeof form.batch_items[number]) {
    const errors: { manufacturing_date?: string; expiry_date?: string } = {};
    if (item.manufacturing_date) {
        if (item.manufacturing_date > todayStr) {
            errors.manufacturing_date = 'NSX phải nhỏ hơn ngày hiện tại';
        }
    }
    if (item.expiry_date && item.manufacturing_date) {
        if (item.expiry_date <= item.manufacturing_date) {
            errors.expiry_date = 'HSD phải lớn hơn NSX';
        }
    }
    return errors;
}

function submitBatch() {
    for (const item of form.batch_items) {
        const errors = validateProductDates(item);
        const product = props.products.data.find((p) => p.id === item.product_id);
        if (errors.manufacturing_date || errors.expiry_date) {
            let msg = '';
            if (errors.manufacturing_date) msg += `Sản phẩm "${product?.name}": ${errors.manufacturing_date}\n`;
            if (errors.expiry_date) msg += `Sản phẩm "${product?.name}": ${errors.expiry_date}\n`;
            Swal.fire({
                icon: 'error',
                title: 'Lỗi ngày sản xuất/hết hạn',
                text: msg.trim(),
            });
            return;
        }
    }

    if (!form.supplier_id) {
        supplierError.value = 'Vui lòng chọn nhà cung cấp';
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn nhà cung cấp',
        });
        return;
    }

    if (form.batch_items.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn ít nhất một sản phẩm',
        });
        return;
    }

    if (!form.import_date) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn ngày nhập hàng',
        });
        return;
    }

    if ((form.payment_status === 'paid' || form.payment_status === 'partially_paid') && !form.payment_date) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn ngày thanh toán',
        });
        return;
    }

    // Cập nhật tổng tiền sau chiết khấu
    form.total_amount = totalAfterDiscount.value;

    // Gọi API tạo mới
    form.post(route('admin.batches.store'), {
        onStart: () => {
            isLoading.value = true;
        },
        onFinish: () => {
            isLoading.value = false;
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: Object.values(errors).join('\n') || 'Đã có lỗi xảy ra khi lưu đơn nhập hàng.',
            });
        },
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Đã tạo đơn nhập hàng thành công!',
            });
        },
    });
}


// Watch for search query changes
watch(searchQuery, (newQuery) => {
    if (isDropdownOpen.value) {
        fetchProducts(newQuery);
    }
});

// Watch payment status
watch(() => form.payment_status, (newStatus) => {
    if (newStatus === 'paid' || newStatus === 'partially_paid') {
        if (!form.payment_method) {
            form.payment_method = 'cash';
        }
        form.paid_amount = totalAfterDiscount.value;
        form.payment_date = new Date().toISOString().split('T')[0];
        form.reference_code = form.reference_code || '';
    } else {
        form.payment_method = '';
        form.paid_amount = 0;
        form.payment_date = '';
        form.reference_code = '';
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

    <Head title="Create Batch" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <form @submit.prevent="submitBatch">
            <div class="min-h-screen bg-gray-50 p-4">
                <div class="mx-auto max-w-7xl">
                    <!-- Header -->
                    <div class="mb-4 flex items-center">
                        <button @click="goBack" type="button"
                            class="mb-0 flex h-10 w-10 items-center justify-center rounded border border-gray-300 bg-white text-gray-600 hover:border-gray-400 hover:text-gray-800">
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <h1 class="ml-4 text-3xl font-bold text-gray-900">Tạo đơn nhập hàng</h1>
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
                                        <table class="min-w-full table-fixed divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th
                                                        class="w-[30%] px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Sản phẩm</th>
                                                    <th
                                                        class="w-[8%] px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                        Số lượng</th>
                                                    <th
                                                        class="w-[10%] px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                        Đơn giá</th>
                                                    <th
                                                        class="w-[14%] px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                        NSX</th>
                                                    <th
                                                        class="w-[14%] px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                        HSD</th>
                                                    <th
                                                        class="w-[12%] px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                                        Thành tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                <tr v-for="product in selectedProducts" :key="product.id">
                                                    <td class="px-4 py-3">
                                                        <div class="flex items-center space-x-4">
                                                            <img :src="product.image_url || '/storage/piclumen-1747750187180.png'"
                                                                :alt="product.name"
                                                                class="h-12 w-12 rounded-lg border border-gray-200 object-cover" />
                                                            <div>
                                                                <h4 class="text-xs font-medium text-gray-900">{{
                                                                    product.name }}</h4>
                                                                <p class="text-[11px] text-gray-500">SKU: {{ product.sku
                                                                    }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <input type="number" min="1" :value="product.quantity"
                                                            @input="updateQuantity(product.id, parseInt(($event.target as HTMLInputElement).value))"
                                                            class="w-16 rounded border border-gray-300 px-2 py-1 text-center text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                                                    </td>
                                                    <td class="px-4 py-3 text-center text-sm text-gray-900">
                                                        <button class="text-blue-600 underline hover:text-blue-800"
                                                            @click="openPriceModal(product)" type="button">
                                                            {{ formatPrice(product.purchase_price) }}
                                                        </button>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <input type="date"
                                                            :value="form.batch_items.find(item => item.product_id === product.id)?.manufacturing_date"
                                                            @input="updateDate(product.id, 'manufacturing_date', ($event.target as HTMLInputElement).value)"
                                                            class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                                                    </td>

                                                    <!-- Hạn sử dụng -->
                                                    <td class="px-4 py-3 text-center">
                                                        <input type="date"
                                                            :value="form.batch_items.find(item => item.product_id === product.id)?.expiry_date"
                                                            @input="updateDate(product.id, 'expiry_date', ($event.target as HTMLInputElement).value)"
                                                            class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                                                    </td>
                                                    <td
                                                        class="px-4 py-3 text-center text-sm font-semibold text-gray-900">
                                                        {{ formatPrice(product.total) }}
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
                                            <button v-if="isDropdownOpen" @click="closeDropdown" type="button"
                                                class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                                <ChevronUp class="h-4 w-4" />
                                            </button>
                                            <button v-else @click="openDropdown" type="button"
                                                class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                                <ChevronDown class="h-4 w-4" />
                                            </button>
                                        </div>

                                        <!-- Dropdown -->
                                        <div v-if="isDropdownOpen"
                                            class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                            <div class="max-h-80 overflow-y-auto">
                                                <div v-if="isLoading" class="p-4 text-center text-gray-500">
                                                    <div
                                                        class="mx-auto h-6 w-6 animate-spin rounded-full border-b-2 border-blue-500">
                                                    </div>
                                                    <p class="mt-2 text-sm">Đang tải...</p>
                                                </div>
                                                <div v-else-if="filteredProducts.length > 0">
                                                    <button v-for="product in filteredProducts" :key="product.id"
                                                        @click="selectProduct(product)" type="button"
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
                                                                        <p class="text-sm text-gray-500">{{ product.sku
                                                                            }}</p>
                                                                        <p v-if="product.description"
                                                                            class="line-clamp-1 text-sm text-gray-400">
                                                                            {{ product.description }}
                                                                        </p>
                                                                    </div>
                                                                    <div class="text-right">
                                                                        <p class="font-semibold text-blue-600">{{
                                                                            formatPrice(0) }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </button>
                                                </div>
                                                <div v-else class="p-4 text-center text-gray-500">
                                                    <p class="text-sm">Không tìm thấy sản phẩm nào</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Section -->
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
                                            <span>{{ new Intl.NumberFormat('vi-VN', {
                                                style: 'currency', currency: 'VND'
                                            }).format(totalAfterDiscount) }}</span>
                                        </div>
                                    </div>
                                    <div class="border-t pt-4" v-if="totalAfterDiscount > 0">
                                        <div class="w-full space-y-4 rounded-md bg-blue-50 p-6">
                                            <div class="space-y-4 rounded-md bg-blue-50 p-4">
                                                <div class="flex items-center space-x-6">
                                                    <label class="inline-flex items-center space-x-2">
                                                        <input type="radio" value="paid" v-model="form.payment_status"
                                                            class="form-radio text-blue-600" />
                                                        <span class="font-medium text-gray-800">Đã thanh toán</span>
                                                    </label>
                                                </div>
                                                <div v-if="form.payment_status === 'paid'"
                                                    class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Hình thức
                                                            thanh toán</label>
                                                        <select v-model="form.payment_method" name="payment_method"
                                                            class="border-white-300 mt-1 w-full rounded-md bg-white p-2 shadow-sm">
                                                            <option value="cash">Tiền mặt</option>
                                                            <option value="bank_transfer">Chuyển khoản</option>
                                                            <option value="credit_card">Thẻ</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Số tiền
                                                            thanh toán</label>
                                                        <div class="relative mt-1">
                                                            <input type="text" :value="formattedPaidAmount"
                                                                @input="handleFormattedInput"
                                                                class="w-full rounded-md bg-white p-2 pr-10 shadow-sm" />
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Ngày ghi
                                                            nhận</label>
                                                        <input type="date" v-model="form.payment_date"
                                                            name="payment_date"
                                                            class="mt-1 w-full rounded-md border-gray-300 bg-white p-2 shadow-sm" />
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700">Tham
                                                            chiếu</label>
                                                        <input type="text" v-model="form.reference_code"
                                                            placeholder="Nhập mã tham chiếu"
                                                            class="mt-1 w-full rounded-md border-gray-300 bg-white p-2 shadow-sm" />
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-6">
                                                    <label class="inline-flex items-center space-x-2">
                                                        <input type="radio" value="unpaid" v-model="form.payment_status"
                                                            class="form-radio text-blue-600" />
                                                        <span class="font-medium text-gray-800">Thanh toán sau</span>
                                                    </label>
                                                </div>
                                            </div>
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
                                    <h2 class="text-lg font-semibold">Tìm kiếm nhà cung cấp</h2>
                                    <div v-if="supplierError.length > 0" class="text-sm text-red-500">
                                        {{ supplierError }}
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div v-if="!selectedSupplier" class="relative" ref="supplierDropdownRef">
                                        <div class="relative">
                                            <Search
                                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                            <input v-model="supplierSearchQuery" type="text"
                                                placeholder="Tìm kiếm nhà cung cấp..." @focus="openSupplierDropdown"
                                                @keydown.escape="closeSupplierDropdown"
                                                class="h-12 w-full rounded-md border border-gray-300 pr-4 pl-10 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                            <button v-if="isSupplierDropdownOpen" @click="closeSupplierDropdown"
                                                type="button"
                                                class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                                <ChevronUp class="h-4 w-4" />
                                            </button>
                                            <button v-else @click="openSupplierDropdown" type="button"
                                                class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600">
                                                <ChevronDown class="h-4 w-4" />
                                            </button>
                                        </div>
                                        <div v-if="isSupplierDropdownOpen"
                                            class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                            <div class="max-h-80 overflow-y-auto">
                                                <div v-if="filteredSuppliers.length > 0">
                                                    <button v-for="supplier in filteredSuppliers" :key="supplier.id"
                                                        @click="selectSupplier(supplier)" type="button"
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
                                    <div v-if="selectedSupplier"
                                        class="rounded-md border border-gray-200 bg-gray-50 p-4">
                                        <div class="mb-2 flex items-center justify-between">
                                            <h3 class="font-medium text-gray-900">{{ selectedSupplier.name }}</h3>
                                            <button @click="unselectSupplier" type="button"
                                                class="text-gray-400 hover:text-gray-600">
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

                            <!-- Users Search and Additional Info -->
                            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-200 p-4">
                                    <h2 class="text-lg font-semibold">Thông tin bổ sung</h2>
                                </div>
                                <div class="space-y-4 p-4">
                                    <div class="relative">
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Nhân viên phụ
                                            trách</label>
                                        <input type="text" :value="props.user.name" disabled
                                            class="h-10 w-full rounded-md border border-gray-300 pr-4 pl-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhập
                                            hàng</label>
                                        <input type="datetime-local" v-model="form.import_date" :min="now()"
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
                                    <textarea v-model="form.note" placeholder="Thêm ghi chú..."
                                        class="min-h-[80px] w-full rounded-md border border-gray-300 p-2 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                            </div>

                            <button type="submit" :disabled="form.batch_items.length === 0"
                                class="mt-4 h-12 w-full rounded-md font-medium text-white"
                                :class="form.batch_items.length > 0 ? 'bg-blue-500 hover:bg-blue-600' : 'bg-gray-500 hover:bg-gray-600'">
                                Lưu đơn nhập hàng
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Price Modal -->
        <div v-if="isPriceModalOpen" class="fixed inset-0 z-50">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="mb-4 text-lg font-semibold">Chỉnh sửa đơn giá</h3>
                    <input type="number" min="0" v-model.number="editingPrice"
                        class="mb-4 w-full rounded border border-gray-300 px-3 py-2" />
                    <div class="flex justify-end space-x-2">
                        <button @click="closePriceModal" type="button"
                            class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Hủy</button>
                        <button @click="savePrice" type="button"
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
                    <h3 class="mb-6 border-b text-lg font-semibold">Thêm chiết khấu</h3>
                    <div class="mb-6 flex items-center gap-2 border-b pb-6">
                        <label class="font-medium whitespace-nowrap text-gray-700">Loại chiết khấu:</label>
                        <div class="flex items-center">
                            <button
                                :class="['h-10 rounded-l border border-gray-300 px-4 whitespace-nowrap', modalDiscountType === 'amount' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700']"
                                @click="setDiscountType('amount')" type="button">
                                Giá trị
                            </button>
                            <button
                                :class="['h-10 rounded-r border-t border-r border-b border-gray-300 px-4', modalDiscountType === 'percent' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700']"
                                @click="setDiscountType('percent')" type="button">
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
                    <div class="flex justify-end space-x-2">
                        <button @click="closeDiscountModal" type="button"
                            class="bg-white-200 rounded border-1 border-red-500 px-4 py-1 font-semibold text-red-500 hover:bg-red-100">
                            Xóa
                        </button>
                        <button @click="saveDiscount" type="button"
                            class="rounded bg-blue-500 px-4 py-1 font-semibold text-white hover:bg-blue-400">Lưu</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="css" scoped></style>