<script setup>
import { onMounted, onUnmounted } from 'vue';

// Nhận các props từ POS.vue
const props = defineProps({
  onHelp: Function,
  onAddItem: Function,
  onSearch: Function,
  onCustomer: Function,
  onDiscount: Function,
  onHoldOrder: Function,
  onReprint: Function,
  onDeleteItem: Function,
  onCheckout: Function,
});

const handleKeyDown = (event) => {
  // Kiểm tra xem event.key có tồn tại không trước khi sử dụng
  if (!event.key) return;

  // Xử lý các phím chức năng
  switch (event.key.toLowerCase()) {
    case 'f1':
      event.preventDefault();
      props.onHelp?.();
      break;
    case 'f2':
      event.preventDefault();
      props.onAddItem?.();
      break;
    case 'f3':
      event.preventDefault();
      props.onSearch?.();
      break;
    case 'f4':
      event.preventDefault();
      props.onCustomer?.();
      break;
    case 'f5':
      event.preventDefault();
      props.onDiscount?.();
      break;
    case 'f6':
      event.preventDefault();
      props.onHoldOrder?.();
      break;
    case 'f7':
      event.preventDefault();
      props.onReprint?.();
      break;
    case 'f8':
      event.preventDefault();
      props.onDeleteItem?.();
      break;
    case 'f9':
      event.preventDefault();
      props.onCheckout?.();
      break;
    default:
      break;
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
  <!-- Không cần template vì component này chỉ xử lý sự kiện bàn phím -->
</template>