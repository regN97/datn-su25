<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

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
    }, 3000); // Ẩn sau 3 giây
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
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-x-full"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition ease-in duration-300"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-full"
    >
        <div
            v-if="show"
            :class="{
                'bg-green-500': type === 'success',
                'bg-red-500': type === 'error',
            }"
            class="fixed top-4 right-4 z-50 p-4 text-white rounded-lg shadow-lg flex items-center space-x-2"
        >
            <svg v-if="type === 'success'" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <svg v-else-if="type === 'error'" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ message }}</span>
            <button @click="show = false" class="ml-auto focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </Transition>
</template>