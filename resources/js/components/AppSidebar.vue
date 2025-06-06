<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, PackageSearch } from 'lucide-vue-next';
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

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <SidebarDropdown
                :icon="PackageSearch"
                label="Quản lý hàng hóa"
                :items="[
                    { label: 'Quản lý sản phẩm', href: route('admin.products.index') },
                    { label: 'Quản lý danh mục', href: route('admin.categories.index') },
                    { label: 'Quản lý nhà cung cấp', href: route('admin.suppliers.index') },
                    { label: 'Quản lý phiếu trả hàng', href: route('admin.purchaseReturn.index') },
                ]"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
