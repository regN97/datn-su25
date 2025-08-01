<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import MultiSelectSearch from '@/components/MultiSelectSearch.vue';
import ProductUnitSelect from '@/components/ProductUnitSelect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Supplier {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    parent_id: number | null;
    children?: Category[];
}

interface ProductUnit {
    id: number;
    name: string;
}

interface ProductForm {
    name: string;
    image_url: string | null;
    image_file: File | null;
    image_input_type: 'url' | 'file';
    selected_supplier_ids: number[];
    is_active: boolean;
    selling_price: number;
    barcode: string;
    category_id: number | null;
    unit_id: number | null;
    description: string;
    min_stock_level: number;
    max_stock_level: number | null;
    purchase_prices: { [key: number]: number | null };
}

const imageInputType = ref<'url' | 'file'>('url');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quản lí sản phẩm', href: '/admin/products' },
    { title: 'Thêm sản phẩm', href: '/admin/products/create' },
];

const form = useForm<ProductForm>({
    name: '',
    image_url: null,
    image_file: null,
    image_input_type: 'url',
    selected_supplier_ids: [],
    is_active: true,
    selling_price: 0,
    barcode: '',
    category_id: null,
    unit_id: null,
    description: '',
    min_stock_level: 20,
    max_stock_level: 200,
    purchase_prices: {},
});

// Validate selling price against purchase prices
watch(
    () => [form.selling_price, form.purchase_prices],
    ([newSellingPrice, newPurchasePrices]) => {
        if (typeof newSellingPrice !== 'number') return; // Kiểm tra kiểu
        const purchasePrices = Object.values(newPurchasePrices).filter((price): price is number => price !== null);
        if (purchasePrices.length > 0 && newSellingPrice > 0) {
            const minPurchasePrice = Math.min(...purchasePrices);
            if (newSellingPrice < minPurchasePrice) {
                form.errors.selling_price = `Giá bán phải lớn hơn hoặc bằng giá nhập thấp nhất (${minPurchasePrice})`;
            } else {
                delete form.errors.selling_price;
            }
        }
    },
    { deep: true }
);

// Validate error purchase prices
const getPurchasePriceErrorMessage = (supplierId: number): string | undefined => {
    const key = `purchase_prices.${supplierId}`;
    const error = (form.errors as Record<string, string>)[key];
    if (!error) return undefined;

    // Nếu muốn đơn giản hóa tất cả về "Trường giá nhập là bắt buộc."
    if (error.includes('purchase_prices')) {
        return 'Trường giá nhập là bắt buộc.';
    }

    return error;
};

watch(imageInputType, (newType) => {
    form.image_input_type = newType;
    if (newType === 'url') {
        form.image_file = null;
        const fileInput = document.getElementById('image_file_input') as HTMLInputElement;
        if (fileInput) fileInput.value = '';
    } else {
        form.image_url = null;
    }
});

watch(
    () => form.selected_supplier_ids,
    (newSelectedIds) => {
        const newPrices: { [key: number]: number | null } = {};
        newSelectedIds.forEach((id) => {
            newPrices[id] = form.purchase_prices[id] !== undefined ? form.purchase_prices[id] : null;
        });
        form.purchase_prices = newPrices;
    },
    { deep: true },
);

const descriptionCharCount = computed(() => {
    return form.description ? form.description.length : 0;
});

const props = defineProps<{
    suppliers: Supplier[];
    categories: Category[];
    product_units: ProductUnit[];
}>();

const productUnits = ref<ProductUnit[]>(props.product_units);

const handleImageFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.image_file = target.files[0];
        form.image_url = null;
    } else {
        form.image_file = null;
    }
};

const clearImageFile = () => {
    form.image_file = null;
    const fileInput = document.getElementById('image_file_input') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
};

function useCurrencyInput(valueGetter: () => number | null, valueSetter: (val: number | null) => void) {
    const display = ref('');

    const formatCurrency = (value: number | null): string => {
        if (value === null || isNaN(value)) return '';
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            minimumFractionDigits: 0,
        }).format(value);
    };

    watch(
        valueGetter,
        (val) => {
            display.value = formatCurrency(val);
        },
        { immediate: true }
    );

    const onInput = (event: Event) => {
        const input = event.target as HTMLInputElement;
        const raw = input.value.replace(/[^\d]/g, '');
        const parsed = parseInt(raw, 10);
        valueSetter(isNaN(parsed) ? null : parsed);
    };

    return {
        display,
        onInput,
    };
}

const { display: sellingPriceDisplay, onInput: onSellingPriceInput } = useCurrencyInput(
    () => form.selling_price,
    (val) => (form.selling_price = val ?? 0)
);

