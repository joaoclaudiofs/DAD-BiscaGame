<script setup>
import { reactiveOmit } from "@vueuse/core";
import { TabsContent, useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
    value: { type: String, required: true },
    forceMount: { type: Boolean, required: false },
    asChild: { type: Boolean, required: false },
    as: { type: null, required: false },
    class: { type: null, required: false },
});

const delegatedProps = reactiveOmit(props, "class");
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <TabsContent data-slot="tabs-content" v-bind="forwardedProps" :class="cn('mt-4 outline-none', props.class)">
        <slot />
    </TabsContent>
</template>
