<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronLeft, ChevronUp } from 'lucide-vue-next';
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

interface SelectedProduct extends Product {
    quantity: number;
    rejectedQuantity: number;
    rejectedReason?: string;
    total: number;
    purchaseOrderItemId?: number;
    manufacturingDate?: string;
    expiryDate?: string;
}

interface ProductsResponse {
    data: Product[];
    total: number;
}

interface Props {
    products?: ProductsResponse;
    suppliers?: Supplier[];
    users?: User[];
    purchaseOrder: PurchaseOrder;
    purchaseOrderItem: PurchaseOrderItem[];
}
const props = withDefaults(defineProps<Props>(), {
    products: () => ({
        data: [],
        total: 0,
    }),
    suppliers: () => [],
    users: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tạo đơn nhập hàng',
        href: '/admin/batches/add/',
    },
];

const selectedPurchaseOrder = computed(() => props.purchaseOrder);

// Reactive data
// const searchQuery = ref('');
const isDropdownOpen = ref(false);
const selectedProducts = ref<SelectedProduct[]>([]);
const isLoading = ref(false);
// const searchInputRef = ref<HTMLInputElement>();
const dropdownRef = ref<HTMLElement>();
const isPriceModalOpen = ref(false);
const editingProductId = ref<number | null>(null);
const editingPrice = ref(0);
const isDiscountModalOpen = ref(false);
const discount = ref<{ type: 'amount' | 'percent'; value: number }>({
    type: props.purchaseOrder?.discount_type === 'percent' ? 'percent' : 'amount',
    value: props.purchaseOrder?.discount_amount || 0,
});
const modalDiscountType = ref<'amount' | 'percent'>(discount.value.type);
const modalDiscountInput = ref(discount.value.value.toString());
const discountError = ref('');
const notes = ref(props.purchaseOrder?.notes);
const supplierError = ref('');
const paymentStatus = ref('unpaid');
const paymentMethod = ref('cash');
const paymentDate = ref('');
const paymentReference = ref('');
const paidAmount = ref<number>(0);
const batchCode = ref(props.purchaseOrder?.po_number || '');
const invoiceCode = ref('');
const selectedUserId = ref<number | null>(props.purchaseOrder?.created_by || null);
const userSearchQuery = ref('');
const isUserDropdownOpen = ref(false);
const userDropdownRef = ref<HTMLElement>();
const isQuantityModalOpen = ref(false);
const editingQuantity = ref(0);
const editingRejectedQuantity = ref(0);
const editingRejectedReason = ref<string>('');
const rejectedValue = ref(0);

const expectedImportDate = ref('');

// Computed properties
const subtotal = computed(() => {
    return selectedProducts.value.reduce((sum, product) => sum + product.total, 0);
});

const filteredUsers = computed(() => {
    if (!userSearchQuery.value) return props.users;
    return props.users.filter((user) => user.name.toLowerCase().includes(userSearchQuery.value.toLowerCase()));
});

const discountAmount = computed(() => {
    if (discount.value.type === 'amount') {
        return Math.min(discount.value.value, subtotal.value);
    }
    return Math.round((subtotal.value * discount.value.value) / 100);
});

const totalAfterDiscount = computed(() => {
    return Math.max(subtotal.value - discountAmount.value, 0);
});

const formattedSubtotal = computed(() => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(subtotal.value);
});

const formattedTotalAfterDiscount = computed(() => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(totalAfterDiscount.value);
});

const formattedDiscount = computed(() => {
    if (discount.value.value === 0) return '0₫';
    if (discount.value.type === 'amount') {
        return `-${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(discount.value.value)}`;
    }
    return `-${discount.value.value}%`;
});

const formattedPaidAmount = computed(() => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
    }).format(paidAmount.value);
});

// const selectedUser = computed(() => props.users.find((u) => u.id === selectedUserId.value) || null);
const selectedSupplier = computed(() => props.suppliers.find((s) => s.id === props.purchaseOrder?.supplier_id) || null);
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

function handleFormattedInput(event: Event) {
    const target = event.target as HTMLInputElement | null;
    if (!target) return;

    const rawValue = target.value.replace(/[^\d]/g, '');
    const numericValue = parseFloat(rawValue);
    let newPaidAmount = isNaN(numericValue) ? 0 : numericValue;

    if (newPaidAmount > totalAfterDiscount.value) {
        newPaidAmount = totalAfterDiscount.value;
    }

    paidAmount.value = newPaidAmount;
    target.value = new Intl.NumberFormat('vi-VN').format(newPaidAmount);
}

