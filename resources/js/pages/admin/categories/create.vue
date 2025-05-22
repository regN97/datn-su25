<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const categories = ref(page.props.categories as { id: number; name: string }[]);

const form = useForm({
    name: '',
    parent_id: null as number | null,
    description: '',
});

const newParentName = ref('');

function addNewParentCategory() {
    const name = newParentName.value.trim();
    if (!name) return;
    const tempId = -Date.now();
    categories.value.unshift({ id: tempId, name });
    form.parent_id = tempId;
    newParentName.value = '';
}

function submit() {
    form.post('/admin/categories');
}
</script>

<template>
    <Head title="Thêm danh mục" />

    <AppLayout>
        <div class="flex justify-center items-center min-h-[80vh] bg-gradient-to-br from-blue-50 to-green-50">
            <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl p-8 border border-blue-100">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <h1 class="text-2xl font-bold text-blue-700">Thêm danh mục sản phẩm</h1>
                    </div>
                    <Link
                        href="/admin/categories"
                        class="rounded-xl bg-gray-200 text-gray-700 px-4 py-2 hover:bg-gray-300 transition"
                    >
                        Quay lại
                    </Link>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Tên danh mục <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            :class="{ 'border-red-400': form.errors.name }"
                            placeholder="Nhập tên danh mục"
                        />
                        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                            {{ form.errors.name }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Thêm nhanh danh mục cha</label>
                        <div class="flex gap-2">
                            <input
                                v-model="newParentName"
                                type="text"
                                class="flex-1 border-2 border-green-100 rounded-lg px-3 py-2 focus:border-green-400 outline-none transition"
                                placeholder="Nhập tên danh mục cha mới"
                            />
                            <button
                                type="button"
                                class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition"
                                @click="addNewParentCategory"
                            >
                                + Thêm
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Danh mục cha</label>
                        <select
                            v-model="form.parent_id"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            :class="{ 'border-red-400': form.errors.parent_id }"
                        >
                            <option :value="null">— Không có —</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.parent_id" class="text-red-500 text-sm mt-1">
                            {{ form.errors.parent_id }}
                        </div>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-gray-700">Mô tả</label>
                        <textarea
                            v-model="form.description"
                            class="w-full border-2 border-blue-100 rounded-lg px-3 py-2 focus:border-blue-400 outline-none transition"
                            rows="3"
                            :class="{ 'border-red-400': form.errors.description }"
                            placeholder="Nhập mô tả (không bắt buộc)"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">
                            {{ form.errors.description }}
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-8 py-2 rounded-xl font-semibold hover:bg-blue-700 transition"
                            :disabled="form.processing"
                        >
                            Thêm mới
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
