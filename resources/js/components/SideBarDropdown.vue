<script setup lang="ts">
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu'
import { SidebarMenuItem, SidebarMenuButton } from '@/components/ui/sidebar'
import { Link } from '@inertiajs/vue3'
import { PackageSearch } from 'lucide-vue-next' // hoặc icon bạn muốn

defineProps<{
  icon: any, // truyền component icon
  label: string,
  items: Array<{
    label: string,
    href: string
  }>
}>()
</script>

<template>
  <SidebarMenuItem class="px-2 py-0">
    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <SidebarMenuButton>
          <PackageSearch class="mr-2" />
          {{ label }}
        </SidebarMenuButton>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="start">
        <DropdownMenuItem v-for="item in items" :key="item.href" as-child>
          <Link :href="item.href">{{ item.label }}</Link>
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