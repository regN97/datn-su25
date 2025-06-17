<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MultiSelectSearch from '@/components/MultiSelectSearch.vue';
import InputError from '@/components/InputError.vue';
import ProductUnitSelect from '@/components/ProductUnitSelect.vue';

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
    sku: string;
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

const page = usePage();
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
    sku: '',
    selected_supplier_ids: [],
    is_active: true,
    selling_price: 0,
    barcode: '',
    category_id: null,
    unit_id: null,
    description: '',
    min_stock_level: 0,
    max_stock_level: null,
    purchase_prices: {},
});

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
    { deep: true }
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

const goBack = () => {
    router.get('/admin/products');
};

const submitForm = () => {
    form.post('/admin/products', {
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
        <div class="flex flex-1 flex-col gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="border border-gray-200 dark:border-gray-600 flex-1 rounded-lg bg-white dark:bg-gray-900">
                <div class="container mx-auto p-6">
                    <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Thêm sản phẩm</h2>

                    <form class="space-y-6" @submit.prevent="submitForm" enctype="multipart/form-data">
                        <div class="grid grid-cols-4 gap-6">
                            <div class="col-span-3 space-y-6">
                                <div class="grid grid-cols-3 gap-6">
                                    <div>
                                        <label for="name"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Tên sản phẩm <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="name" v-model="form.name"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.name }"
                                            placeholder="Nhập tên sản phẩm" />
                                        <InputError :message="form.errors.name" />
                                    </div>
                                    <div>
                                        <label for="sku"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Mã SKU <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="sku" v-model="form.sku"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.sku }" placeholder="G7-MLH-011" />
                                        <InputError :message="form.errors.sku" />
                                    </div>
                                    <div>
                                        <label for="barcode"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Mã vạch <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="barcode" v-model="form.barcode"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.barcode }"
                                            placeholder="Nhập mã vạch" />
                                        <InputError :message="form.errors.barcode" />
                                    </div>

                                    <div>
                                        <label for="is_active"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Trạng thái <span class="text-red-500">*</span>
                                        </label>
                                        <select id="is_active" v-model="form.is_active"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.is_active }">
                                            <option :value="true">Hiển thị</option>
                                            <option :value="false">Ẩn</option>
                                        </select>
                                        <InputError :message="form.errors.is_active" />
                                    </div>
                                    <div>
                                        <label for="min_stock_level"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Tồn kho tối thiểu <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="min_stock_level" v-model.number="form.min_stock_level"
                                            min="0"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.min_stock_level }"
                                            placeholder="0" />
                                        <InputError :message="form.errors.min_stock_level" />
                                    </div>
                                    <div>
                                        <label for="max_stock_level"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Tồn kho tối đa
                                        </label>
                                        <input type="number" id="max_stock_level" v-model.number="form.max_stock_level"
                                            min="0"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
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
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Giá bán <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="selling_price" v-model.number="form.selling_price"
                                            min="0"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.selling_price }"
                                            placeholder="8000" />
                                        <InputError :message="form.errors.selling_price" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-6">
                                    <div class="col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Nhà cung cấp <span class="text-red-500">*</span>
                                        </label>
                                        <MultiSelectSearch v-model="form.selected_supplier_ids"
                                            :options="props.suppliers.map(s => ({ label: s.name, value: s.id }))"
                                            placeholder="Tìm nhà cung cấp" no-results-text="Không tìm thấy"
                                            no-options-text="Không có nhà cung cấp"
                                            class="w-full text-gray-800 dark:text-gray-200" />
                                        <InputError :message="form.errors.selected_supplier_ids" />
                                    </div>
                                    <div class="col-span-3">
                                        <div v-if="form.selected_supplier_ids.length > 0" class="space-y-3">
                                            <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">Giá
                                                nhập từ nhà cung cấp đã chọn:</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div v-for="supplierId in form.selected_supplier_ids" :key="supplierId"
                                                    class="flex flex-col md:flex-row md:items-center md:space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                                                    <label :for="`supplier_price_${supplierId}`"
                                                        class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Giá từ {{props.suppliers.find(s => s.id === supplierId)?.name}}
                                                    </label>
                                                    <div class="flex-1">
                                                        <input type="number" :id="`supplier_price_${supplierId}`"
                                                            v-model.number="form.purchase_prices[supplierId]" min="0"
                                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                            :class="{ 'border-red-500': form.errors[`purchase_prices.${supplierId}`] }"
                                                            placeholder="Nhập giá nhập" />
                                                        <InputError
                                                            :message="form.errors[`purchase_prices.${supplierId}`]" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label for="description"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Mô tả sản phẩm
                                    </label>
                                    <textarea id="description" v-model="form.description"
                                        class="w-full border border-gray-300 rounded-md h-28 p-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-y"
                                        :class="{ 'border-red-500': form.errors.description }" placeholder="Nhập mô tả"
                                        maxlength="5000"></textarea>
                                    <span class="text-gray-500 text-xs mt-1 block text-right">{{ descriptionCharCount
                                        }}/5000</span>
                                    <InputError :message="form.errors.description" />
                                </div>

                                
                            </div>

                            <div class="col-span-1 space-y-6">
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600"
                                    style="height: 250px;">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Ảnh sản phẩm <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center space-x-4 mb-2">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" v-model="imageInputType" value="url"
                                                name="image_input_type" class="form-radio h-4 w-4 text-blue-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">URL</span>
                                        </label>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" v-model="imageInputType" value="file"
                                                name="image_input_type" class="form-radio h-4 w-4 text-blue-600" />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tải lên</span>
                                        </label>
                                    </div>
                                    <InputError :message="form.errors.image_input_type" />
                                    <div v-if="imageInputType === 'url'">
                                        <input type="text" id="image_url" v-model="form.image_url"
                                            @input="form.image_file = null"
                                            class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.image_url }"
                                            placeholder="Nhập URL ảnh" />
                                        <span class="text-gray-500 text-xs mt-1 block">+ Nên thêm miền URL hoặc tải ảnh
                                            trực tiếp</span>
                                        <span class="text-gray-500 text-xs mt-1 block">(Dung lượng ảnh tối đa
                                            2MB)</span>
                                        <InputError :message="form.errors.image_url" />
                                    </div>
                                    <div v-else>
                                        <input type="file" id="image_file_input" @change="handleImageFileChange"
                                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-1 file:px-3 file:rounded file:border file:border-gray-300 file:bg-gray-50 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-100 dark:hover:file:bg-gray-600"
                                            :class="{ 'border-red-500': form.errors.image_file }"
                                            accept="image/jpeg,image/png,image/gif,image/webp" />
                                        <span v-if="form.image_file" class="text-gray-500 text-xs mt-1 block">
                                            {{ form.image_file.name }}
                                            <button type="button" @click="clearImageFile"
                                                class="text-red-500 hover:text-red-700 ml-2 text-xs">Xóa</button>
                                        </span>
                                        <span class="text-gray-500 text-xs mt-1 block">Tối đa 2MB (JPEG, PNG, GIF,
                                            WEBP)</span>
                                        <InputError :message="form.errors.image_file" />
                                    </div>
                                </div>

                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 "
                                    style="height: 200px;">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Danh mục</h4>
                                    <select id="category_id_sidebar" v-model="form.category_id"
                                        class="w-full border border-gray-300 rounded-md h-10 px-3 text-gray-800 dark:text-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500"
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
                                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        Quay lại
                                    </button>
                                    <button type="submit" :disabled="form.processing"
                                        class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 focus:outline-none focus:ring-1 focus:ring-blue-500">
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