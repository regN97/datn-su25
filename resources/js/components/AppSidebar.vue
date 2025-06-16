<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    PackageSearch,
    Warehouse,
    Truck,
    FilePlus2,
    RotateCw,
    Boxes,
    Layers3,
    Users,
    User,
    UserCog,
    KeyRound
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import SidebarDropdown from './SideBarDropdown.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Tổng quan',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                        <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-4 flex-1">
            <!-- Tổng quan -->
            <NavMain :items="mainNavItems" class="space-y-1" />

            <!-- Quản lý hàng hóa -->
            <SidebarDropdown :icon="PackageSearch" label="Quản lý hàng hóa" :items="[
                { label: 'Quản lý sản phẩm', href: route('admin.products.index'), icon: LayoutGrid },
                { label: 'Quản lý danh mục', href: route('admin.categories.index'), icon: PackageSearch },
                { label: 'Quản lý nhà cung cấp', href: route('admin.suppliers.index'), icon: Warehouse }
            ]" class="mt-4" />

            <!-- Quản lý kho hàng -->
            <SidebarDropdown :icon="Warehouse" label="Quản lý kho hàng" :items="[
                { label: 'Quản lý đơn đặt hàng', href: route('admin.purchase-orders.index'), icon: Truck },
                { label: 'Quản lý phiếu nhập hàng', href: route('admin.product-batches.index'), icon: FilePlus2 },
                { label: 'Quản lý phiếu trả hàng', href: route('admin.purchaseReturn.index'), icon: RotateCw },
                { label: 'Quản lý lô hàng', href: route('admin.product-batches.index'), icon: Boxes },
                { label: 'Quản lý tồn kho', href: '#', icon: Layers3 }
            ]" class="mt-2" />

            <SidebarDropdown :icon="Users" label="Quản lý người dùng" :items="[
                { label: 'Quản lý khách hàng', href: '#', icon: User },
                { label: 'Quản lý nhân viên', href: '#', icon: UserCog },
                { label: 'Quản lý tài khoản', href: '#', icon: KeyRound }
            ]" class="mt-2" />

        </SidebarContent>



        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