const getPurchasePriceHandlers = (supplierId: number) => {
    return useCurrencyInput(
        () => form.purchase_prices[supplierId] ?? 0,
        (val) => (form.purchase_prices[supplierId] = val)
    );
};

const goBack = () => {
    router.get('/admin/products');
};

const submitForm = () => {
    form.post('/admin/products', {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            form.purchase_prices = {};
            imageInputType.value = 'url';
            clearImageFile();
            router.visit('/admin/products');
        },
        onError: (errors) => {
            console.error('Lỗi validation:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>

    <Head title="Thêm Sản phẩm" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
            <div class="flex-1 rounded-lg border border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-900">
                <div class="container mx-auto p-6">
                    <h2 class="mb-6 text-xl font-semibold text-gray-800 dark:text-gray-200">Thêm sản phẩm</h2>

                    <form class="space-y-6" @submit.prevent="submitForm" enctype="multipart/form-data">
                        <div class="grid grid-cols-4 gap-6">
                            <div class="col-span-3 space-y-6">
                                <div class="grid grid-cols-3 gap-6">
                                    <div>
                                        <label for="name"
                                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Tên sản phẩm <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="name" v-model="form.name"
                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ 'border-red-500': form.errors.name }"
                                            placeholder="Nhập tên sản phẩm" />
                                        <InputError :message="form.errors.name" />
                                    </div>
                                    <div>
                                        <label for="barcode"
                                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Mã vạch
                                        </label>
                                        <input type="text" id="barcode" v-model="form.barcode"
                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ 'border-red-500': form.errors.barcode }"
                                            placeholder="Nhập mã vạch" />
                                        <InputError :message="form.errors.barcode" />
                                    </div>

                                    <div>
                                        <label for="is_active"
                                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Trạng thái <span class="text-red-500">*</span>
                                        </label>
                                        <select id="is_active" v-model="form.is_active"
                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ 'border-red-500': form.errors.is_active }">
                                            <option :value="true">Hiển thị</option>
                                            <option :value="false">Ẩn</option>
                                        </select>
                                        <InputError :message="form.errors.is_active" />
                                    </div>
                                    <div>
                                        <label for="min_stock_level"
                                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Tồn kho tối thiểu <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="min_stock_level" v-model.number="form.min_stock_level"
                                            min="0"
                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ 'border-red-500': form.errors.min_stock_level }"
                                            placeholder="0" />
                                        <InputError :message="form.errors.min_stock_level" />
                                    </div>
                                    <div>
                                        <label for="max_stock_level"
                                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Tồn kho tối đa
                                        </label>
                                        <input type="number" id="max_stock_level" v-model.number="form.max_stock_level"
                                            min="0"
                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ 'border-red-500': form.errors.max_stock_level }"
                                            placeholder="Không giới hạn" />
                                        <InputError :message="form.errors.max_stock_level" />
                                    </div>

                                    <div class="col-span-2">
                                        <ProductUnitSelect v-model="form.unit_id" :units="productUnits"
                                            :error="form.errors.unit_id" @update:units="productUnits = $event" />
                                    </div>
                                    <div>
                                        <label for="selling_price"
                                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Giá bán <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="selling_price" :value="sellingPriceDisplay"
                                            @input="onSellingPriceInput"
                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ 'border-red-500': form.errors.selling_price }"
                                            placeholder="8.000 ₫" />
                                        <InputError :message="form.errors.selling_price" />

                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-6">
                                    <div class="col-span-3">
                                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Nhà cung cấp <span class="text-red-500">*</span>
                                        </label>
                                        <MultiSelectSearch v-model="form.selected_supplier_ids"
                                            :options="props.suppliers.map((s) => ({ label: s.name, value: s.id }))"
                                            placeholder="Tìm nhà cung cấp" no-results-text="Không tìm thấy"
                                            no-options-text="Không có nhà cung cấp"
                                            class="w-full text-gray-800 dark:text-gray-200" />
                                        <InputError :message="form.errors.selected_supplier_ids" />
                                    </div>
                                    <div class="col-span-3">
                                        <div v-if="form.selected_supplier_ids.length > 0" class="space-y-3">
                                            <h4 class="text-md mb-2 font-medium text-gray-800 dark:text-gray-200">
                                                Giá nhập từ nhà cung cấp đã chọn:
                                            </h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div v-for="supplierId in form.selected_supplier_ids" :key="supplierId"
                                                    class="flex flex-col rounded border border-gray-200 bg-gray-50 p-3 md:flex-row md:items-center md:space-x-3 dark:border-gray-600 dark:bg-gray-700">
                                                    <label :for="`supplier_price_${supplierId}`"
                                                        class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Giá từ {{props.suppliers.find((s) => s.id === supplierId)?.name
                                                        }}
                                                    </label>
                                                    <div class="flex-1">
                                                        <input type="number" :id="`supplier_price_${supplierId}`"
                                                            v-model.number="form.purchase_prices[supplierId]" min="0"
                                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                                            placeholder="Nhập giá nhập" />
                                                        <InputError
                                                            :message="getPurchasePriceErrorMessage(supplierId)" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label for="description"
                                        class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Mô tả sản phẩm
                                    </label>
                                    <textarea id="description" v-model="form.description"
                                        class="h-28 w-full resize-y rounded-md border border-gray-300 p-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                        :class="{ 'border-red-500': form.errors.description }" placeholder="Nhập mô tả"
                                        maxlength="5000"></textarea>
                                    <span class="mt-1 block text-right text-xs text-gray-500">{{ descriptionCharCount
                                    }}/5000</span>
                                    <InputError :message="form.errors.description" />
                                </div>
                            </div>

                            <div class="col-span-1 space-y-6">
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700"
                                    style="height: 250px">
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Ảnh sản phẩm <span class="text-red-500">*</span>
                                    </label>
                                    <div class="mb-2 flex items-center space-x-4">
                                        <label class="inline-flex cursor-pointer items-center">
                                            <input type="radio" v-model="imageInputType" value="url"
                                                name="image_input_type" class="form-radio h-4 w-4 text-blue-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">URL</span>
                                        </label>
                                        <label class="inline-flex cursor-pointer items-center">
                                            <input type="radio" v-model="imageInputType" value="file"
                                                name="image_input_type" class="form-radio h-4 w-4 text-blue-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tải lên</span>
                                        </label>
                                    </div>
                                    <InputError :message="form.errors.image_input_type" />
                                    <div v-if="imageInputType === 'url'">
                                        <input type="text" id="image_url" v-model="form.image_url"
                                            @input="form.image_file = null"
                                            class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                            :class="{ 'border-red-500': form.errors.image_url }"
                                            placeholder="Nhập URL ảnh" />
                                        <span class="mt-1 block text-xs text-gray-500">+ Nên thêm miền URL hoặc tải ảnh
                                            trực tiếp</span>
                                        <span class="mt-1 block text-xs text-gray-500">(Dung lượng ảnh tối đa
                                            2MB)</span>
                                        <InputError :message="form.errors.image_url" />
                                    </div>
                                    <div v-else>
                                        <input type="file" id="image_file_input" @change="handleImageFileChange"
                                            class="block w-full text-sm text-gray-500 file:mr-3 file:rounded file:border file:border-gray-300 file:bg-gray-50 file:px-3 file:py-1 file:text-gray-700 hover:file:bg-gray-100 dark:file:bg-gray-700 dark:file:text-gray-200 dark:hover:file:bg-gray-600"
                                            :class="{ 'border-red-500': form.errors.image_file }"
                                            accept="image/jpeg,image/png,image/gif,image/webp" />
                                        <span v-if="form.image_file" class="mt-1 block text-xs text-gray-500">
                                            {{ form.image_file.name }}
                                            <button type="button" @click="clearImageFile"
                                                class="ml-2 text-xs text-red-500 hover:text-red-700">
                                                Xóa
                                            </button>
                                        </span>
                                        <span class="mt-1 block text-xs text-gray-500">Tối đa 2MB (JPEG, PNG, GIF,
                                            WEBP)</span>
                                        <InputError :message="form.errors.image_file" />
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-700"
                                    style="height: 200px">
                                    <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Danh mục</h4>
                                    <select id="category_id_sidebar" v-model="form.category_id"
                                        class="h-10 w-full rounded-md border border-gray-300 px-3 text-gray-800 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-gray-700 dark:text-gray-200"
                                        :class="{ 'border-red-500': form.errors.category_id }">
                                           
                                        <option :value="null">Chọn danh mục</option>
                                        <template v-for="category in props.categories" :key="category.id">
                                            <option :value="category.id">{{ category.name }}</option>
                                            <template v-if="category.children && category.children.length">
                                                <option v-for="child in category.children" :key="child.id"
                                                    :value="child.id" class="text-sm">
                                                    — {{ child.name }}
                                                </option>
                                            </template>
                                        </template>
                                    </select>
                                    <InputError :message="form.errors.category_id" />
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-6">
                            <button type="button" @click="goBack"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                Quay lại
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-1 focus:ring-blue-500 focus:outline-none dark:bg-blue-700 dark:hover:bg-blue-800">
                                Lưu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="scss" scoped></style>