function closeDropdown() {
    isDropdownOpen.value = false;
}

// function selectProduct(product: Product, purchaseOrderItemId?: number) {
//     const existingProduct = selectedProducts.value.find((p) => p.id === product.id);
//     const supplierPrice = product.suppliers?.length > 0 ? product.suppliers[0].pivot.purchase_price : product.purchase_price;

//     if (existingProduct) {
//         existingProduct.quantity += 1;
//         existingProduct.total = existingProduct.quantity * existingProduct.purchase_price;
//     } else {
//         selectedProducts.value.push({
//             ...product,
//             purchase_price: supplierPrice || 0,
//             quantity: 1,
//             rejectedQuantity: 0,
//             rejectedReason: '',
//             total: supplierPrice || 0,
//             purchaseOrderItemId,
//         });
//     }

//     searchQuery.value = '';
//     closeDropdown();

//     router.get(
//         route('admin.batches.add', { po_id: props.purchaseOrder.id }),
//         {},
//         {
//             preserveState: true,
//             preserveScroll: true,
//             replace: true,
//         },
//     );
// }

// function updateQuantity(productId: number, quantity: number) {
//     const product = selectedProducts.value.find((p) => p.id === productId);
//     if (product && quantity > 0) {
//         product.quantity = quantity;
//         product.total = product.quantity * product.purchase_price;
//     }
// }

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
    if (editingPrice.value < 0) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Đơn giá không được âm',
        });
        return;
    }
    const product = selectedProducts.value.find((p) => p.id === editingProductId.value);
    if (product) {
        product.purchase_price = editingPrice.value;
        product.total = product.quantity * product.purchase_price;
    }
    closePriceModal();
}

function openQuantityModal(product: SelectedProduct) {
    editingProductId.value = product.id;
    editingQuantity.value = product.quantity;
    editingRejectedQuantity.value = product.rejectedQuantity || 0;
    editingRejectedReason.value = product.rejectedReason || '';
    isQuantityModalOpen.value = true;
}

function saveQuantity() {
    const product = selectedProducts.value.find((p) => p.id === editingProductId.value);
    if (product) {
        product.quantity = editingQuantity.value;
        product.rejectedQuantity = editingRejectedQuantity.value;
        product.rejectedReason = editingRejectedReason.value; // Stored in front-end only
        rejectedValue.value = product.rejectedQuantity * product.purchase_price;
        product.total = product.quantity * product.purchase_price - rejectedValue.value;

        if (product.rejectedQuantity > product.quantity) {
            product.rejectedQuantity = 0;
            editingRejectedQuantity.value = 0;
            product.total = product.quantity * product.purchase_price;
            return Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Số lượng từ chối không được lớn hơn số lượng nhận',
            });
        }
    }
    closeQuantityModal();
    router.get(
        route('admin.batches.add', { po_id: props.purchaseOrder.id }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function closeQuantityModal() {
    isQuantityModalOpen.value = false;
    editingProductId.value = null;
}

function openDiscountModal() {
    isDiscountModalOpen.value = true;
    modalDiscountType.value = discount.value.type;
    modalDiscountInput.value = discount.value.value.toString();
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
            discountError.value = 'Chiết khấu không được lớn hơn tổng tiền';
            return;
        }
    } else {
        if (isNaN(val) || val < 0 || val > 100) {
            discountError.value = 'Phần trăm phải từ 0 đến 100';
            return;
        }
    }
    discount.value.type = modalDiscountType.value;
    discount.value.value = val;
    closeDiscountModal();
}

function selectUser(user: User) {
    selectedUserId.value = user.id;
    userSearchQuery.value = user.name;
    isUserDropdownOpen.value = false;
}

function toggleUserDropdown() {
    isUserDropdownOpen.value = !isUserDropdownOpen.value;
}

const todayStr = new Date().toISOString().split('T')[0];

