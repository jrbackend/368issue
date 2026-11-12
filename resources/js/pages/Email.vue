<script setup lang="ts">
import { Head, useForm, usePoll } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    KeyRound,
    MailOpen,
    Send,
    Settings2,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import ProviderCredentialsDialog from '@/components/ProviderCredentialsDialog.vue';
import type { ProviderOption } from '@/components/ProviderCredentialsDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/email';
import { store } from '@/routes/email/campaigns';
import { update as updateProviderCredentials } from '@/routes/email/providers';

type Stats = {
    sent: number;
    queued: number;
    failed: number;
    opened: number;
};

type RecentCampaign = {
    id: number;
    subject: string;
    provider: string;
    recipients_count: number;
    sent_count: number;
    failed_count: number;
    opened_count: number;
    created_at: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Disparo de E-mail',
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    providers: ProviderOption[];
    maxRecipients: number;
    defaultSubject: string;
    previewHtml: string;
    stats: Stats;
    recentCampaigns: RecentCampaign[];
}>();

usePoll(5000, { only: ['stats', 'recentCampaigns'] });

const emailsPlaceholder = ['joao@exemplo.com', 'maria@exemplo.com', 'ana@exemplo.com'].join('\n');

/*
 * Provider selection: pre-select a configured provider; selecting one without
 * stored credentials opens the credentials dialog.
 */
const selectedProviderKey = ref<string | undefined>(
    props.providers.find((provider) => provider.configured)?.key,
);
const isCredentialsDialogOpen = ref(false);

const selectedProvider = computed(() =>
    props.providers.find((provider) => provider.key === selectedProviderKey.value),
);

watch(selectedProviderKey, () => {
    if (selectedProvider.value && !selectedProvider.value.configured) {
        isCredentialsDialogOpen.value = true;
    }
});

/*
 * Campaign form.
 */
const form = useForm({
    provider: selectedProviderKey.value ?? '',
    subject: props.defaultSubject,
    emails: '',
});

watch(selectedProviderKey, (providerKey) => {
    form.provider = providerKey ?? '';
});

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const parsedAddresses = computed(() => {
    const addresses = [
        ...new Set(
            form.emails
                .split(/[\r\n,;]+/)
                .map((line) => line.trim().toLowerCase())
                .filter((line) => line !== ''),
        ),
    ];

    return {
        unique: addresses.length,
        invalid: addresses.filter((address) => !EMAIL_PATTERN.test(address)).length,
    };
});

const isOverLimit = computed(() => parsedAddresses.value.unique > props.maxRecipients);

const canSend = computed(
    () =>
        selectedProvider.value?.configured === true &&
        parsedAddresses.value.unique > 0 &&
        parsedAddresses.value.invalid === 0 &&
        !isOverLimit.value &&
        form.subject.trim() !== '',
);

const isConfirmDialogOpen = ref(false);

function send(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('emails');
            isConfirmDialogOpen.value = false;
        },
        onError: () => {
            isConfirmDialogOpen.value = false;
        },
    });
}

/*
 * Stats.
 */
const numberFormat = new Intl.NumberFormat('pt-BR');
const dateTimeFormat = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
});

const openRate = computed(() =>
    props.stats.sent === 0
        ? '0%'
        : `${Math.round((props.stats.opened / props.stats.sent) * 100)}%`,
);

const statCards = computed<
    { label: string; value: string; detail: string; icon: Component; iconClass: string; pulse?: boolean }[]
