<template>
    <aside class="flex w-16 flex-col items-center space-y-2 overflow-y-auto border-r bg-white p-2 shadow-inner">
        <Link :href="route('cashier.pos.index')" title="Bán hàng" class="rounded p-2 hover:bg-gray-100">
            <FileText class="h-5 w-5" />
        </Link>
        <Link :href="route('cashier.bill.lookup')" title="Tra cứu hóa đơn" class="flex items-center gap-2 rounded p-2 hover:bg-gray-100">
            <Search class="h-5 w-5" />
        </Link>
        <Link :href="route('cashier.returns.list')" title="Đổi trả" class="rounded p-2 hover:bg-gray-100">
            <Undo2 class="h-5 w-5" />
        </Link>
        <Link :href="route('cashier.shift.report')" title="Báo cáo ca" class="rounded p-2 hover:bg-gray-100">
            <BarChart class="h-5 w-5" />
        </Link>
        <Link :href="route('cashier.customer.lookup')" title="Khách hàng" class="rounded p-2 hover:bg-gray-100">
            <Users class="h-5 w-5" />
        </Link>
        <Link :href="route('cashier.notifications')" title="Thông báo" class="rounded p-2 hover:bg-gray-100">
            <Bell class="h-5 w-5" />
        </Link>
    </aside>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { BarChart, Bell, FileText, Search, Undo2, Users } from 'lucide-vue-next';
</script>
