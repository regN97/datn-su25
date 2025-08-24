<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { router, usePage, Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { ref, computed, onMounted, watch } from 'vue'
import { Pencil, Trash2, PackagePlus } from 'lucide-vue-next';

const props = defineProps({ units: Array })
const breadcrumbs = [
  { title: 'Quản lý đơn vị tính', href: route('admin.units.index') },
]

const perPageOptions = [5, 10, 25, 50]
const perPage = ref(5)
const currentPage = ref(1)

const searchTerm = ref('') 

function normalize(str) {
  return str
    ? str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
    : ''
}

const filteredUnits = computed(() => {
  if (!searchTerm.value) return props.units
  const term = normalize(searchTerm.value)
  return props.units.filter(u =>
    normalize(u.name).includes(term)
  )
})

const total = computed(() => filteredUnits.value.length)
const totalPages = computed(() => Math.ceil(total.value / perPage.value))

const paginatedUnits = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredUnits.value.slice(start, start + perPage.value)
})

// reset trang về 1 khi search
watch(searchTerm, () => {
  currentPage.value = 1
})

const notification = ref('success');
const showNotification = ref(false);
const page = usePage();

function showSimpleNotification(message) {
  notification.value = message;
  showNotification.value = true;
  setTimeout(() => {
    showNotification.value = false;
  }, 2500);
}

function deleteUnit(id) {
  if (confirm('Bạn có chắc chắn muốn xóa đơn vị tính này?')) {
    router.delete(route('admin.units.destroy', id), {
      onSuccess: () => {
        showSimpleNotification('Xóa đơn vị tính thành công', 'success');
      },
      onError: (error) => {
        showSimpleNotification(error.message || 'Không thể xóa đơn vị tính này', 'error');
      }
    })
  }
}

function goToCreatePage() {
  router.visit(route('admin.units.create'))
}
function goToEditPage(id) {
  router.visit(route('admin.units.edit', id))
}
function goToPage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}
function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}
function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++
}
function changePerPage(event) {
  perPage.value = +(event.target.value)
  currentPage.value = 1
}

onMounted(() => {
  if (page.props.errors && page.props.errors.unit_delete) {
    showSimpleNotification(page.props.errors.unit_delete)
    page.props.errors.unit_delete = undefined
  }
})
</script>

<template>

  <Head title="Units" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div v-if="showNotification" :class="[
      'fixed top-6 left-1/2 z-50 -translate-x-1/2 rounded-lg px-6 py-3 text-white shadow-lg text-base font-semibold animate-fade-in-out',
      notification === 'success' ? 'bg-green-500' : 'bg-red-500'
    ]">
      {{ notification }}
    </div>

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div
        class="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[100vh] flex-1 rounded-xl border md:min-h-min">
        <div class="p-6">
          <div class="mb-4">
  <!-- H1 + nút thêm -->
  <div class="flex items-center justify-between mb-2">
    <h1 class="text-xl font-bold">Quản lý đơn vị tính</h1>
    <button @click="goToCreatePage" class="rounded-3xl bg-green-500 px-8 py-2 text-white hover:bg-green-600">
      <PackagePlus />
    </button>
  </div>

  <!-- Ô tìm kiếm (xuống dưới h1) -->
  <div>
    <input
      v-model="searchTerm"
      type="text"
      placeholder="Tìm theo tên đơn vị..."
      class="border rounded px-3 py-2 text-sm w-[250px]"
    />
  </div>
</div>


          <div class="overflow-hidden rounded-lg bg-white shadow">
            <table class="w-full border-collapse text-sm table-fixed">
              <thead>
                <tr class="bg-gray-100">
                  <th class="w-[15%] px-3 py-2 text-center font-semibold">Tên đơn vị</th>
                  <th class="w-[65%] px-3 py-2 text-center font-semibold">Mô tả</th>
                  <th class="w-[20%] px-3 py-2 text-center font-semibold">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(unit, index) in paginatedUnits" :key="unit.id" class="border-t hover:bg-gray-50 transition">
                  <td class="px-3 py-2 text-center truncate">{{ unit.name }}</td>
                  <td class="px-3 py-2 text-center" style="vertical-align: top;">
                    <div class="line-clamp-2 min-h-[48px] overflow-hidden">
                      {{ unit.description || '-' }}
                    </div>
                  </td>
                  <td class="w-[15%] p-3 text-left text-sm whitespace-nowrap">
                  <td class="flex items-center justify-center space-x-2 text-center">
                    <button @click="goToEditPage(unit.id)"
                      class="rounded-md bg-blue-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none">
                      <Pencil class="h-4 w-4" />
                    </button>
                    <button @click="deleteUnit(unit.id)" variant="destructive"
                      class="rounded-md bg-red-600 px-3 py-1 text-white transition duration-150 ease-in-out hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none">
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </td>
                  </td>
                </tr>
                <tr v-if="!paginatedUnits.length">
                  <td colspan="3" class="text-center py-4 text-gray-500">Không có đơn vị tính nào.</td>
                </tr>
              </tbody>
            </table>
          </div>



          <!-- Phân trang -->
          <div class="mt-4 flex flex-col items-start gap-2 md:flex-row md:items-center md:justify-between">
            <p class="text-sm">
              Hiển thị từ <span class="font-medium">{{ (currentPage - 1) * perPage + 1 }}</span> đến
              <span class="font-medium">{{ Math.min(currentPage * perPage, total) }}</span> trên
              <span class="font-medium">{{ total }}</span> mục
            </p>

            <div class="flex items-center gap-2">
              <button class="px-2 py-1 text-sm text-gray-600 hover:text-black" :disabled="currentPage === 1"
                @click="prevPage">
                ← Trước
              </button>
              <template v-for="page in totalPages" :key="page">
                <button class="px-3 py-1 text-sm rounded"
                  :class="page === currentPage ? 'bg-gray-300 font-bold' : 'text-gray-500 hover:text-black'"
                  @click="goToPage(page)">
                  {{ page }}
                </button>
              </template>
              <button class="px-2 py-1 text-sm text-gray-600 hover:text-black" :disabled="currentPage === totalPages"
                @click="nextPage">
                Sau →
              </button>
            </div>

            <div class="flex items-center gap-1">
              <span class="text-sm">Hiển thị</span>
              <select v-model="perPage" @change="changePerPage" class="border rounded p-1 text-sm">
                <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
              </select>
              <span class="text-sm">kết quả</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>


<style scoped>
@keyframes fade-in-out {
  0% {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }

  10% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

  90% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }

  100% {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
}

.animate-fade-in-out {
  animation: fade-in-out 2.5s both;
}
</style>
