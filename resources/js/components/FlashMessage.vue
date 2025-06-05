<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle, XCircle, X } from 'lucide-vue-next';

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
    }, 4000); // Ẩn sau 3 giây
};

// Theo dõi sự thay đổi của flash messages từ page props
watch(() => page.props.flash, (newFlash) => {
    if (newFlash.success) {
        displayMessage(newFlash.success, 'success');
    } else if (newFlash.error) {
        displayMessage(newFlash.error, 'error');
    }
}, { deep: true });

// Khi component được mount, kiểm tra xem có thông báo flash nào ngay lập tức không
onMounted(() => {
    if (page.props.flash.success) {
        displayMessage(page.props.flash.success, 'success');
    } else if (page.props.flash.error) {
        displayMessage(page.props.flash.error, 'error');
    }
});
</script>

<template>
    <Transition enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-2 scale-95">
        <div v-if="show"
            class="fixed top-6 right-6 z-50 w-[360px] max-w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-900/90 shadow-2xl border-l-4 flex items-start gap-3 backdrop-blur-sm"
            :class="{
                'border-green-500 text-green-800 dark:text-green-300': type === 'success',
                'border-red-500 text-red-800 dark:text-red-300': type === 'error',
            }">
            <!-- Icon -->
            <div class="mt-0.5">
                <CheckCircle v-if="type === 'success'" class="w-6 h-6 text-green-500" />
                <XCircle v-else-if="type === 'error'" class="w-6 h-6 text-red-500" />
            </div>

            <!-- Message -->
            <div class="flex-1 text-sm font-medium">
                {{ message }}
            </div>

            <!-- Close -->
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-100 transition">
                <X class="w-4 h-4" />
            </button>
        </div>
    </Transition>


</template>