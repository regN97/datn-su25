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
    short_name?: string;
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
}

const page = usePage();
const imageInputType = ref<'url' | 'file'>('url');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lí sản phẩm',
        href: '/admin/products',
    },
    {
        title: 'Thêm sản phẩm',
        href: '/admin/products/create',
    },
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
            imageInputType.value = 'url';
            clearImageFile();
            console.log('Sản phẩm đã được tạo thành công và form đã được reset.');
            router.visit('/admin/products');
        },
        onError: (errors) => {
            console.error('Lỗi validation từ server:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>

<template>
    <Head title="Thêm Sản phẩm" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h2 class="mb-6 text-2xl font-semibold text-gray-800 dark:text-gray-200">Thêm sản phẩm</h2>

                    <form class="space-y-6" @submit.prevent="submitForm" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1 block text-sm font-semibold text-gray-700">
                                    Tên sản phẩm <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    v-model="form.name"
                                    class="h-14 w-full rounded-md border border-gray-300 px-3 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="Nhập tên sản phẩm vào đây"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Ảnh <span class="text-red-500">*</span></label>
                                <div class="mb-2 flex items-center space-x-4">
                                    <label class="inline-flex cursor-pointer items-center">
                                        <input
                                            type="radio"
                                            v-model="imageInputType"
                                            value="url"
                                            name="image_input_type"
                                            class="form-radio h-4 w-4 border-gray-300 text-blue-600 checked:bg-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Đường dẫn ảnh</span>
                                    </label>
                                    <label class="inline-flex cursor-pointer items-center">
                                        <input
                                            type="radio"
                                            v-model="imageInputType"
                                            value="file"
                                            name="image_input_type"
                                            class="form-radio h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span class="ml-2 text-sm text-gray-700">Tải ảnh lên</span>
                                    </label>
                                </div>
                                <InputError :message="form.errors.image_input_type" class="mb-1" />
                                <div v-if="imageInputType === 'url'">
                                    <input
                                        type="text"
                                        id="image_url"
                                        name="image_url"
                                        v-model="form.image_url"
                                        @input="form.image_file = null"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.image_url }"
                                        placeholder="Nhập link ảnh https://"
                                    />
                                    <InputError :message="form.errors.image_url" />
                                </div>
                                <div v-else-if="imageInputType === 'file'">
                                    <input
                                        type="file"
                                        id="image_file_input"
                                        name="image_file"
                                        @change="handleImageFileChange"
                                        class="block w-full cursor-pointer text-sm text-gray-500 file:mr-4 file:rounded-md file:border file:border-gray-300 file:bg-gray-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-100"
                                        :class="{ 'border-red-500': form.errors.image_file }"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                    />
                                    <span v-if="form.image_file" class="mt-1 block text-xs text-gray-500">
                                        Đã chọn: {{ form.image_file.name }}
                                        <button
                                            type="button"
                                            @click="clearImageFile"
                                            class="ml-2 text-xs font-medium text-red-500 hover:text-red-700"
                                        >
                                            Xóa
                                        </button>
                                    </span>
                                    <span class="mt-1 block text-xs text-gray-500">Tối đa 2MB, định dạng JPEG, PNG, GIF, WEBP.</span>
                                    <InputError :message="form.errors.image_file" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                                <div>
                                    <label for="sku" class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Mã SKU <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="text"
                                        id="sku"
                                        name="sku"
                                        v-model="form.sku"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.sku }"
                                        placeholder="G7-MLH-011"
                                    />
                                    <span class="mt-1 block text-xs text-gray-500">Ví dụ: G7-MTHH-001</span>
                                    <InputError :message="form.errors.sku" />
                                </div>
                                <div>
                                    <label for="barcode" class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Mã vạch <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="text"
                                        id="barcode"
                                        name="barcode"
                                        v-model="form.barcode"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.barcode }"
                                        placeholder="Vui lòng nhập mã vạch"
                                    />
                                    <InputError :message="form.errors.barcode" />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-semibold text-gray-700">Nhà cung cấp <span class="text-red-500">*</span></label>
                                <MultiSelectSearch
                                    v-model="form.selected_supplier_ids"
                                    :options="props.suppliers.map((s) => ({ label: s.name, value: s.id }))"
                                    placeholder="Tìm kiếm nhà cung cấp"
                                    no-results-text="Không tìm thấy nhà cung cấp nào."
                                    no-options-text="Không có nhà cung cấp để lựa chọn."
                                    class="w-full text-gray-800"
                                />
                                <span class="mt-1 block text-xs text-gray-500">Có thể chọn nhiều nhà cung cấp</span>
                                <InputError :message="form.errors.selected_supplier_ids" />
                            </div>

                            <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-1">
                                <div>
                                    <label for="selling_price" class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Giá bán <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="number"
                                        id="selling_price"
                                        name="selling_price"
                                        v-model.number="form.selling_price"
                                        min="0"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.selling_price }"
                                        placeholder="8000"
                                    />
                                    <InputError :message="form.errors.selling_price" />
                                </div>
                            </div>

                            <div>
                                <label for="is_active" class="mb-1 block text-sm font-semibold text-gray-700"
                                    >Trạng thái <span class="text-red-500">*</span></label
                                >
                                <select
                                    id="is_active"
                                    name="is_active"
                                    v-model="form.is_active"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.is_active }"
                                >
                                    <option :value="true">Hiển thị</option>
                                    <option :value="false">Ẩn/Bản nháp</option>
                                </select>
                                <InputError :message="form.errors.is_active" />
                            </div>

                            <div>
                                <label for="category_id" class="mb-1 block text-sm font-semibold text-gray-700"
                                    >Danh mục <span class="text-red-500">*</span></label
                                >
                                <select
                                    id="category_id"
                                    name="category_id"
                                    v-model="form.category_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.category_id }"
                                >
                                    <option :value="null">Chọn danh mục</option>
                                    <template v-for="category in props.categories" :key="category.id">
                                        <option :value="category.id">{{ category.name }}</option>
                                        <template v-if="category.children && category.children.length">
                                            <option v-for="child in category.children" :key="child.id" :value="child.id" class="text-sm">
                                                   — {{ child.name }}
                                            </option>
                                        </template>
                                    </template>
                                </select>
                                <InputError :message="form.errors.category_id" />
                            </div>

                            <ProductUnitSelect
                                v-model="form.unit_id"
                                :units="productUnits"
                                :error="form.errors.unit_id"
                                @update:units="productUnits = $event"
                            />

                            <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                                <div>
                                    <label for="min_stock_level" class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Tồn kho tối thiểu <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="number"
                                        id="min_stock_level"
                                        name="min_stock_level"
                                        v-model.number="form.min_stock_level"
                                        min="0"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.min_stock_level }"
                                        placeholder="30"
                                    />
                                    <InputError :message="form.errors.min_stock_level" />
                                </div>

                                <div>
                                    <label for="max_stock_level" class="mb-1 block text-sm font-semibold text-gray-700"
                                        >Tồn kho tối đa <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        type="number"
                                        id="max_stock_level"
                                        name="max_stock_level"
                                        v-model.number="form.max_stock_level"
                                        min="0"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.max_stock_level }"
                                        placeholder="0"
                                    />
                                    <InputError :message="form.errors.max_stock_level" />
                                </div>
                            </div>

                            <div>
                                <label for="description" class="mb-2 block font-medium text-gray-700 dark:text-gray-300">Mô tả</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    v-model="form.description"
                                    class="w-full rounded-md border-gray-300 bg-white p-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.description }"
                                    rows="3"
                                    placeholder="Mô tả về sản phẩm"
                                    maxlength="5000"
                                ></textarea>
                                <span class="mt-1 block text-right text-sm text-gray-500"> {{ descriptionCharCount }}/5000 ký tự </span>
                                <InputError :message="form.errors.description" />
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-4">
                            <button
                                type="button"
                                @click="goBack"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-200 ease-in-out hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500"
                            >
                                🙏 Quay lại
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:bg-blue-700 dark:hover:bg-blue-800"
                                :disabled="form.processing"
                            >
                                <span>💾 Lưu</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="scss" scoped></style>
