<script setup>
import { reactiveOmit } from "@vueuse/core";
import { TabsList, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
    asChild: { type: Boolean, required: false },
    as: { type: null, required: false },
    loop: { type: Boolean, required: false },
    class: { type: null, required: false },
});

const delegatedProps = reactiveOmit(props, "class");
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <TabsList data-slot="tabs-list" v-bind="forwardedProps" :class="cn(
        'bg-muted text-muted-foreground inline-flex h-10 w-full items-center justify-center rounded-md p-1',
        props.class,
    )
        ">
        <slot />
    </TabsList>
</template>
