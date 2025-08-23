<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import MultiSelectSearch from '@/components/MultiSelectSearch.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

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
    product: any;
    categories: { id: number; name: string }[];
    units: { id: number; name: string }[];
    suppliers: { id: number; name: string }[];
    productSuppliers: number[];
}>();

const form = useForm({
    name: props.product.name ?? '',
    sku: props.product.sku ?? '',
    barcode: props.product.barcode ?? '',
    description: props.product.description ?? '',
    category_id: props.product.category_id ?? null,
    unit_id: props.product.unit_id ?? null,
    selling_price: props.product.selling_price ?? 0,
    min_stock_level: props.product.min_stock_level ?? 0,
    max_stock_level: props.product.max_stock_level ?? 0,
    is_active: typeof props.product.is_active === 'boolean'
        ? Number(props.product.is_active)
        : 1,
    selected_supplier_ids: Array.isArray(props.productSuppliers) ? [...props.productSuppliers] : [],
    purchase_prices: {}, // Sẽ được populate từ suppliers
    image_url: props.product.image_url ?? '',
    image_file: null as File | null,
    image_input_type: 'url',
});

// Populate purchase_prices từ suppliers
const populatePurchasePrices = () => {
    if (props.product.suppliers && Array.isArray(props.product.suppliers)) {
        const prices: { [key: number]: number | null } = {};
        props.product.suppliers.forEach((supplier: any) => {
            if (supplier.pivot && supplier.pivot.purchase_price) {
                prices[supplier.id] = supplier.pivot.purchase_price;
            }
        });
        form.purchase_prices = prices;
    }
};

// Gọi function khi component mount
populatePurchasePrices();

