<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    // Cập nhật để cho phép user là null hoặc undefined
    user?: User | null;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    user: null, // Thêm giá trị mặc định cho user
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
// Kiểm tra user có tồn tại trước khi truy cập các thuộc tính của nó
const showAvatar = computed(() => props.user && props.user.avatar && props.user.avatar !== '');

const userName = computed(() => props.user?.name || 'User');
</script>

<template>
    <div class="flex items-center space-x-2">
        <template v-if="props.user">
            <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                <AvatarImage v-if="showAvatar" :src="props.user.avatar" :alt="props.user.name" />
                <AvatarFallback class="rounded-lg text-black dark:text-white">
                    {{ getInitials(props.user.name) }}
                </AvatarFallback>
            </Avatar>

            <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-medium">{{ props.user.name }}</span>
                <span v-if="showEmail" class="text-muted-foreground truncate text-xs">{{ props.user.email }}</span>
            </div>
        </template>
        <template v-else>
            <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                <AvatarFallback class="rounded-lg text-black dark:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
                    </svg>
                </AvatarFallback>
            </Avatar>
            <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-medium">Người dùng không xác định</span>
            </div>
        </template>
    </div>
</template>
