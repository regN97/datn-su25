<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import { PencilLine } from 'lucide-vue-next';

defineProps<{
    purchaseReturn: {
        return_number: string
        id: number; // Add ID here as you use it in goToEdit(purchaseReturn.id)
        purchase_order_code: string
        supplier_name: string
        reason: string | null
        return_date: string
        status: string
        created_by: string
        total_items_returned: number
        total_value_returned: number
        items: {
            product_name: string
            batch_number: string
            product_sku: string
            manufacturing_date: string | null
            expiry_date: string | null
            quantity_returned: number
            unit_cost: number
            subtotal: number
            reason: string | null
        }[]
    }
}>()

const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('vi-VN')
}

const statusTextClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'rejected':
            return 'text-red-500'
        case 'approved':
            return 'text-blue-500'
        case 'pending':
            return 'text-amber-500'
        case 'completed':
            return 'text-green-500'
        default:
            return 'text-gray-500'
    }
}

const translateStatus = (status: string) => {
    switch (status.toLowerCase()) {
        case 'pending':
            return 'Chờ duyệt'
        case 'approved':
            return 'Đã duyệt'
        case 'completed':
            return 'Hoàn tất'
        case 'rejected':
            return 'Từ chối'
        default:
            return status
    }
}

function goToIndex() {
    router.visit('/admin/purchaseReturn')
}
function goToEdit(id: number) { // Make sure ID is number as per your prop definition
    router.visit(`/admin/purchaseReturn/${id}/edit`)
}
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Quản lý phiếu trả hàng',
        href: '/admin/purchaseReturn',
    },
]
</script>

<template>
    <Head title="Chi tiết phiếu trả hàng" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 rounded-2xl p-8 bg-gray-50 min-h-screen">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 border-l-4 border-blue-500 pl-4">
                        Chi tiết phiếu trả hàng
                    </h1>
                    <div class="flex items-center gap-2 mt-4">
                        <button
                            v-if="purchaseReturn.status.toLowerCase() !== 'completed'"
                            @click="goToEdit(purchaseReturn.id)"
                            class="flex items-center gap-1 text-sm text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-xl transition">
                            <PencilLine class="h-4 w-4" />
                            Sửa đơn
                        </button>
                        <button @click="goToIndex"
                            class="flex items-center gap-1 px-4 py-2 text-sm text-gray-700 border border-gray-300 bg-white hover:bg-gray-100 rounded-lg transition">
                            🔙 Quay lại danh sách
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm space-y-2">
                        <p><strong>Mã phiếu trả hàng:</strong> {{ purchaseReturn.return_number }}</p>
                        <p><strong>Mã đơn đặt hàng:</strong> {{ purchaseReturn.purchase_order_code }}</p>
                        <p><strong>Nhà cung cấp:</strong> {{ purchaseReturn.supplier_name }}</p>
                        <p><strong>Lý do trả hàng:</strong> {{ purchaseReturn.reason || 'Không có' }}</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 shadow-sm space-y-2">
                        <p><strong>Ngày trả hàng:</strong> {{ formatDate(purchaseReturn.return_date) }}</p>
                        <p>
                            <strong>Trạng thái:</strong>
                            <span class="inline-block font-medium text-base px-3 py-1 rounded"
                                :class="statusTextClass(purchaseReturn.status)">
                                {{ translateStatus(purchaseReturn.status) }}
                            </span>
                        </p>
                        <p><strong>Người tạo phiếu:</strong> {{ purchaseReturn.created_by }}</p>
                    </div>
                </div>

                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Danh sách sản phẩm</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border-separate border-spacing-y-3">
                            <thead class="bg-blue-50 text-gray-700 uppercase font-semibold">
                                <tr>
                                    <th class="px-6 py-4 text-left">Tên sản phẩm</th>
                                    <th class="px-6 py-4 text-left">Mã lô</th>
                                    <th class="px-6 py-4 text-left">Mã SKU</th>
                                    <th class="px-6 py-4 text-left">NSX</th>
                                    <th class="px-6 py-4 text-left">HSD</th>
                                    <th class="px-6 py-4 text-center">Số lượng</th>
                                    <th class="px-6 py-4 text-right">Đơn giá</th>
                                    <th class="px-6 py-4 text-right">Tổng tiền</th>
                                    <th class="px-6 py-4 text-left">Lý do trả</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in purchaseReturn.items" :key="item.product_sku"
                                    class="bg-white border border-gray-200 rounded-lg shadow-sm">
                                    <td class="px-6 py-4 rounded-l-lg">{{ item.product_name }}</td>
                                    <td class="px-6 py-4">{{ item.batch_number }}</td>
                                    <td class="px-6 py-4">{{ item.product_sku }}</td>
                                    <td class="px-6 py-4">{{ item.manufacturing_date ?
                                        formatDate(item.manufacturing_date) : '—' }}</td>
                                    <td class="px-6 py-4">{{ item.expiry_date ? formatDate(item.expiry_date) : '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">{{ item.quantity_returned }}</td>
                                    <td class="px-6 py-4 text-right">{{ item.unit_cost.toLocaleString() }} đ</td>
                                    <td class="px-6 py-4 text-right">{{ item.subtotal.toLocaleString() }} đ</td>
                                    <td class="px-6 py-4 rounded-r-lg">{{ item.reason || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row justify-between text-gray-800 font-semibold mb-6">
                    <p>Tổng số sản phẩm trả: {{ purchaseReturn.total_items_returned }}</p>
                    <p>Tổng giá trị trả lại: {{ purchaseReturn.total_value_returned.toLocaleString() }} đ</p>
                </div>

                <div class="flex gap-4">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Gửi yêu cầu
                    </button>
                    <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Xoá
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
