<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'




const props = defineProps<{
  user: {
    id: number
    name: string
    email: string | null
    phone_number: string | null
    role_id: number
  },
  userRoles: {
    id: number
    name: string
  }[]
}>()

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone_number: props.user.phone_number,
  password: '',
  role_id: props.user.role_id,
})

function submit() {
  form.put(`/admin/users/${props.user.id}`, {
    onSuccess: () => {
      // có thể hiển thị thông báo hoặc chuyển trang
    },
  })
}
</script>


<template>
  <Head title="Cập nhật thông tin nhân viên" />
  <AppLayout>
    <h1 class="text-2xl font-bold mb-4">Cập nhật thông tin nhân viên</h1>
    
    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block font-medium">Tên nhân viên</label>
        <input v-model="form.name" class="w-full border p-2 rounded" />
      </div>
      <div>
        <label class="block font-medium">Email</label>
        <input v-model="form.email" type="email" class="w-full border p-2 rounded" />
      </div>
      <div>
        <label class="block font-medium">Chức vụ</label>
        <select v-model="form.role_id" class="w-full border p-2 rounded">
          <option v-for="role in props.userRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
        </select>
      </div>
      <div>
        <label class="block font-medium">Số điện thoại</label>
        <input v-model="form.phone_number" class="w-full border p-2 rounded" />
      </div>
      
    </form>

    <div class="mt-6 flex justify-between">
      <button @click="router.visit('/admin/users')" class="bg-red-500 text-white px-4 py-2 rounded">Quay lại</button>

      <button @click="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Lưu</button>
    </div>
  </AppLayout>
</template>

