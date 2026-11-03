<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User | null;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Simplification de la logique de vérification de l'avatar
const hasAvatar = computed(() => !!props.user?.avatar?.trim());
</script>

<template>
    <div v-if="user" class="flex items-center gap-2">
        <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
            <AvatarImage
                v-if="hasAvatar"
                :src="user.avatar ?? ''"
                :alt="user.name"
            />
            <AvatarFallback class="rounded-lg text-black dark:text-white">
                {{ getInitials(user.name) }}
            </AvatarFallback>
        </Avatar>

        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-medium">{{ user.name }}</span>
            <span
                v-if="showEmail"
                class="truncate text-xs text-muted-foreground"
            >
                {{ user.email }}
            </span>
        </div>
    </div>
</template>
