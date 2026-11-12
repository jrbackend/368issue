<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useId } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();

const gradientId = `bet368-logo-${useId()}`;
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        role="img"
        aria-label="bet368"
        :class="className"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient :id="gradientId" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#13593f" />
                <stop offset="1" stop-color="#0b3326" />
            </linearGradient>
        </defs>
        <rect width="64" height="64" rx="14" :fill="`url(#${gradientId})`" />
        <text
            x="32"
            y="39"
            text-anchor="middle"
            font-family="Arial, Helvetica, sans-serif"
            font-size="25"
            font-weight="900"
            font-style="italic"
            letter-spacing="-1"
            fill="#f7d417"
        >
            368
        </text>
        <path
            d="M13 47 C 27 52, 45 50, 53 40"
            fill="none"
            stroke="#f7d417"
            stroke-width="3.5"
            stroke-linecap="round"
        />
    </svg>
</template>
