<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import MultiSelectSearch from '@/components/MultiSelectSearch.vue';
import InputError from '@/components/InputError.vue';

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
    purchase_price: number;
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
    purchase_price: 0,
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
        if (fileInput) {
            fileInput.value = '';
        }
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
    if (fileInput) {
        fileInput.value = '';
    }
};

const goBack = () => {
    router.get(route('admin.products.index'));
};

const submitForm = () => {
    form.post(route('admin.products.store'), {
        onSuccess: () => {
            form.reset();
            imageInputType.value = 'url';
            clearImageFile();
            console.log('Sản phẩm đã được tạo thành công và form đã được reset.');
            router.visit(route('admin.products.index'));
        },
        onError: (errors) => {
            console.error('Lỗi validation từ server:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onFinish: () => {
        }
    });
};
</script>

<template>

    <Head title="Thêm Sản phẩm" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h2 class="text-2xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Thêm sản phẩm</h2>

                   

                    <form class="space-y-6" @submit.prevent="submitForm" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <div>
                                <label for="name" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">
                                    Tên sản phẩm <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" v-model="form.name"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.name }" placeholder="Nhập tên sản phẩm" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div>
                                <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Ảnh</label>
                                <div class="flex items-center space-x-4 mb-2">
                                    <label class="inline-flex items-center">
                                        <input type="radio" v-model="imageInputType" value="url" name="image_input_type"
                                            class="form-radio text-blue-600" />
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Đường dẫn ảnh</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" v-model="imageInputType" value="file"
                                            name="image_input_type" class="form-radio text-blue-600" />
                                        <span class="ml-2 text-gray-700 dark:text-gray-300">Tải ảnh lên</span>
                                    </label>
                                </div>
                                <InputError :message="form.errors.image_input_type" />
                                <div v-if="imageInputType === 'url'">
                                    <input type="text" id="image_url" name="image_url" v-model="form.image_url"
                                        @input="form.image_file = null"
                                        class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                        :class="{ 'border-red-500': form.errors.image_url }"
                                        placeholder="Nhập URL ảnh sản phẩm" />
                                    <InputError :message="form.errors.image_url" />
                                </div>

                                <div v-else-if="imageInputType === 'file'">
                                    <input type="file" id="image_file_input" name="image_file"
                                        @change="handleImageFileChange"
                                        class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        :class="{ 'border-red-500': form.errors.image_file }"
                                        accept="image/jpeg,image/png,image/gif,image/webp" />
                                    <span v-if="form.image_file" class="text-gray-500 text-sm mt-1 block">
                                        Đã chọn: {{ form.image_file.name }}
                                        <button type="button" @click="clearImageFile"
                                            class="text-red-500 hover:text-red-700 ml-2">Xóa</button>
                                    </span>
                                    <span class="text-gray-500 text-xs mt-1 block">Tối đa 2MB, định dạng JPEG, PNG, GIF,
                                        WEBP.</span>
                                    <InputError :message="form.errors.image_file" />
                                </div>
                            </div>

                            <div>
                                <label for="sku" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Mã SKU
                                    <span class="text-red-500">*</span></label>
                                <input type="text" id="sku" name="sku" v-model="form.sku"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.sku }" placeholder="Nhập mã SKU" />
                                <span class="text-gray-500 text-xs mt-1 block">
                                    Ví dụ: G7-MTHH-001
                                </span>
                                <InputError :message="form.errors.sku" />
                            </div>

                            <div>
                                <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Nhà cung cấp
                                    <span class="text-red-500">*</span></label>
                                <MultiSelectSearch v-model="form.selected_supplier_ids"
                                    :options="props.suppliers.map(s => ({ label: s.name, value: s.id }))"
                                    placeholder="Tìm kiếm nhà cung cấp"
                                    no-results-text="Không tìm thấy nhà cung cấp nào."
                                    no-options-text="Không có nhà cung cấp để lựa chọn." />
                                <span class="text-gray-500 text-xs mt-1 block">Có thể chọn nhiều nhà cung cấp</span>
                                <InputError :message="form.errors.selected_supplier_ids" />
                            </div>

                            <div>
                                <label for="barcode" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Mã
                                    vạch</label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="barcode" name="barcode" v-model="form.barcode"
                                        class="flex-1 rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                        :class="{ 'border-red-500': form.errors.barcode }" placeholder="Nhập mã vạch" />
                                </div>
                                <InputError :message="form.errors.barcode" />
                            </div>

                            <div>
                                <label for="is_active"
                                    class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Trạng thái <span
                                        class="text-red-500">*</span></label>
                                <select id="is_active" name="is_active" v-model="form.is_active"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.is_active }">
                                    <option :value="true">Hiển thị</option>
                                    <option :value="false">Ẩn/Bản nháp</option>
                                </select>
                                <InputError :message="form.errors.is_active" />
                            </div>

                            <div>
                                <label for="selling_price"
                                    class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Giá bán <span
                                        class="text-red-500">*</span></label>
                                <input type="number" id="selling_price" name="selling_price"
                                    v-model.number="form.selling_price" min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.selling_price }" placeholder="0" />
                                <InputError :message="form.errors.selling_price" />
                            </div>

                            <div>
                                <label for="purchase_price"
                                    class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Giá nhập <span
                                        class="text-red-500">*</span></label>
                                <input type="number" id="purchase_price" name="purchase_price"
                                    v-model.number="form.purchase_price" min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.purchase_price }" placeholder="0" />
                                <InputError :message="form.errors.purchase_price" />
                            </div>

                            <div>
                                <label for="category_id"
                                    class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Danh mục</label>
                                <select id="category_id" name="category_id" v-model="form.category_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.category_id }">
                                    <option :value="null">Chọn danh mục</option>
                                    <template v-for="category in props.categories" :key="category.id">
                                        <option :value="category.id">
                                            {{ category.name }}
                                        </option>
                                        <template v-if="category.children && category.children.length">
                                            <option v-for="child in category.children" :key="child.id"
                                                :value="child.id">
                                                &nbsp;&nbsp;&nbsp;— {{ child.name }}
                                            </option>
                                        </template>
                                    </template>
                                </select>
                                <InputError :message="form.errors.category_id" />
                            </div>

                            <div>
                                <label for="unit_id" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Đơn
                                    vị tính</label>
                                <select id="unit_id" name="unit_id" v-model="form.unit_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.unit_id }">
                                    <option :value="null">Chọn đơn vị tính</option>
                                    <option v-for="unit in props.product_units" :key="unit.id" :value="unit.id">
                                        {{ unit.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.unit_id" />
                            </div>

                            <div>
                                <label for="min_stock_level"
                                    class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Tồn kho tối thiểu
                                    <span class="text-red-500">*</span></label>
                                <input type="number" id="min_stock_level" name="min_stock_level"
                                    v-model.number="form.min_stock_level" min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.min_stock_level }" placeholder="0" />
                                <InputError :message="form.errors.min_stock_level" />
                            </div>

                            <div>
                                <label for="max_stock_level"
                                    class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Tồn kho tối
                                    đa</label>
                                <input type="number" id="max_stock_level" name="max_stock_level"
                                    v-model.number="form.max_stock_level" min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                    :class="{ 'border-red-500': form.errors.max_stock_level }" placeholder="0" />
                                <InputError :message="form.errors.max_stock_level" />
                            </div>
                        </div>

                        <div class="col-span-full">
                            <label for="description" class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Mô
                                tả</label>
                            <textarea id="description" name="description" v-model="form.description"
                                class="w-full rounded-md border-gray-300 shadow-sm p-2 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                :class="{ 'border-red-500': form.errors.description }" rows="3"
                                placeholder="Mô tả về sản phẩm" maxlength="5000"></textarea>
                            <span class="text-gray-500 text-sm mt-1 block text-right">
                                {{ descriptionCharCount }}/5000 ký tự
                            </span>
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="flex justify-end space-x-4 mt-8">
                            <button type="button" @click="goBack"
                                class="inline-flex items-center px-5 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 ease-in-out dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500">
                                🙏 Quay lại
                            </button>

                            <button type="submit"
                                class="inline-flex items-center px-5 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 ease-in-out dark:bg-blue-700 dark:hover:bg-blue-800"
                                :disabled="form.processing">
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