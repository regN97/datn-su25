<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    product: {
        id: number;
        name: string;
        price: number;
        description: string;
        category_id: number | null;
        status: string;
        image: string | null;
    },
    categories: { id: number; name: string }[],
    suppliers: { id: number; name: string }[],
    productSuppliers: number[]
}>();

const form = useForm({
    name: props.product.name,
    price: props.product.price,
    description: props.product.description,
    category_id: props.product.category_id,
    status: props.product.status,
    image: null as File | null,
    suppliers: Array.isArray(props.productSuppliers) ? [...props.productSuppliers] : [],
});

function submit() {
    form.post(`/admin/products/${props.product.id}?_method=PUT`, {
        forceFormData: true
    });
}
</script>

<template>
    <Head title="Sửa sản phẩm" />

    <AppLayout>
        <div class="flex justify-center items-center min-h-[80vh] bg-gradient-to-br from-blue-50 to-green-50">
            <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-8 border border-blue-100">
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-2xl font-bold text-blue-700">Sửa sản phẩm</h1>
                    <Link
                        href="/admin/products"
                        class="rounded-xl bg-gray-200 text-gray-700 px-4 py-2 hover:bg-gray-300 transition"
                    >
                        Quay lại
                    </Link>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Tên sản phẩm</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            :class="{ 'border-red-400': form.errors.name }"
                            placeholder="Nhập tên sản phẩm"
                        />
                        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                            {{ form.errors.name }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Giá</label>
                        <input
                            v-model="form.price"
                            type="number"
                            min="0"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            :class="{ 'border-red-400': form.errors.price }"
                            placeholder="Nhập giá sản phẩm"
                        />
                        <div v-if="form.errors.price" class="text-red-500 text-sm mt-1">
                            {{ form.errors.price }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Mô tả</label>
                        <textarea
                            v-model="form.description"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            rows="3"
                            :class="{ 'border-red-400': form.errors.description }"
                            placeholder="Nhập mô tả sản phẩm"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                            {{ form.errors.description }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Danh mục</label>
                        <select
                            v-model="form.category_id"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            :class="{ 'border-red-400': form.errors.category_id }"
                        >
                            <option :value="null">— Chọn danh mục —</option>
                            <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">
                            {{ form.errors.category_id }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Nhà cung cấp</label>
                        <select
                            v-model="form.suppliers"
                            multiple
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            :class="{ 'border-red-400': form.errors.suppliers }"
                        >
                            <option v-for="sup in props.suppliers" :key="sup.id" :value="sup.id">
                                {{ sup.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.suppliers" class="text-red-500 text-sm mt-1">
                            {{ form.errors.suppliers }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Trạng thái</label>
                        <select
                            v-model="form.status"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            :class="{ 'border-red-400': form.errors.status }"
                        >
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Ngừng hoạt động</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-500 text-sm mt-1">
                            {{ form.errors.status }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Ảnh sản phẩm</label>
                        <!-- <input
                            type="file"
                            @change="e => form.image = e.target.files[0]"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                        /> -->
                        <div v-if="props.product.image" class="mt-2">
                            <img :src="props.product.image" alt="Ảnh sản phẩm" class="h-24 rounded shadow" />
                        </div>
                        <div v-if="form.errors.image" class="text-red-500 text-sm mt-1">
                            {{ form.errors.image }}
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition"
                            :disabled="form.processing"
                        >
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
