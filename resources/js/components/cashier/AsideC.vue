<template>
  <aside class="bg-white border-r w-16 p-2 flex flex-col items-center space-y-2 overflow-y-auto shadow-inner">
    <Link :href="route('cashier.pos.index')" title="Bán hàng" class="p-2 hover:bg-gray-100 rounded">
    <FileText class="w-5 h-5" />
    </Link>
    <Link :href="route('cashier.bill.lookup')" title="Tra cứu hóa đơn"
      class="p-2 hover:bg-gray-100 rounded flex items-center gap-2">
    <Search class="w-5 h-5" />
    </Link>
    <Link href="#" title="Đổi trả" class="p-2 hover:bg-gray-100 rounded">
    <Undo2 class="w-5 h-5" />
    </Link>
    <Link :href="route('cashier.shift.report')" title="Báo cáo ca" class="p-2 hover:bg-gray-100 rounded">
    <BarChart class="w-5 h-5" />
    </Link>
    <Link :href="route('cashier.customer.lookup')" title="Khách hàng" class="p-2 hover:bg-gray-100 rounded">
    <Users class="w-5 h-5" />
    </Link>
    <Link :href="route('cashier.notifications')" title="Thông báo" class="p-2 hover:bg-gray-100 rounded">
    <Bell class="w-5 h-5" />
    </Link>
    <Link href="#" title="Cài đặt" class="p-2 hover:bg-gray-100 rounded">
    <Settings class="w-5 h-5" />
    </Link>
  </aside>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import {
  FileText,
  Boxes,
  Search,
  Undo2,
  BarChart,
  Users,
  Printer,
  Bell,
  UserCog,
  Settings
} from 'lucide-vue-next';
</script>
