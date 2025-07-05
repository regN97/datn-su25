<template>
  <div class="h-screen bg-gray-100 flex flex-col text-gray-800 text-sm font-sans overflow-hidden">
    <HeaderC />
    <div class="flex flex-1 overflow-hidden">
      <AsideC />
          <FlashMessage />

      <MainC>
        <slot></slot>
      </MainC>
    </div>
  </div>
</template>

<script setup>
import HeaderC from '@/components/cashier/HeaderC.vue';
import AsideC from '@/components/cashier/AsideC.vue';
import MainC from '@/components/cashier/MainC.vue';
import FlashMessage from '@/components/FlashMessage.vue';
</script>

<style>
.overflow-y-auto::-webkit-scrollbar {
  display: none;
}
.overflow-y-auto {
  scrollbar-width: none;
}
</style>
