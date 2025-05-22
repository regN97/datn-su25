<script setup lang="ts">
// import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

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
    form.post('/admin/products', { forceFormData: true });
}
</script>

<template>

    <Head title="Thêm sản phẩm" />
    <AppLayout>
        <nav class="flex items-center text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li>
                    <Link href="/admin/products" class="hover:underline flex items-center gap-1">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Quản lí sản phẩm
                    </Link>
                </li>
                <li>
                    <span class="mx-2">/</span>
                </li>
                <li class="text-gray-900 font-medium">
                    Cập nhập sản phẩm
                </li>
            </ol>
        </nav>
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
                                <label class="font-semibold block mb-1">Nhà cung cấp</label>
                                <select v-model="form.suppliers" multiple class="w-full border rounded px-3 py-2">
                                    <option v-for="sup in props.suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}
                                    </option>
                                </select>
                                <div class="text-xs text-gray-400 mt-1">Có thể chọn nhiều nhà cung cấp</div>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Trạng thái *</label>
                                <select v-model="form.is_active" class="w-full border rounded px-3 py-2">
                                    <option :value="true">Hiển thị</option>
                                    <option :value="false">Ẩn</option>
                                </select>
                            </div>
                            <div>
                                <label class="font-semibold block mb-1">Mã vạch</label>
                                <div class="flex gap-2">
                                    <input v-model="form.barcode" type="text" class="flex-1 border rounded px-3 py-2"
                                        placeholder="Nhập hoặc tạo mã vạch" />
                                    <button type="button"
                                        class="bg-purple-500 text-white px-3 py-2 rounded hover:bg-purple-600 transition">Sửa
                                        mã vạch</button>
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
