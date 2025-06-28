<template>
  <aside class="bg-white border-r w-16 p-2 flex flex-col items-center space-y-2 overflow-y-auto shadow-inner">
    <Link :href="route('cashier.pos.index')" title="Bán hàng" class="p-2 hover:bg-gray-100 rounded">
      <FileText class="w-5 h-5" />
    </Link>
    <Link href="#" title="Kiểm kho" class="p-2 hover:bg-gray-100 rounded">
      <Boxes class="w-5 h-5" />
    </Link>
    <Link href="#" title="Tra hóa đơn" class="p-2 hover:bg-gray-100 rounded">
      <Search class="w-5 h-5" />
    </Link>
    <Link href="#" title="Đổi trả" class="p-2 hover:bg-gray-100 rounded">
      <Undo2 class="w-5 h-5" />
    </Link>
    <Link href="#" title="Báo cáo ca" class="p-2 hover:bg-gray-100 rounded">
      <BarChart class="w-5 h-5" />
    </Link>
    <Link href="#" title="Khách hàng" class="p-2 hover:bg-gray-100 rounded">
      <Users class="w-5 h-5" />
    </Link>
    <Link href="#" title="In lại HĐ" class="p-2 hover:bg-gray-100 rounded">
      <Printer class="w-5 h-5" />
    </Link>
    <Link href="#" title="Thông báo" class="p-2 hover:bg-gray-100 rounded">
      <Bell class="w-5 h-5" />
    </Link>
    <Link href="#" title="Nhân viên" class="p-2 hover:bg-gray-100 rounded">
      <UserCog class="w-5 h-5" />
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
