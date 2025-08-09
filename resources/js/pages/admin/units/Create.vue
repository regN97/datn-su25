<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
        <div class="container mx-auto p-6">
          <h2 class="text-2xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Thêm đơn vị tính</h2>
          <form class="space-y-6 w-full" @submit.prevent="submit">
            <div>
              <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                Tên đơn vị <span class="text-red-500">*</span>
              </label>
              <input type="text" id="name" v-model="form.name"
                class="w-full border border-gray-300 rounded-md h-12 px-3 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': form.errors.name }"
                placeholder="Nhập tên đơn vị vào đây" />
              <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
            </div>
            <div>
              <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Mô tả</label>
              <textarea id="description" v-model="form.description"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                rows="3"></textarea>
              <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
            </div>
            <div class="flex justify-end gap-2 mt-8">
              <button type="button" @click="goBack"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors duration-200 ease-in-out hover:bg-gray-100 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">Quay lại</button>
              <button type="submit"
                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:bg-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800 dark:disabled:bg-blue-500">Lưu</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>


<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
const form = useForm({ name: '', description: '' })
const breadcrumbs = [
  { title: 'Quản lý đơn vị tính', href: route('admin.units.index') },
  { title: 'Thêm mới', href: '#' },
]
function submit() {
  form.post(route('admin.units.store'))
}
function goBack() {
  router.visit(route('admin.units.index'))
}
</script> 