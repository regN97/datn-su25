<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { FilePlus2, KeyRound, Layers3, LayoutGrid, PackageSearch, RotateCw, Truck, User, UserCog, Users, Warehouse, PencilRuler, TrendingUp } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import SidebarDropdown from './SideBarDropdown.vue';

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

        <SidebarContent class="flex-1 px-4">
            <!-- Tổng quan -->
            <div
                class="mt-2 flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase sidebar-section-label">
                <LayoutGrid class="w-4 h-4" />
                <span class="sidebar-section-text">Tổng quan</span>
            </div>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="/dashboard" class="flex items-center gap-2">
                        <LayoutGrid class="w-4 h-4" />
                        Tổng quan nhập hàng
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="/test" class="flex items-center gap-2">
                        <TrendingUp class="w-4 h-4" />
                        Thống kê thử nghiệm
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Quản lý hàng hóa -->
            <div
                class="mt-4 flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase sidebar-section-label">
                <PackageSearch class="w-4 h-4" />
                <span class="sidebar-section-text">Quản lý hàng hóa</span>
            </div>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.products.index')" class="flex items-center gap-2">
                        <LayoutGrid class="w-4 h-4" />
                        Quản lý sản phẩm
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.categories.index')" class="flex items-center gap-2">
                        <PackageSearch class="w-4 h-4" />
                        Quản lý danh mục
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.suppliers.index')" class="flex items-center gap-2">
                        <Warehouse class="w-4 h-4" />
                        Quản lý nhà cung cấp
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.units.index')" class="flex items-center gap-2">
                        <PencilRuler class="w-4 h-4" />
                        Quản lý đơn vị tính
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Quản lý kho hàng -->
            <div
                class="mt-4 flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase sidebar-section-label">
                <Warehouse class="w-4 h-4" />
                <span class="sidebar-section-text">Quản lý kho hàng</span>
            </div>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.purchase-orders.index')" class="flex items-center gap-2">
                        <Truck class="w-4 h-4" />
                        Quản lý đơn đặt hàng
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.batches.index')" class="flex items-center gap-2">
                        <FilePlus2 class="w-4 h-4" />
                        Quản lý phiếu nhập hàng
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.purchaseReturn.index')" class="flex items-center gap-2">
                        <RotateCw class="w-4 h-4" />
                        Quản lý phiếu trả hàng
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <!-- <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.product-batches.index')" class="flex items-center gap-2">
                            <Boxes class="w-4 h-4" />
                            Quản lý lô hàng
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem> -->
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.inventory.index')" class="flex items-center gap-2">
                        <Layers3 class="w-4 h-4" />
                        Quản lý tồn kho
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Quản lý người dùng -->
             <div
                class="mt-4 flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase sidebar-section-label">
                <User class="w-4 h-4" />
                <span class="sidebar-section-text">Quản lý người dùng</span>
            </div>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.customers.index')" class="flex items-center gap-2">
                        <User class="w-4 h-4" />
                        Quản lý khách hàng
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link :href="route('admin.users.index')" class="flex items-center gap-2">
                        <UserCog class="w-4 h-4" />
                        Quản lý nhân viên
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <!-- <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="#" class="flex items-center gap-2">
                            <KeyRound class="w-4 h-4" />
                            Quản lý tài khoản
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem> -->
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
