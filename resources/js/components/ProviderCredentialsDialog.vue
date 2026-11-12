<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck } from '@lucide/vue';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export type CredentialField = {
    name: string;
    label: string;
    type: 'text' | 'email' | 'password';
    secret: boolean;
    placeholder: string;
    help: string | null;
    value: string;
};

export type ProviderOption = {
    key: string;
    label: string;
    configured: boolean;
    fields: CredentialField[];
};

const props = defineProps<{
    provider: ProviderOption;
    updateUrl: string;
}>();

const open = defineModel<boolean>('open', { required: true });

function initialValues(provider: ProviderOption): Record<string, string> {
    return Object.fromEntries(
        provider.fields.map((field) => [field.name, field.value]),
    );
}

const form = useForm<Record<string, string>>(initialValues(props.provider));

watch(open, (isOpen) => {
    if (isOpen) {
        form.defaults(initialValues(props.provider));
        form.reset();
        form.clearErrors();
    }
});

function submit(): void {
    form.put(props.updateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="grid gap-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <KeyRound class="size-5" />
                        Credenciais do {{ provider.label }}
                    </DialogTitle>
                    <DialogDescription>
                        As credenciais são verificadas com o serviço e
                        guardadas criptografadas.
                        <template v-if="provider.configured">
                            Deixe os campos secretos em branco para manter os
                            valores atuais.
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div
                        v-for="field in provider.fields"
                        :key="field.name"
                        class="grid gap-2"
                    >
                        <Label :for="`credential-${field.name}`">
                            {{ field.label }}
                        </Label>
                        <PasswordInput
                            v-if="field.type === 'password'"
                            :id="`credential-${field.name}`"
                            v-model="form[field.name]"
                            autocomplete="off"
                            :placeholder="
                                provider.configured && field.secret
                                    ? '••••••••  (manter atual)'
                                    : field.placeholder
                            "
                        />
                        <Input
                            v-else
                            :id="`credential-${field.name}`"
                            v-model="form[field.name]"
                            :type="field.type"
                            :placeholder="field.placeholder"
                        />
                        <p v-if="field.help" class="text-xs text-muted-foreground">
                            {{ field.help }}
                        </p>
                        <InputError :message="form.errors[field.name]" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        <ShieldCheck v-else />
                        Verificar e salvar
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
