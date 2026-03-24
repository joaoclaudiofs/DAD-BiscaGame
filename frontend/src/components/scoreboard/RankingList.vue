<template>
    <div class="space-y-3">
        <Card v-if="userPosition && !loading" class="bg-primary/5 border-primary/20">
            <CardContent class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <MapPin class="h-4 w-4 text-primary" />
                        <span class="text-sm font-medium">A tua posição</span>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold text-primary">#{{ userPosition.rank }}</span>
                        <span class="text-xs text-muted-foreground"> de {{ userPosition.total }}</span>
                        <p class="text-xs text-muted-foreground">{{ formatValue(userPosition.value) }} {{ suffix }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base flex items-center gap-2">
                    <Trophy v-if="icon === 'trophy'" class="h-5 w-5 text-amber-500" />
                    <FlagTriangleRight v-else-if="icon === 'flagtriangleright'" class="h-5 w-5 text-blue-500" />
                    <Club v-else-if="icon === 'club'" class="h-5 w-5 text-red-500" />
                    <Coins v-else-if="icon === 'coins'" class="h-5 w-5 text-yellow-500" />
                    <Award v-else class="h-5 w-5 text-purple-500" />
                    {{ title }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="loading" class="space-y-2">
                    <div v-for="i in 5" :key="i" class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                        <Skeleton class="h-8 w-8 rounded-full" />
                        <Skeleton class="h-10 w-10 rounded-full" />
                        <div class="flex-1">
                            <Skeleton class="h-4 w-24" />
                        </div>
                        <div class="text-right">
                            <Skeleton class="h-4 w-12 mb-1" />
                            <Skeleton class="h-3 w-8" />
                        </div>
                    </div>
                </div>

                <div v-else-if="items.length === 0" class="text-center py-8 text-muted-foreground">
                    <Trophy class="h-10 w-10 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Ainda sem rankings</p>
                </div>

                <div v-else class="space-y-2">
                    <div v-for="item in items" :key="item.id"
                        class="flex items-center gap-3 p-3 rounded-lg transition-colors hover:bg-muted/70" :class="[
                            item.id === currentUserId ? 'bg-primary/10 border border-primary/30' : 'bg-muted/50',
                            item.rank <= 3 ? 'font-medium' : ''
                        ]">

                        <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm"
                            :class="getRankClass(item.rank)">
                            <span>{{ item.rank }}</span>
                        </div>

                        <Avatar v-if="item.avatarUrl" class="h-10 w-10 rounded-full overflow-hidden border-2" :class="[
                            item.rank === 1 ? 'border-amber-400' :
                            item.rank === 2 ? 'border-gray-300' :
                            item.rank === 3 ? 'border-amber-600' : 'border-transparent'
                        ]">
                            <img :src="item.avatarUrl" :alt="item.name" class="h-full w-full object-cover" />
                        </Avatar>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">
                                {{ item.name }}
                                <span v-if="item.id === currentUserId" class="text-primary text-xs">(Tu)</span>
                            </p>
                            <p v-if="hasExtras(item)" class="text-[11px] text-muted-foreground">
                                {{ item.bandeiras ?? 0 }} bandeiras - {{ item.capotes ?? 0 }} capotes
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p class="font-semibold" :class="getValueClass(item.rank)">
                                {{ formatValue(item.value) }}
                            </p>
                            <p class="text-xs text-muted-foreground">{{ suffix }}</p>
                        </div>
                    </div>

                    <template v-if="userPosition && !isUserInList">
                        <div class="flex items-center gap-2 py-2">
                            <div class="flex-1 border-t border-dashed"></div>
                            <span class="text-xs text-muted-foreground">...</span>
                            <div class="flex-1 border-t border-dashed"></div>
                        </div>

                        <div class="flex items-center gap-3 p-3 rounded-lg bg-primary/10 border border-primary/30">
                            <div
                                class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm bg-muted text-muted-foreground">
                                {{ userPosition.rank }}
                            </div>
                            <Avatar v-if="userPosition.avatarUrl" class="h-10 w-10 rounded-full overflow-hidden border-2 border-primary/30">
                                <img :src="userPosition.avatarUrl" :alt="userPosition.name"
                                    class="h-full w-full object-cover" />
                            </Avatar>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">
                                    {{ userPosition.name }}
                                    <span class="text-primary text-xs">(Tu)</span>
                                </p>
                                <p v-if="hasExtras(userPosition)" class="text-[11px] text-muted-foreground">
                                    {{ userPosition.bandeiras ?? 0 }} bandeiras - {{ userPosition.capotes ?? 0 }} capotes
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-semibold">{{ formatValue(userPosition.value) }}</p>
                                <p class="text-xs text-muted-foreground">{{ suffix }}</p>
                            </div>
                        </div>
                    </template>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Skeleton } from '@/components/ui/skeleton'
import { Trophy, Coins, Award, MapPin, FlagTriangleRight, Club } from 'lucide-vue-next'

const props = defineProps({
    items: {
        type: Array,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    icon: {
        type: String,
        default: 'trophy'
    },
    suffix: {
        type: String,
        default: ''
    },
    currentUserId: {
        type: Number,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    },
    userPosition: {
        type: Object,
        default: null
    }
})

const isUserInList = computed(() => {
    if (!props.currentUserId) return false
    return props.items.some(item => item.id === props.currentUserId)
})

function getRankClass(rank) {
    switch (rank) {
        case 1:
            return 'bg-amber-400 text-amber-900'
        case 2:
            return 'bg-gray-300 text-gray-700'
        case 3:
            return 'bg-amber-600 text-amber-100'
        default:
            return 'bg-muted text-muted-foreground'
    }
}

function getValueClass(rank) {
    switch (rank) {
        case 1:
            return 'text-amber-500'
        case 2:
            return 'text-gray-500'
        case 3:
            return 'text-amber-700'
        default:
            return ''
    }
}

function hasExtras(item) {
    return item && (item.bandeiras !== undefined || item.capotes !== undefined)
}

function formatValue(value) {
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M'
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(1) + 'K'
    }
    return value.toString()
}
</script>
