<template>
    <div class="flex h-screen flex-col overflow-hidden bg-gray-100 font-sans text-sm text-gray-800">
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
import AsideC from '@/components/cashier/AsideC.vue';
import HeaderC from '@/components/cashier/HeaderC.vue';
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
