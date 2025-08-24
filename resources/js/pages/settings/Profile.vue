<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cài đặt hồ sơ',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    phone_number: user.phone_number || '',
    date_of_birth: user.date_of_birth || '',
    address: user.address || '',
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Cài đặt hồ sơ" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Thông tin hồ sơ" description="Cập nhật thông tin cá nhân của bạn" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Họ và tên</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            placeholder="Nhập họ và tên"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Địa chỉ email</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Nhập địa chỉ email"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone_number">Số điện thoại</Label>
                        <Input
                            id="phone_number"
                            type="tel"
                            class="mt-1 block w-full"
                            v-model="form.phone_number"
                            autocomplete="tel"
                            placeholder="Nhập số điện thoại"
                        />
                        <InputError class="mt-2" :message="form.errors.phone_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="date_of_birth">Ngày sinh</Label>
                        <Input
                            id="date_of_birth"
                            type="date"
                            class="mt-1 block w-full"
                            v-model="form.date_of_birth"
                            autocomplete="bday"
                            placeholder="Chọn ngày sinh"
                        />
                        <InputError class="mt-2" :message="form.errors.date_of_birth" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Địa chỉ</Label>
                        <Input
                            id="address"
                            class="mt-1 block w-full"
                            v-model="form.address"
                            autocomplete="street-address"
                            placeholder="Nhập địa chỉ"
                        />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="text-muted-foreground -mt-4 text-sm">
                            Email của bạn chưa được xác minh.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Nhấn vào đây để gửi lại email xác minh.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            Một liên kết xác minh mới đã được gửi đến địa chỉ email của bạn.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Lưu</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Đã lưu.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>