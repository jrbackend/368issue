<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ShieldX } from '@lucide/vue';
</script>

<template>
    <Head title="Conteúdo indisponível">
        <meta name="robots" content="noindex, nofollow" />
        <meta name="theme-color" content="#0b3326" />
    </Head>

    <div
        class="relative isolate flex min-h-svh flex-col items-center justify-center overflow-hidden bg-bet-ink px-6 text-center text-white"
    >
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
            <div
                class="absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-bet-green/40 blur-3xl"
            />
        </div>

        <span
            class="mb-8 text-3xl font-extrabold tracking-tight italic"
            aria-label="bet368"
        >
            <span class="text-white">bet</span><span class="text-bet-yellow">368</span>
        </span>

        <span
            class="mb-6 flex size-16 items-center justify-center rounded-full border border-bet-yellow/30 bg-bet-yellow/10 text-bet-yellow"
        >
            <ShieldX class="size-8" />
        </span>

        <h1 class="max-w-xl text-3xl font-bold tracking-tight text-balance sm:text-4xl">
            Conteúdo indisponível
        </h1>
        <p class="mt-4 max-w-md text-base text-pretty text-white/60">
            Este conteúdo não está disponível para o seu dispositivo, idioma ou
            região.
        </p>
    </div>
</template>
