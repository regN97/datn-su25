<template>
  <!-- This component doesn't render anything -->
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
  onHelp: Function,
  onAddItem: Function,
  onSearch: Function,
  onCustomer: Function,
  onDiscount: Function,
  onHoldOrder: Function,
  onReprint: Function,
  onDeleteItem: Function,
  onCheckout: Function
});

const handleKeyDown = (event) => {
  // Prevent default browser behavior for function keys
  if (event.key.startsWith('F')) {
    event.preventDefault();
  }

  switch (event.key) {
    case 'F1':
      props.onHelp?.();
      break;
    case 'F2':
      props.onAddItem?.();
      break;
    case 'F3':
      props.onSearch?.();
      break;
    case 'F4':
      props.onCustomer?.();
      break;
    case 'F5':
      props.onDiscount?.();
      break;
    case 'F6':
      props.onHoldOrder?.();
      break;
    case 'F7':
      props.onReprint?.();
      break;
    case 'F8':
      props.onDeleteItem?.();
      break;
    case 'F9':
      props.onCheckout?.();
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