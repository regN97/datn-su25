<script setup lang="ts">
// import { ref } from 'vue';
import { type BreadcrumbItem } from '@/types';
import MultiSelectSearch from '@/components/MultiSelectSearch.vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lí sản phẩm',
        href: '/admin/products',

    },
    {
        title: 'cập nhập sản phẩm',
        href: '/admin/products/edit',

    },
];
const props = defineProps<{
    product: any,
    categories: { id: number; name: string }[],
    units: { id: number; name: string }[],
    suppliers: { id: number; name: string }[],
    productSuppliers: number[]
}>();

const form = useForm({
    name: props.product.name ?? '',
    sku: props.product.sku ?? '',
    barcode: props.product.barcode ?? '',
    description: props.product.description ?? '',
    category_id: props.product.category_id ?? null,
    unit_id: props.product.unit_id ?? null,
    purchase_price: props.product.purchase_price ?? 0,
    selling_price: props.product.selling_price ?? 0,
    min_stock_level: props.product.min_stock_level ?? 0,
    max_stock_level: props.product.max_stock_level ?? 0,
    is_active: props.product.is_active ?? true,
    suppliers: Array.isArray(props.productSuppliers) ? [...props.productSuppliers] : [],
    image_url: props.product.image_url ?? '',
    image_file: null as File | null,
    image_type: 'url', // hoặc props.product.image_type nếu có
});


function submit() {
    form.transform(data => {
        const payload: any = {
            ...data,
            selected_supplier_ids: data.suppliers,
            is_active: data.is_active ? 1 : 0,
            _method: 'PUT',
        };
        // Nếu chọn upload thì bỏ image_url, nếu chọn url thì bỏ image_file
        if (data.image_type === 'upload') {
            delete payload.image_url;
        } else {
            delete payload.image_file;
        }
        return payload;
    }).post(`/admin/products/${props.product.id}`, {
        forceFormData: true,
    });
}


</script>

<template>

    <Head title="Thêm sản phẩm" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <div class="container mx-auto max-w-5xl mt-8">
            <div class="bg-white rounded-xl shadow p-8">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-bold">Cập nhập sản phẩm</h1>
                    <Link href="/admin/products" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">
                    Quay lại</Link>
                </div>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Cột trái -->
                        <div class="space-y-4">
                            <div>
                                <label class="font-semibold block mb-1">Tên sản phẩm *</label>
                                <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2"
                                    placeholder="Nhập tên sản phẩm" />
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Mã SKU *</label>
                                <input v-model="form.sku" type="text" class="w-full border rounded px-3 py-2"
                                    placeholder="Nhập mã SKU" />
                                <div class="text-xs text-gray-400 mt-1">Ví dụ: G7-MTHH-001</div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="font-semibold block mb-1">Giá nhập *</label>
                                    <input v-model="form.purchase_price" type="number" min="0"
                                        class="w-full border rounded px-3 py-2" />
                                </div>
                                <div class="flex-1">
                                    <label class="font-semibold block mb-1">Giá bán *</label>
                                    <input v-model="form.selling_price" type="number" min="0"
                                        class="w-full border rounded px-3 py-2" />
                                </div>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Danh mục</label>
                                <select v-model="form.category_id" class="w-full border rounded px-3 py-2">
                                    <option :value="null">Chọn danh mục</option>
                                    <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="font-semibold block mb-1">Tồn kho tối thiểu</label>
                                    <input v-model="form.min_stock_level" type="number" min="0"
                                        class="w-full border rounded px-3 py-2" />
                                </div>
                                <div class="flex-1">
                                    <label class="font-semibold block mb-1">Tồn kho tối đa</label>
                                    <input v-model="form.max_stock_level" type="number" min="0"
                                        class="w-full border rounded px-3 py-2" />
                                </div>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Mô tả</label>
                                <textarea v-model="form.description" class="w-full border rounded px-3 py-2" rows="3"
                                    placeholder="Mô tả về sản phẩm"></textarea>
                                <div class="text-xs text-gray-400 mt-1 text-right">{{ form.description.length }}/5000 ký
                                    tự</div>
                            </div>
                        </div>
                        <!-- Cột phải -->
                        <div class="space-y-4">
                            <div>
                                <label class="font-semibold block mb-1">Ảnh</label>
                                <div class="flex items-center gap-4 mb-2">
                                    <label class="flex items-center gap-1">
                                        <input type="radio" value="url" v-model="form.image_type" />
                                        Đường dẫn ảnh
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" value="upload" v-model="form.image_type" />
                                        Tải ảnh lên
                                    </label>
                                </div>
                                <div v-if="form.image_type === 'url'">
                                    <input v-model="form.image_url" type="text" class="w-full border rounded px-3 py-2"
                                        placeholder="Nhập URL ảnh sản phẩm" />
                                </div>
                                <div v-else>
                                    <input type="file" @change="e => form.image_file = e.target.files[0]"
                                        class="w-full border rounded px-3 py-2" />
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Nhà cung cấp
                                    <span class="text-red-500">*</span></label>
                                <MultiSelectSearch v-model="form.suppliers"
                                    :options="props.suppliers.map(s => ({ label: s.name, value: s.id }))"
                                    placeholder="Tìm kiếm nhà cung cấp"
                                    no-results-text="Không tìm thấy nhà cung cấp nào."
                                    no-options-text="Không có nhà cung cấp để lựa chọn." />
                                <span class="text-gray-500 text-xs mt-1 block">Có thể chọn nhiều nhà cung cấp</span>
                                <InputError :message="form.errors.selected_supplier_ids" />
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Trạng thái *</label>
                                <select v-model="form.is_active" class="w-full border rounded px-3 py-2">
                                    <option :value="1">Hiển thị</option>
                                    <option :value="0">Ẩn</option>
                                </select>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Mã vạch</label>
                                <div class="flex gap-2">
                                    <input v-model="form.barcode" type="text" class="flex-1 border rounded px-3 py-2"
                                        placeholder="Nhập hoặc tạo mã vạch" />

                                </div>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Đơn vị tính</label>
                                <select v-model="form.unit_id" class="w-full border rounded px-3 py-2">
                                    <option :value="null">Chọn đơn vị tính</option>
                                    <option v-for="unit in props.units" :key="unit.id" :value="unit.id">{{ unit.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-8">
                        <Link href="/admin/products"
                            class="px-6 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Quay lại</Link>
                        <button type="submit"
                            class="px-8 py-2 rounded bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold hover:from-purple-600 hover:to-pink-600 transition">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