function validateProductDates(product: SelectedProduct) {
    const errors: { manufacturingDate?: string; expiryDate?: string; isBlank?: boolean } = {};

    if (!product.manufacturingDate || !product.expiryDate) {
        errors.isBlank = true;
    }

    // Validate ngày sản xuất không lớn hơn ngày hiện tại
    if (product.manufacturingDate) {
        if (product.manufacturingDate > todayStr) {
            errors.manufacturingDate = 'NSX phải nhỏ hơn ngày hiện tại';
        }
    }

    // Validate ngày hết hạn phải sau ngày sản xuất
    if (product.expiryDate && product.manufacturingDate) {
        if (product.expiryDate <= product.manufacturingDate) {
            errors.expiryDate = 'HSD phải lớn hơn NSX';
        }
    }

    return errors;
}

function submitBatch() {
    for (const product of selectedProducts.value) {
        const errors = validateProductDates(product);
        if (errors.manufacturingDate || errors.expiryDate || errors.isBlank === true) {
            let msg = '';
            if (errors.manufacturingDate) msg += `Sản phẩm "${product.name}": ${errors.manufacturingDate}\n`;
            if (errors.expiryDate) msg += `Sản phẩm "${product.name}": ${errors.expiryDate}\n`;
            if (errors.isBlank === true) msg += 'Vui lòng nhập ngày sản xuất và hết hạn cho sản phẩm.\n';
            Swal.fire({
                icon: 'error',
                title: 'Lỗi ngày sản xuất/hết hạn',
                text: msg.trim(),
            });
            return;
        }
    }

    if (!selectedSupplier.value?.id) {
        supplierError.value = 'Vui lòng chọn nhà cung cấp';
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn nhà cung cấp',
        });
        return;
    }

    if (selectedProducts.value.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn ít nhất một sản phẩm',
        });
        return;
    }

    if (!expectedImportDate.value) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn ngày nhập hàng',
        });
        return;
    }

    if ((paymentStatus.value === 'paid' || paymentStatus.value === 'partially_paid') && !paymentDate.value) {
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Vui lòng chọn ngày thanh toán',
        });
        return;
    }

    const batchItems = selectedProducts.value.map((p) => ({
        product_id: p.id,
        purchase_order_item_id: p.purchaseOrderItemId || null,
        manufacturing_date: p.manufacturingDate,
        expiry_date: p.expiryDate,
        received_quantity: p.quantity - p.rejectedQuantity,
        rejected_quantity: p.rejectedQuantity,
        purchase_price: p.purchase_price,
        total_amount: p.total,
    }));

    const payload = {
        batch_items: batchItems,
        discount: {
            type: discount.value.type,
            value: discount.value.value,
        },
        total_amount: totalAfterDiscount.value,
        supplier_id: selectedSupplier.value?.id || null,
        user_id: selectedUserId.value || null,
        purchase_order_id: props.purchaseOrder?.id || null,
        expected_import_date: expectedImportDate.value,
        batch_code: batchCode.value,
        invoice_code: invoiceCode.value,
        notes: notes.value,
        payment_status: paymentStatus.value,
        paid_amount: Number(paidAmount.value) || 0,
        payment_date: paymentDate.value,
        payment_method: paymentStatus.value === 'unpaid' ? null : paymentMethod.value,
    };

    router.post(route('admin.batches.save'), payload, {
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

function handleClickOutside(event: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown();
    }
    if (userDropdownRef.value && !userDropdownRef.value.contains(event.target as Node)) {
        isUserDropdownOpen.value = false;
    }
}

watch(
    () => props.purchaseOrder?.created_by,
    (newUserId) => {
        selectedUserId.value = newUserId || null;
    },
);

watch(paymentStatus, (newStatus) => {
    if (newStatus === 'paid' || newStatus === 'partially_paid') {
        if (!paymentMethod.value) {
            paymentMethod.value = 'cash';
        }
        paidAmount.value = totalAfterDiscount.value;
        paymentDate.value = new Date().toISOString().split('T')[0];
        paymentReference.value = paymentReference.value || '';
    } else {
        paymentMethod.value = '';
        paidAmount.value = 0;
        paymentDate.value = '';
        paymentReference.value = '';
    }
});

watch(
    discount,
    () => {
        if (discount.value.type === 'amount' && discount.value.value > subtotal.value) {
            discount.value.value = subtotal.value;
        }
    },
    { deep: true },
);

watch(paidAmount, (newValue) => {
    if (isNaN(newValue) || newValue < 0) {
        paidAmount.value = 0;
    }
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    if (props.purchaseOrderItem && props.purchaseOrderItem.length > 0) {
        selectedProducts.value = props.purchaseOrderItem.map((item) => ({
            ...item.product,
            quantity: item.ordered_quantity,
            rejectedQuantity: (item as any).rejected_quantity || 0,
            rejectedReason: (item as any).rejected_reason || '',
            purchase_price: item.unit_cost,
            total: item.subtotal,
            purchaseOrderItemId: item.id,
        }));
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Create Batch" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 p-4">
            <div class="mx-auto max-w-7xl">
                <!-- Header -->
                <div class="mb-6">
                    <button @click="goBack" class="mb-4 flex cursor-pointer items-center text-gray-600 hover:text-gray-800">
                        <ChevronLeft class="mr-1 h-4 w-4" />
                        Quay lại
                    </button>
                    <h1 class="text-3xl font-bold text-gray-900">Tạo đơn nhập từ đơn đặt {{ selectedPurchaseOrder?.po_number }}</h1>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="space-y-6 p-6">
                                <!-- Selected Products -->
                                <div v-if="selectedProducts.length > 0" class="space-y-3">
                                    <h3 class="text-sm font-medium text-gray-700">Sản phẩm đã chọn</h3>
                                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    scope="col"
                                                    class="w-[22%] px-2 py-2 text-left font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Sản phẩm
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="w-[11%] px-2 py-2 text-center font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Số lượng
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="w-[13%] px-2 py-2 text-center font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Đơn giá
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="w-[14%] px-2 py-2 text-center font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    NSX
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="w-[14%] px-2 py-2 text-center font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    HSD
                                                </th>
                                                <th
                                                    scope="col"
                                                    class="w-[10%] px-2 py-2 text-left font-medium tracking-wider text-gray-500 uppercase"
                                                >
                                                    Tổng tiền
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <tr v-for="product in selectedProducts" :key="product.id">
                                                <td class="w-[22%] px-2 py-2 whitespace-nowrap">
                                                    <div class="flex items-center space-x-2">
                                                        <img
                                                            :src="product.image_url || '/storage/piclumen-1747750187180.png'"
                                                            :alt="product.name"
                                                            class="h-10 w-10 rounded-lg border border-gray-200 object-cover"
                                                        />
                                                        <div>
                                                            <h4 class="text-xs font-medium text-gray-900">{{ product.name }}</h4>
                                                            <p class="text-[11px] text-gray-500">SKU: {{ product.sku }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="w-[11%] text-center align-middle">
                                                    <div class="relative flex flex-col items-center justify-center">
                                                        <button
                                                            class="text-xs text-blue-600 hover:text-blue-800"
                                                            @click="openQuantityModal(product)"
                                                            type="button"
                                                        >
                                                            {{ product.quantity }}
                                                        </button>
                                                        <div v-if="product.rejectedQuantity > 0" class="group relative">
                                                            <span class="cursor-pointer text-xs text-red-600 select-none">{{
                                                                product.rejectedQuantity
                                                            }}</span>
                                                            <div
                                                                class="absolute z-50 hidden w-48 flex-col items-start rounded-lg border border-gray-300 bg-white px-2 py-2 text-xs text-gray-700 shadow-md group-hover:flex"
                                                                style="top: 100%; left: 50%; transform: translateX(-50%) translateY(8px)"
                                                            >
                                                                <div class="mb-1 flex items-center">
                                                                    <span class="mr-1 h-2 w-2 rounded-full bg-red-600"></span>
                                                                    <span class="font-semibold"
                                                                        >Số lượng đã từ chối: {{ product.rejectedQuantity }}</span
                                                                    >
                                                                </div>
                                                                <hr class="my-1 w-full border-gray-200" />
                                                                <div class="mb-1 text-xs text-gray-400">Lý do từ chối</div>
                                                                <div class="leading-snug break-words text-gray-800">
                                                                    {{ product.rejectedReason || 'Không có lý do' }}
                                                                </div>
                                                                <div
                                                                    class="absolute -top-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 transform border-t border-r border-gray-300 bg-white shadow-sm"
                                                                ></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="w-[13%] text-center text-xs whitespace-nowrap text-gray-900">
                                                    <button class="text-blue-600 hover:text-blue-800" @click="openPriceModal(product)" type="button">
                                                        {{ formatPrice(product.purchase_price) }}
                                                    </button>
                                                </td>
                                                <td class="w-[14%] text-center text-xs whitespace-nowrap">
                                                    <input
                                                        type="date"
                                                        v-model="product.manufacturingDate"
                                                        class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                    />
                                                </td>
                                                <td class="w-[14%] px-2 py-2 text-center text-xs whitespace-nowrap">
                                                    <input
                                                        type="date"
                                                        v-model="product.expiryDate"
                                                        class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                    />
                                                </td>
                                                <td class="w-[10%] px-2 py-2 text-xs font-semibold whitespace-nowrap text-gray-900">
                                                    {{ formatPrice(product.total) }}
                                                    <p v-if="product.rejectedQuantity > 0" class="mt-0.5 text-red-500">
                                                        {{ formatPrice(rejectedValue) }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
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
                                        <span>{{ formattedTotalAfterDiscount }}</span>
                                    </div>
                                </div>
                                <div class="border-t pt-4">
                                    <div class="w-full space-y-4 rounded-md bg-blue-50 p-6">
                                        <div class="space-y-4 rounded-md bg-blue-50 p-4">
                                            <div class="flex items-center space-x-6">
                                                <label class="inline-flex items-center space-x-2">
                                                    <input type="radio" value="paid" v-model="paymentStatus" class="form-radio text-blue-600" />
                                                    <span class="font-medium text-gray-800">Đã thanh toán</span>
                                                </label>
                                            </div>
                                            <div v-if="paymentStatus === 'paid'" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Hình thức thanh toán</label>
                                                    <select
                                                        v-model="paymentMethod"
                                                        name="payment_method"
                                                        class="border-white-300 mt-1 w-full rounded-md bg-white p-2 shadow-sm"
                                                    >
                                                        <option value="cash">Tiền mặt</option>
                                                        <option value="bank_transfer">Chuyển khoản</option>
                                                        <option value="credit_card">Thẻ</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Số tiền thanh toán</label>
                                                    <div class="relative mt-1">
                                                        <input
                                                            type="text"
                                                            :value="formattedPaidAmount"
                                                            @input="handleFormattedInput"
                                                            class="w-full rounded-md bg-white p-2 pr-10 shadow-sm"
                                                        />
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Ngày ghi nhận</label>
                                                    <input
                                                        type="date"
                                                        v-model="paymentDate"
                                                        name="payment_date"
                                                        class="mt-1 w-full rounded-md border-gray-300 bg-white p-2 shadow-sm"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Tham chiếu</label>
                                                    <input
                                                        type="text"
                                                        v-model="paymentReference"
                                                        placeholder="Nhập mã tham chiếu"
                                                        class="mt-1 w-full rounded-md border-gray-300 bg-white p-2 shadow-sm"
                                                    />
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-6">
                                                <label class="inline-flex items-center space-x-2">
                                                    <input type="radio" value="unpaid" v-model="paymentStatus" class="form-radio text-blue-600" />
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

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Thông tin bổ sung</h2>
                            </div>
                            <div class="space-y-4 p-4">
                                <div class="relative" ref="userDropdownRef">
                                    <label class="text-black-700 mb-1 block text-sm font-medium">Nhân viên phụ trách</label>
                                    <div class="relative">
                                        <input
                                            v-model="userSearchQuery"
                                            type="text"
                                            :placeholder="
                                                selectedUserId ? props.users.find((u) => u.id === selectedUserId)?.name : 'Tìm kiếm nhân viên...'
                                            "
                                            @focus="isUserDropdownOpen = true"
                                            @keydown.escape="isUserDropdownOpen = false"
                                            :class="[
                                                'border-black-300 h-10 w-full rounded-md border pr-10 pl-4 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500',
                                                selectedUserId ? 'text-black' : 'text-gray-500',
                                            ]"
                                        />
                                        <button
                                            @click="toggleUserDropdown"
                                            class="absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 hover:text-gray-600"
                                        >
                                            <ChevronDown v-if="!isUserDropdownOpen" class="h-4 w-4" />
                                            <ChevronUp v-else class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div
                                        v-if="isUserDropdownOpen"
                                        class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg"
                                    >
                                        <div class="max-h-60 overflow-y-auto">
                                            <div v-if="filteredUsers.length === 0" class="p-4 text-center text-gray-500">
                                                <p class="text-sm">Không tìm thấy nhân viên nào</p>
                                            </div>
                                            <button
                                                v-else
                                                v-for="user in filteredUsers"
                                                :key="user.id"
                                                @click="selectUser(user)"
                                                class="w-full border-b border-gray-100 p-3 text-left last:border-b-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                                            >
                                                <div class="flex items-center">
                                                    <span class="font-medium text-gray-900">{{ user.name }}</span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhập hàng</label>
                                    <input
                                        type="datetime-local"
                                        v-model="expectedImportDate"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Mã đơn đặt hàng nhập</label>
                                    <input
                                        type="text"
                                        v-model="batchCode"
                                        readonly
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Tham chiếu</label>
                                    <input
                                        type="text"
                                        v-model="invoiceCode"
                                        placeholder="Mã nhập tham chiếu"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 p-4">
                                <h2 class="text-lg font-semibold">Ghi chú</h2>
                            </div>
                            <div class="p-4">
                                <textarea
                                    v-model="notes"
                                    placeholder="Thêm ghi chú..."
                                    name="notes"
                                    class="min-h-[80px] w-full rounded-md border border-gray-300 p-2 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                ></textarea>
                            </div>
                        </div>

                        <button
                            class="mt-4 h-12 w-full rounded-md bg-blue-500 font-medium text-white hover:bg-blue-600"
                            @click="submitBatch"
                            v-if="selectedProducts.length > 0"
                            :disabled="isLoading"
                        >
                            {{ isLoading ? 'Đang lưu...' : 'Lưu đơn nhập hàng' }}
                        </button>
                        <button
                            class="mt-4 h-12 w-full rounded-md bg-gray-500 font-medium text-white hover:bg-gray-600"
                            @click="submitBatch"
                            v-else
                            disabled
                        >
                            Lưu đơn nhập hàng
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Price Modal -->
        <div v-if="isPriceModalOpen" class="fixed inset-0 z-50">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="mb-4 text-lg font-semibold">Điều chỉnh giá sản phẩm</h3>
                    <input type="number" min="0" v-model.number="editingPrice" class="mb-4 w-full rounded border border-gray-300 px-3 py-2" />
                    <div class="flex justify-end space-x-2">
                        <button @click="closePriceModal" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Hủy</button>
                        <button @click="savePrice" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Áp dụng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quantity Modal -->
        <div v-if="isQuantityModalOpen" class="fixed inset-0 z-50">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="mb-4 text-lg font-semibold">Điều chỉnh số lượng nhập</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Số lượng nhập</label>
                        <input
                            type="number"
                            min="0"
                            v-model.number="editingQuantity"
                            class="w-full rounded border border-gray-300 px-3 py-2"
                            @input="editingQuantity = Math.max(0, Number(($event.target as HTMLInputElement).value))"
                        />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Số lượng từ chối</label>
                        <input
                            type="number"
                            min="0"
                            v-model.number="editingRejectedQuantity"
                            class="w-full rounded border border-gray-300 px-3 py-2"
                            @input="editingRejectedQuantity = Math.max(0, Number(($event.target as HTMLInputElement).value))"
                        />
                    </div>
                    <div class="mb-4" v-if="editingRejectedQuantity > 0">
                        <label class="block text-sm font-medium text-gray-700">Lý do từ chối</label>
                        <textarea
                            v-model="editingRejectedReason"
                            placeholder="Nhập lý do từ chối..."
                            maxlength="255"
                            class="min-h-[80px] w-full rounded-md border border-gray-300 p-2 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button @click="closeQuantityModal" class="rounded bg-gray-200 px-4 py-2 hover:bg-gray-300">Hủy</button>
                        <button @click="saveQuantity" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Áp dụng</button>
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
                                class="h-10 w-32 rounded-l border border-gray-300 px-2 focus:outline-none"
                            />
                            <button class="h-10 w-[36px] rounded-r border border-l-0 border-gray-300 px-2" type="button">
                                <span v-if="modalDiscountType === 'amount'" class="text-gray-500">₫</span>
                                <span v-else class="text-gray-500">%</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="discountError" class="mb-2 text-sm text-red-600">{{ discountError }}</div>
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

<style scoped>
.group-hover\:block {
    transition: opacity 0.3s ease-in-out;
    opacity: 1;
}
</style>
