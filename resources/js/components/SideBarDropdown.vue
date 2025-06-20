<script setup lang="ts">
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';

defineProps<{
    icon: any; // truyền component icon
    label: string;
    items: Array<{
        label: string;
        href: string;
        icon?: any;
    }>;
}>();
</script>

<template>
    <SidebarMenuItem class="px-2 py-0">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <SidebarMenuButton>
                    <component :is="icon" class="mr-2 h-4 w-4" />
                    {{ label }}
                </SidebarMenuButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                <DropdownMenuItem v-for="item in items" :key="item.href" as-child>
                    <Link :href="item.href" class="flex items-center gap-2">
                        <component v-if="item.icon" :is="item.icon" class="text-muted-foreground h-4 w-4" />
                        {{ item.label }}
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </SidebarMenuItem>
</template>

<style scoped>
li {
    list-style: none;
}

li::marker {
    content: '';
}
</style>
