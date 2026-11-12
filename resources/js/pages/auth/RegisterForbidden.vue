<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ShieldX } from '@lucide/vue';
import TextLink from '@/components/TextLink.vue';
import { keyword } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Acesso negado',
        description: 'A palavra-chave informada não é válida',
    },
});
</script>

<template>
    <Head title="Acesso negado" />

    <div class="flex flex-col items-center gap-6 text-center">
        <span
            class="flex size-14 items-center justify-center rounded-full bg-destructive/10 text-destructive"
        >
            <ShieldX class="size-7" />
        </span>

        <p class="text-sm text-muted-foreground">
            Você não tem permissão para se cadastrar. Verifique a palavra-chave
            com o responsável e tente novamente.
        </p>

        <TextLink :href="keyword()" class="text-sm">Tentar novamente</TextLink>
    </div>
</template>
