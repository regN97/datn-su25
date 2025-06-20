<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm ,usePage} from '@inertiajs/vue3';
import { ref,onMounted } from 'vue';
import { Pencil ,Trash } from 'lucide-vue-next';

interface User {
  id: number;
  name: string;
  email: string | null;
  phone_number: string | null;
  role?: {
    id: number;
    name: string;
    code: string;
  };
}

defineProps<{
  users: User[];
  userRoles: {
    id: number;
    name: string;
  }[];
}>();

const showForm = ref(false)

const form = useForm({
  name: '',
  email: '',
  password: '',
  phone_number: '',
  role_id: '', // Để ép user chọn
})

function submit() {
  form.post('/admin/users', {
    onSuccess: () => {
      form.reset()
      showForm.value = false
    },
  })
}
const page = usePage() as { props: { flash: { success?: string } } };
const hasShownSuccess = ref(false);

onMounted(() => {
  if (page.props.flash.success && !hasShownSuccess.value) {
    alert(page.props.flash.success);
    hasShownSuccess.value = true;
  }
});
function deleteUser(id: number) {
  if (confirm('Bạn có chắc chắn muốn xoá người dùng này?')) {
    router.delete(`/admin/users/${id}`, {
      onSuccess: () => {
        // alert đơn giản hoặc flash message
        alert('Đã xoá người dùng thành công');
      },
    })
  }
}
</script>

<template>
  <Head title="Quản lý người dùng" />
  <AppLayout :breadcrumbs="[{ title: 'Quản lý người dùng', href: '/admin/users' }]">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="border-sidebar-border/70 relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
        <div class="container mx-auto p-6">
          
          <!-- Tiêu đề và nút -->
          <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Danh sách người dùng</h1>
            <button class="rounded-3xl bg-green-600 px-6 py-2 text-white hover:bg-green-700" @click="showForm = !showForm">
              {{ showForm ? 'Đóng' : 'Thêm mới' }}
            </button>
          </div>
         


          <!-- Form thêm mới -->
          <div v-if="showForm" class="bg-white rounded-xl p-6 shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block font-medium">Tên người dùng</label>
                <input v-model="form.name" type="text" class="w-full border p-2 rounded" />
                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
              </div>
              <div>
                <label class="block font-medium">Email</label>
                <input v-model="form.email" type="email" class="w-full border p-2 rounded" />
                <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
              </div>
              <div>
                <label class="block font-medium">Mật khẩu</label>
                <input v-model="form.password" type="password" class="w-full border p-2 rounded" />
                <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
              </div>
              <div>
                <label class="block font-medium">Số điện thoại</label>
                <input v-model="form.phone_number" type="text" class="w-full border p-2 rounded" />
                <p v-if="form.errors.phone_number" class="text-sm text-red-600">{{ form.errors.phone_number }}</p>
              </div>
            </div>
            <div>
             <label class="block font-medium  mb-3">Vai trò</label>
                 <select v-model="form.role_id" class="w-full border p-2 rounded">
                     <option disabled value="">Chọn vai trò</option>
                        <option
                            v-for="role in userRoles"
                            :key="role.id"
                            :value="Number(role.id)"
                            >
                            {{ role.name }}
                        </option>
                        </select>
                            <p v-if="form.errors.role_id" class="text-sm text-red-600">{{ form.errors.role_id }}</p>
                            </div>



            <div class="mt-4">
              <button @click="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                Thêm người dùng
              </button>
            </div>
          </div>

          <!-- Bảng danh sách -->
          <div class="table-wrapper overflow-hidden rounded-lg bg-white shadow-md">
            <table class="min-w-full table-auto border-separate border-spacing-y-3">
              <thead class="bg-gray-200 text-gray-700 text-sm font-semibold uppercase">
                <tr>
                  <th class="px-6 py-4 text-left">Tên</th>
                  <th class="px-6 py-4 text-left">Email</th>
                  <th class="px-6 py-4 text-left">Số điện thoại</th>
                  <th class="px-6 py-4 text-left">Vai trò</th>
                  <th class="px-6 py-4 text-center">Hành động</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="user in users"
                  :key="user.id"
                  class="bg-white hover:bg-gray-100 transition border border-gray-200 rounded-lg shadow-sm"
                >
                  <td class="px-6 py-4 rounded-l-lg">{{ user.name }}</td>
                  <td class="px-6 py-4">{{ user.email }}</td>
                  <td class="px-6 py-4">{{ user.phone_number }}</td>
                  <td class="px-6 py-4">{{ user.role?.name }}</td>
                  <td class="px-6 py-4 text-center rounded-r-lg">
  <button
    class="text-blue-600 hover:text-blue-800 transition p-1"
    title="Sửa"
    @click="router.visit(`/admin/users/${user.id}/edit`)"
  >
    <Pencil class="w-5 h-5" />
  </button>
  <button
    class="text-red-600 hover:text-red-800 transition p-1 ml-2"
    title="Xoá"
    @click="deleteUser(user.id)"
  >
    <Trash class="w-5 h-5" />
  </button>
</td>
                </tr>
                <tr v-if="users.length === 0">
                  <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                    Không có dữ liệu
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>