>(() => [
    {
        label: 'Enviados',
        value: numberFormat.format(props.stats.sent),
        detail: 'entregues ao serviço de e-mail',
        icon: CheckCircle2,
        iconClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    {
        label: 'Na fila',
        value: numberFormat.format(props.stats.queued),
        detail: props.stats.queued > 0 ? 'enviando agora…' : 'nada pendente',
        icon: Clock,
        iconClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        pulse: props.stats.queued > 0,
    },
    {
        label: 'Lidos',
        value: numberFormat.format(props.stats.opened),
        detail: `taxa de abertura de ${openRate.value}`,
        icon: MailOpen,
        iconClass: 'bg-primary/10 text-primary',
    },
    {
        label: 'Falhas',
        value: numberFormat.format(props.stats.failed),
        detail: 'não puderam ser enviados',
        icon: AlertTriangle,
        iconClass: 'bg-destructive/10 text-destructive',
    },
]);
</script>

<template>
    <Head title="Disparo de E-mail" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <!-- Header: title + provider selection -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Disparo de E-mail"
                description="Envie o aviso de mudança de domínio para a sua lista de e-mails."
            />

            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="selectedProviderKey">
                    <SelectTrigger class="w-56" aria-label="Serviço de e-mail">
                        <SelectValue placeholder="Selecione o serviço de e-mail" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="provider in providers"
                            :key="provider.key"
                            :value="provider.key"
                        >
                            {{ provider.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <template v-if="selectedProvider">
                    <Badge
                        v-if="selectedProvider.configured"
                        class="border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400"
                    >
                        <CheckCircle2 />
                        Configurado
                    </Badge>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="isCredentialsDialogOpen = true"
                    >
                        <Settings2 v-if="selectedProvider.configured" />
                        <KeyRound v-else />
                        {{
                            selectedProvider.configured
                                ? 'Credenciais'
                                : 'Configurar credenciais'
                        }}
                    </Button>
                </template>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card v-for="card in statCards" :key="card.label" class="py-4">
                <CardContent class="flex items-center gap-4 px-4">
                    <span
                        class="relative flex size-10 shrink-0 items-center justify-center rounded-lg"
                        :class="card.iconClass"
                    >
                        <component :is="card.icon" class="size-5" />
                        <span
                            v-if="card.pulse"
                            class="absolute -top-0.5 -right-0.5 size-2.5 rounded-full bg-amber-500 motion-safe:animate-ping"
                        />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-muted-foreground">
                            {{ card.label }}
                        </p>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ card.value }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ card.detail }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="flex flex-col gap-6 lg:col-span-3">
                <!-- Campaign form -->
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de e-mails</CardTitle>
                        <CardDescription>
                            Um e-mail por linha. Duplicados são removidos
                            automaticamente. Máximo de
                            {{ numberFormat.format(maxRecipients) }} por disparo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            class="grid gap-5"
                            @submit.prevent="isConfirmDialogOpen = true"
                        >
                            <div class="grid gap-2">
                                <Label for="subject">Assunto</Label>
                                <Input id="subject" v-model="form.subject" maxlength="150" />
                                <InputError :message="form.errors.subject" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="emails">E-mails</Label>
                                <Textarea
                                    id="emails"
                                    v-model="form.emails"
                                    rows="14"
                                    spellcheck="false"
                                    class="font-mono text-sm"
                                    :placeholder="emailsPlaceholder"
                                    :aria-invalid="isOverLimit || parsedAddresses.invalid > 0"
                                />
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                                    <span
                                        class="tabular-nums"
                                        :class="isOverLimit ? 'font-semibold text-destructive' : 'text-muted-foreground'"
                                    >
                                        {{ numberFormat.format(parsedAddresses.unique) }} /
                                        {{ numberFormat.format(maxRecipients) }} e-mails únicos
                                    </span>
                                    <span
                                        v-if="parsedAddresses.invalid > 0"
                                        class="font-semibold text-destructive"
                                    >
                                        {{ parsedAddresses.invalid }} inválido(s)
                                    </span>
                                </div>
                                <InputError :message="form.errors.emails" />
                                <InputError :message="form.errors.provider" />
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <Button
                                    type="submit"
                                    size="lg"
                                    :disabled="!canSend || form.processing"
                                    data-test="send-campaign-button"
                                >
                                    <Spinner v-if="form.processing" />
                                    <Send v-else />
                                    Disparar
                                </Button>
                                <p
                                    v-if="!selectedProvider?.configured"
                                    class="text-sm text-muted-foreground"
                                >
                                    Selecione e configure um serviço de e-mail
                                    para disparar.
                                </p>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Recent campaigns -->
                <Card v-if="recentCampaigns.length > 0" class="gap-0 pb-0">
                    <CardHeader class="pb-4">
                        <CardTitle>Últimos disparos</CardTitle>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-y bg-muted/50 text-left text-xs text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Data</th>
                                    <th class="px-4 py-2 font-medium">Assunto</th>
                                    <th class="px-4 py-2 text-right font-medium">Enviados</th>
                                    <th class="px-4 py-2 text-right font-medium">Lidos</th>
                                    <th class="px-4 py-2 text-right font-medium">Falhas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="campaign in recentCampaigns" :key="campaign.id">
                                    <td class="px-4 py-2 whitespace-nowrap tabular-nums">
                                        {{ campaign.created_at ? dateTimeFormat.format(new Date(campaign.created_at)) : '—' }}
                                    </td>
                                    <td class="max-w-64 truncate px-4 py-2" :title="campaign.subject">
                                        {{ campaign.subject }}
                                        <span class="block text-xs text-muted-foreground">
                                            {{ campaign.provider }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap tabular-nums">
                                        {{ numberFormat.format(campaign.sent_count) }} /
                                        {{ numberFormat.format(campaign.recipients_count) }}
                                    </td>
                                    <td class="px-4 py-2 text-right tabular-nums">
                                        {{ numberFormat.format(campaign.opened_count) }}
                                    </td>
                                    <td
                                        class="px-4 py-2 text-right tabular-nums"
                                        :class="{ 'font-semibold text-destructive': campaign.failed_count > 0 }"
                                    >
                                        {{ numberFormat.format(campaign.failed_count) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>

            <!-- E-mail preview -->
            <Card class="gap-0 overflow-hidden pb-0 lg:col-span-2">
                <CardHeader class="pb-4">
                    <CardTitle>Pré-visualização</CardTitle>
                    <CardDescription>{{ form.subject || 'Sem assunto' }}</CardDescription>
                </CardHeader>
                <iframe
                    :srcdoc="previewHtml"
                    sandbox=""
                    title="Pré-visualização do e-mail"
                    class="h-[720px] w-full border-t bg-[#171717]"
                />
            </Card>
        </div>

        <ProviderCredentialsDialog
            v-if="selectedProvider"
            v-model:open="isCredentialsDialogOpen"
            :provider="selectedProvider"
            :update-url="updateProviderCredentials.url(selectedProvider.key)"
        />

        <!-- Send confirmation -->
        <Dialog v-model:open="isConfirmDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Confirmar disparo</DialogTitle>
                    <DialogDescription>
                        O aviso de mudança de domínio será enviado para
                        <strong class="text-foreground">
                            {{ numberFormat.format(parsedAddresses.unique) }} e-mail(s)
                        </strong>
                        usando o {{ selectedProvider?.label }}. Essa ação não
                        pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmDialogOpen = false">
                        Cancelar
                    </Button>
                    <Button :disabled="form.processing" @click="send">
                        <Spinner v-if="form.processing" />
                        <Send v-else />
                        Disparar agora
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
