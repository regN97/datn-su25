<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/InputError.vue';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lí nhà cung cấp',
        href: '/admin/suppliers',
    },
    {
        title: 'Cập nhập nhà cung cấp',
        href: '/admin/suppliers/edit',
    },
];
const props = defineProps<{
    supplier: {
        id: number,
        name: string,
        contact_person: string,
        email: string,
        phone: string,
        address: string | null,
    }
}>();

const form = useForm({
    name: props.supplier.name ?? '',
    contact_person: props.supplier.contact_person ?? '',
    email: props.supplier.email ?? '',
    phone: props.supplier.phone ?? '',
    address: props.supplier.address ?? '',
});

function submit() {
    form.put(`/admin/suppliers/${props.supplier.id}`);
}
</script>

<template>
    <Head title="Sửa Nhà cung cấp" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
                <div class="container mx-auto p-6">
                    <h2 class="text-2xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Sửa nhà cung cấp</h2>

                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block mb-2 font-medium">Tên nhà cung cấp <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" class="w-full rounded border px-3 py-2" placeholder="Nhập tên nhà cung cấp" />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            <div>
                                <label class="block mb-2 font-medium">Người liên hệ <span class="text-red-500">*</span></label>
                                <input v-model="form.contact_person" type="text" class="w-full rounded border px-3 py-2" placeholder="Nhập tên người liên hệ" />
                                <InputError :message="form.errors.contact_person" class="mt-1" />
                            </div>
                            <div>
                                <label class="block mb-2 font-medium">Email <span class="text-red-500">*</span></label>
                                <input v-model="form.email" type="email" class="w-full rounded border px-3 py-2" placeholder="Nhập email" />
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>
                            <div>
                                <label class="block mb-2 font-medium">Số điện thoại <span class="text-red-500">*</span></label>
                                <input v-model="form.phone" type="text" class="w-full rounded border px-3 py-2" placeholder="Nhập số điện thoại" />
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium">Địa chỉ</label>
                            <textarea v-model="form.address" class="w-full rounded border px-3 py-2" placeholder="Nhập địa chỉ nhà cung cấp"></textarea>
                            <InputError :message="form.errors.address" class="mt-1" />
                        </div>
                        <div class="flex justify-end gap-2 mt-6">
                            <Link href="/admin/suppliers" class="px-6 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 flex items-center gap-2">
                                <span>🙏</span> Quay lại
                            </Link>
                            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 flex items-center gap-2">
                                <span>💾</span> Lưu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
