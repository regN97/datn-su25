<script setup>
import { usePage } from '@inertiajs/vue3';
import { CheckCircle, X, XCircle, Bell } from 'lucide-vue-next'; // Thêm icon Bell
import { onMounted, ref, watch } from 'vue';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('');

// Hàm hiển thị thông báo
const displayMessage = (msg, msgType) => {
    message.value = msg;
    type.value = msgType;
    show.value = true;

    setTimeout(() => {
        show.value = false;
        message.value = '';
        type.value = '';
    }, 4000); // Ẩn sau 4 giây
};

// Theo dõi sự thay đổi của flash messages từ page props
watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash.success) {
            displayMessage(newFlash.success, 'success');
        } else if (newFlash.error) {
            displayMessage(newFlash.error, 'error');
        } else if (newFlash.notification) { // Xử lý thông báo mới
            displayMessage(newFlash.notification, 'notification');
        }
    },
    { deep: true },
);

// Khi component được mount, kiểm tra xem có thông báo flash nào ngay lập tức không
onMounted(() => {
    if (page.props.flash.success) {
        displayMessage(page.props.flash.success, 'success');
    } else if (page.props.flash.error) {
        displayMessage(page.props.flash.error, 'error');
    } else if (page.props.flash.notification) {
        displayMessage(page.props.flash.notification, 'notification');
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-2 scale-95"
    >
        <div
            v-if="show"
            class="fixed top-6 right-6 z-50 flex w-[360px] max-w-full items-start gap-3 rounded-xl border-l-4 bg-white/90 px-4 py-3 shadow-2xl backdrop-blur-sm dark:bg-gray-900/90"
            :class="{
                'border-green-500 text-green-800 dark:text-green-300': type === 'success',
                'border-red-500 text-red-800 dark:text-red-300': type === 'error',
                'border-blue-500 text-gray-800 dark:text-gray-200': type === 'notification', // Thêm kiểu cho thông báo mới
            }"
        >
            <div class="mt-0.5">
                <CheckCircle v-if="type === 'success'" class="h-6 w-6 text-green-500" />
                <XCircle v-else-if="type === 'error'" class="h-6 w-6 text-red-500" />
                <Bell v-else-if="type === 'notification'" class="h-6 w-6 text-blue-500" />
            </div>

            <div class="flex-1 text-sm font-medium">
                {{ message }}
            </div>

            <button @click="show = false" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-100">
                <X class="h-4 w-4" />
            </button>
        </div>
    </Transition>
</template>