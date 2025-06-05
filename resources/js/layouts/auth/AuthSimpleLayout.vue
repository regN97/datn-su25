<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>
<template>
  <div class="flex min-h-[80vh] flex-col items-center justify-start pt-12 bg-background p-4 md:p-6">
    <div class="w-full max-w-md sm:max-w-lg"> 
      <div class="flex flex-col gap-6">

        <!-- Logo bên trái, tiêu đề bên phải -->
        <div class="flex items-center gap-4">
          <!-- Logo -->
          <Link :href="route('home')" class="flex-shrink-0">
            <div
              class="h-28 w-28 flex items-center justify-center rounded-xl bg-white shadow dark:bg-gray-800 dark:shadow-lg transition-transform duration-200 hover:scale-105"
              title="Website quản lý cửa hàng bán lẻ G7 Mart"
            >
              <AppLogoIcon imageUrl="/storage/piclumen-1747750187180.png" class="h-24 w-24 object-contain" />
            </div>
          </Link>

          <!-- Tiêu đề và mô tả -->
          <div class="flex flex-col text-left">
            <h1 class="text-lg font-semibold text-orange-600">{{ title }}</h1>
            <br>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ description }}</p>
          </div>
        </div>

        <!-- Slot content -->
        <slot />
        
      </div>
    </div>
  </div>
</template>