function submit() {
    form.transform((data) => {
        const payload: any = {
            ...data,
            is_active: data.is_active ? 1 : 0,
            _method: 'PUT',
        };
        
        // Nếu chọn upload thì bỏ image_url, nếu chọn url thì bỏ image_file
        if (data.image_input_type === 'file') {
            delete payload.image_url;
        } else {
            delete payload.image_file;
        }
        
        return payload;
    }).post(`/admin/products/${props.product.id}`, {
        forceFormData: true,
        onError: (errors) => {
            console.error('Lỗi validation:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
}

const URL = window.URL || window.webkitURL;

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.image_file = file;
    }
};

const displaySellingPrice = computed({
    get() {
        return formatCurrency(form.selling_price);
    },
    set(value: string) {
        // Loại bỏ các ký tự không phải số để cập nhật lại vào form
        const number = Number(value.replace(/[^\d]/g, ''));
        form.selling_price = isNaN(number) ? 0 : number;
    },
});

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
}

function goBack() {
    router.visit('/admin/products');
}

</script>

<template>

    <Head title="Cập nhật sản phẩm" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto mt-8 max-w-5xl">
            <div class="rounded-xl bg-white p-8 shadow">
                <div class="flex items-center gap-3">
                    <button @click="goBack"
                        class="h-9 w-9 rounded border border-gray-300 bg-white text-gray-700 hover:border-gray-400">
                        ←
                    </button>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">{{ props.product.name }}</h1>
                    </div>
                </div>
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <!-- Cột trái -->
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 mt-5 block font-semibold">Tên sản phẩm</label>
                                <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2"
                                    placeholder="Nhập tên sản phẩm" />
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="mb-1 block font-semibold">Giá bán</label>
                                    <input v-model="displaySellingPrice" type="text"
                                        class="w-full rounded border px-3 py-2" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold">Danh mục</label>
                                <select v-model="form.category_id" class="w-full rounded border px-3 py-2">
                                    <option :value="null">Chọn danh mục</option>
                                    <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">{{ cat.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label class="mb-1 block font-semibold">Tồn kho tối thiểu</label>
                                    <input v-model="form.min_stock_level" type="number" min="0"
                                        class="w-full rounded border px-3 py-2" />
                                </div>
                                <div class="flex-1">
                                    <label class="mb-1 block font-semibold">Tồn kho tối đa</label>
                                    <input v-model="form.max_stock_level" type="number" min="0"
                                        class="w-full rounded border px-3 py-2" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold">Mô tả</label>
                                <textarea v-model="form.description" class="w-full rounded border px-3 py-2" rows="3"
                                    placeholder="Mô tả về sản phẩm"></textarea>
                                <div class="mt-1 text-right text-xs text-gray-400">{{ form.description.length }}/5000 ký
                                    tự</div>
                            </div>
                        </div>
                        <!-- Cột phải -->
                        <div class="space-y-4">
                            <div class="mt-4">
                                <label class="mb-1 block font-semibold">Xem trước ảnh</label>
                                <div v-if="form.image_input_type === 'url' && form.image_url">
                                    <img :src="form.image_url" alt="Preview"
                                        class="h-[160px] w-[160px] object-contain" />
                                </div>
                                <div v-else-if="form.image_input_type === 'file' && form.image_file">
                                    <img :src="URL.createObjectURL(form.image_file)" alt="Preview"
                                        class="h-[160px] w-[160px] object-contain" />
                                </div>
                                <div v-else-if="props.product.image_url">
                                    <img :src="props.product.image_url" alt="Ảnh hiện tại"
                                        class="h-[160px] w-[160px] object-contain" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold">Ảnh</label>
                                <div class="mb-2 flex items-center gap-4">
                                    <label class="flex items-center gap-1">
                                        <input type="radio" value="url" v-model="form.image_input_type" />
                                        Đường dẫn ảnh
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" value="file" v-model="form.image_input_type" />
                                        Tải ảnh lên
                                    </label>
                                </div>
                                <div v-if="form.image_input_type === 'url'">
                                    <input v-model="form.image_url" type="text" class="w-full rounded border px-3 py-2"
                                        placeholder="Nhập URL ảnh sản phẩm" />
                                </div>
                                <div v-else>
                                    <input type="file" @change="handleFileChange" class="w-full rounded border px-3 py-2"/>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block font-medium text-gray-700 dark:text-gray-300">Nhà cung
                                    cấp</label>
                                <MultiSelectSearch v-model="form.selected_supplier_ids"
                                    :options="props.suppliers.map((s) => ({ label: s.name, value: s.id }))"
                                    placeholder="Tìm kiếm nhà cung cấp"
                                    no-results-text="Không tìm thấy nhà cung cấp nào."
                                    no-options-text="Không có nhà cung cấp để lựa chọn." />
                                <span class="mt-1 block text-xs text-gray-500">Có thể chọn nhiều nhà cung cấp</span>
                                <InputError :message="form.errors.selected_supplier_ids" />
                                
                                <!-- Hiển thị giá nhập cho từng supplier -->
                                <div v-if="form.selected_supplier_ids.length > 0" class="mt-3 space-y-2">
                                    <div v-for="supplierId in form.selected_supplier_ids" :key="supplierId" class="flex items-center gap-2">
                                        <span class="text-sm text-gray-600">
                                            {{ props.suppliers.find(s => s.id === supplierId)?.name }}:
                                        </span>
                                        <input 
                                            v-model.number="form.purchase_prices[supplierId]" 
                                            type="number" 
                                            min="0" 
                                            step="1000"
                                            class="flex-1 rounded border px-2 py-1 text-sm"
                                            placeholder="Giá nhập"
                                        />
                                        <span class="text-xs text-gray-500">VNĐ</span>
                                    </div>
                                    <InputError :message="form.errors.purchase_prices" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold">Trạng thái</label>
                                <select v-model.number="form.is_active" class="w-full rounded border px-3 py-2">
                                    <option :value="1">Hiển thị</option>
                                    <option :value="0">Ẩn</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold">Mã vạch</label>
                                <div class="flex gap-2">
                                    <input v-model="form.barcode" type="text" class="flex-1 rounded border px-3 py-2"
                                        placeholder="Nhập hoặc tạo mã vạch" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block font-semibold">Đơn vị tính</label>
                                <select v-model="form.unit_id" class="w-full rounded border px-3 py-2">
                                    <option :value="null">Chọn đơn vị tính</option>
                                    <option v-for="unit in props.units" :key="unit.id" :value="unit.id">{{ unit.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end gap-2">
                        <Link href="/admin/products"
                            class="rounded bg-gray-200 px-6 py-2 text-gray-700 hover:bg-gray-300">Quay lại</Link>
                        <button type="submit"
                            class="rounded bg-gradient-to-r from-purple-500 to-pink-500 px-8 py-2 font-semibold text-white transition hover:from-purple-600 hover:to-pink-600">
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
