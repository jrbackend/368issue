<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register/keyword';

defineOptions({
    layout: {
        title: 'Acesso ao cadastro',
        description: 'Informe a palavra-chave para liberar o cadastro',
    },
});
</script>

<template>
    <Head title="Palavra-chave" />

    <Form
        v-bind="store.form()"
        :reset-on-error="['keyword']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="keyword">Palavra-chave</Label>
                <PasswordInput
                    id="keyword"
                    name="keyword"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="off"
                    placeholder="Digite a palavra-chave"
                />
                <InputError :message="errors.keyword" />
            </div>

            <Button
                type="submit"
                class="w-full"
                :tabindex="2"
                :disabled="processing"
                data-test="register-keyword-button"
            >
                <Spinner v-if="processing" />
                Continuar
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Já tem uma conta?
            <TextLink :href="login()" :tabindex="3">Entrar</TextLink>
        </div>
    </Form>
</template>
