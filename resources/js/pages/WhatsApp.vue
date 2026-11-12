<script setup lang="ts">
import { Head, useForm, usePoll } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    CheckCheck,
    CheckCircle2,
    Clock,
    Copy,
    KeyRound,
    RotateCcw,
    Send,
    Settings2,
    Webhook,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import ProviderCredentialsDialog from '@/components/ProviderCredentialsDialog.vue';
import type { ProviderOption } from '@/components/ProviderCredentialsDialog.vue';
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
import { index } from '@/routes/whatsapp';
import { store } from '@/routes/whatsapp/campaigns';
import { update as updateProviderCredentials } from '@/routes/whatsapp/providers';

type WhatsAppProviderOption = ProviderOption & {
    webhookUrl: string | null;
};

type Stats = {
    sent: number;
    queued: number;
    failed: number;
    delivered: number;
    read: number;
};

type RecentCampaign = {
    id: number;
    message: string;
    provider: string;
    recipients_count: number;
    sent_count: number;
    failed_count: number;
    read_count: number;
    created_at: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Disparo de WhatsApp',
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    providers: WhatsAppProviderOption[];
    maxRecipients: number;
    defaultMessage: string;
    secondsBetweenMessages: number;
    stats: Stats;
    recentCampaigns: RecentCampaign[];
}>();

usePoll(5000, { only: ['stats', 'recentCampaigns'] });

const MAX_MESSAGE_LENGTH = 4000;
const DEFAULT_COUNTRY_CODE = '55';
const phonesPlaceholder = ['11 99999-1234', '(21) 98888-5678', '+351 912 345 678'].join('\n');

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
    message: props.defaultMessage,
    phones: '',
});

watch(selectedProviderKey, (providerKey) => {
    form.provider = providerKey ?? '';
});

/**
 * Mirrors the server-side parsing: numbers with "+"/"00" keep their country code,
 * 10/11-digit numbers are considered Brazilian.
 */
const parsedPhones = computed(() => {
    const valid = new Set<string>();
    let invalid = 0;

    for (const rawLine of form.phones.split(/[\r\n,;]+/)) {
        const line = rawLine.trim();

        if (line === '') {
            continue;
        }

        let digits = line.replace(/\D/g, '');
        const hasCountryCode = line.startsWith('+') || line.startsWith('00');

        if (line.startsWith('00')) {
            digits = digits.slice(2);
        }

        if (!hasCountryCode && (digits.length === 10 || digits.length === 11)) {
            digits = DEFAULT_COUNTRY_CODE + digits;
        }

        if (/^[1-9]\d{9,14}$/.test(digits) && /^[\d\s().+-]+$/.test(line)) {
            valid.add(digits);
        } else {
            invalid++;
        }
    }

    return { unique: valid.size, invalid };
});

const isOverLimit = computed(() => parsedPhones.value.unique > props.maxRecipients);

const canSend = computed(
    () =>
        selectedProvider.value?.configured === true &&
        parsedPhones.value.unique > 0 &&
        parsedPhones.value.invalid === 0 &&
        !isOverLimit.value &&
        form.message.trim() !== '',
);

function formatDuration(seconds: number): string {
    if (seconds < 60) {
        return `${seconds} s`;
    }

    const minutes = Math.round(seconds / 60);

    return minutes < 60 ? `${minutes} min` : `${Math.floor(minutes / 60)} h ${minutes % 60} min`;
}

const estimatedDuration = computed(() =>
    formatDuration(Math.max(parsedPhones.value.unique - 1, 0) * props.secondsBetweenMessages),
);

function restoreDefaultMessage(): void {
    form.message = props.defaultMessage;
}

const isConfirmDialogOpen = ref(false);

function send(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('phones');
            isConfirmDialogOpen.value = false;
        },
        onError: () => {
            isConfirmDialogOpen.value = false;
        },
    });
}

/*
 * WhatsApp-style preview: *bold*, _italic_, ~strikethrough~ (HTML is escaped first).
 */
function escapeHtml(text: string): string {
    return text
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

const previewHtml = computed(() =>
    escapeHtml(form.message)
        .replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>')
        .replace(/(^|\s)_([^_\n]+)_(?=\s|$)/g, '$1<em>$2</em>')
        .replace(/~([^~\n]+)~/g, '<s>$1</s>')
        .replace(/(https?:\/\/[^\s<]+)/g, '<span class="text-sky-600 underline dark:text-sky-400">$1</span>'),
);

const previewTime = new Intl.DateTimeFormat('pt-BR', { timeStyle: 'short' }).format(new Date());

/*
 * Webhook.
 */
const hasCopiedWebhookUrl = ref(false);

async function copyWebhookUrl(): Promise<void> {
    if (!selectedProvider.value?.webhookUrl) {
        return;
    }

    await navigator.clipboard.writeText(selectedProvider.value.webhookUrl);
    hasCopiedWebhookUrl.value = true;
    setTimeout(() => (hasCopiedWebhookUrl.value = false), 2000);
}

/*
 * Stats.
 */
const numberFormat = new Intl.NumberFormat('pt-BR');
const dateTimeFormat = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
});

const readRate = computed(() =>
    props.stats.sent === 0
        ? '0%'
        : `${Math.round((props.stats.read / props.stats.sent) * 100)}%`,
);

const statCards = computed<
    { label: string; value: string; detail: string; icon: Component; iconClass: string; pulse?: boolean }[]
>(() => [
    {
        label: 'Enviados',
        value: numberFormat.format(props.stats.sent),
        detail: `${numberFormat.format(props.stats.delivered)} entregues no celular`,
        icon: CheckCircle2,
        iconClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    {
        label: 'Na fila',
        value: numberFormat.format(props.stats.queued),
        detail:
            props.stats.queued > 0
                ? `termina em ≈ ${formatDuration(props.stats.queued * props.secondsBetweenMessages)}`
                : 'nada pendente',
        icon: Clock,
        iconClass: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        pulse: props.stats.queued > 0,
    },
    {
        label: 'Lidos',
        value: numberFormat.format(props.stats.read),
        detail: `taxa de leitura de ${readRate.value}`,
        icon: CheckCheck,
        iconClass: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
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
    <Head title="Disparo de WhatsApp" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <!-- Header: title + provider selection -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Disparo de WhatsApp"
                description="Envie o aviso de mudança de domínio para a sua lista de números."
            />

            <div class="flex flex-wrap items-center gap-2">
                <Select v-model="selectedProviderKey">
                    <SelectTrigger class="w-56" aria-label="Serviço de WhatsApp">
                        <SelectValue placeholder="Selecione o serviço de WhatsApp" />
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
                        <CardTitle>Mensagem e números</CardTitle>
                        <CardDescription>
                            Um número por linha. Números sem código do país são
                            considerados do Brasil (+55). Máximo de
                            {{ numberFormat.format(maxRecipients) }} por disparo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            class="grid gap-5"
                            @submit.prevent="isConfirmDialogOpen = true"
                        >
                            <div class="grid gap-2">
                                <div class="flex items-center justify-between gap-2">
                                    <Label for="message">Mensagem</Label>
                                    <Button
                                        v-if="form.message !== defaultMessage"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="h-7 text-xs"
                                        @click="restoreDefaultMessage"
                                    >
                                        <RotateCcw class="size-3.5" />
                                        Restaurar mensagem padrão
                                    </Button>
                                </div>
                                <Textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="11"
                                    :maxlength="MAX_MESSAGE_LENGTH"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Use *negrito*, _itálico_ e ~riscado~ como no
                                    WhatsApp ·
                                    <span class="tabular-nums">
                                        {{ numberFormat.format(form.message.length) }} /
                                        {{ numberFormat.format(MAX_MESSAGE_LENGTH) }}
                                    </span>
                                </p>
                                <InputError :message="form.errors.message" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="phones">Números</Label>
                                <Textarea
                                    id="phones"
                                    v-model="form.phones"
                                    rows="10"
                                    spellcheck="false"
                                    class="font-mono text-sm"
                                    :placeholder="phonesPlaceholder"
                                    :aria-invalid="isOverLimit || parsedPhones.invalid > 0"
                                />
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                                    <span
                                        class="tabular-nums"
                                        :class="isOverLimit ? 'font-semibold text-destructive' : 'text-muted-foreground'"
                                    >
                                        {{ numberFormat.format(parsedPhones.unique) }} /
                                        {{ numberFormat.format(maxRecipients) }} números únicos
                                    </span>
                                    <span
                                        v-if="parsedPhones.invalid > 0"
                                        class="font-semibold text-destructive"
                                    >
                                        {{ parsedPhones.invalid }} inválido(s)
                                    </span>
                                    <span
                                        v-if="parsedPhones.unique > 1"
                                        class="text-muted-foreground"
                                    >
                                        envio em ≈ {{ estimatedDuration }}
                                    </span>
                                </div>
                                <InputError :message="form.errors.phones" />
                                <InputError :message="form.errors.provider" />
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <Button
                                    type="submit"
                                    size="lg"
                                    :disabled="!canSend || form.processing"
                                    data-test="send-whatsapp-campaign-button"
                                >
                                    <Spinner v-if="form.processing" />
                                    <Send v-else />
                                    Disparar
                                </Button>
                                <p
                                    v-if="!selectedProvider?.configured"
                                    class="text-sm text-muted-foreground"
                                >
                                    Selecione e configure um serviço de WhatsApp
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
                                    <th class="px-4 py-2 font-medium">Mensagem</th>
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
                                    <td class="max-w-64 truncate px-4 py-2" :title="campaign.message">
                                        {{ campaign.message }}
                                        <span class="block text-xs text-muted-foreground">
                                            {{ campaign.provider }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap tabular-nums">
                                        {{ numberFormat.format(campaign.sent_count) }} /
                                        {{ numberFormat.format(campaign.recipients_count) }}
                                    </td>
                                    <td class="px-4 py-2 text-right tabular-nums">
                                        {{ numberFormat.format(campaign.read_count) }}
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

            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- WhatsApp preview -->
                <Card class="gap-0 overflow-hidden pb-0">
                    <CardHeader class="pb-4">
                        <CardTitle>Pré-visualização</CardTitle>
                        <CardDescription>Como a mensagem aparece no WhatsApp.</CardDescription>
                    </CardHeader>
                    <div class="min-h-80 border-t bg-[#efeae2] p-4 dark:bg-[#0b141a]">
                        <div
                            class="relative ml-auto max-w-[90%] rounded-lg rounded-tr-none bg-[#d9fdd3] px-3 pt-2 pb-5 text-[0.9rem] leading-snug text-[#111b21] shadow-sm dark:bg-[#005c4b] dark:text-[#e9edef]"
                        >
                            <p
                                class="break-words whitespace-pre-wrap"
                                v-html="previewHtml || '&nbsp;'"
                            />
                            <span
                                class="absolute right-2 bottom-1 flex items-center gap-1 text-[0.65rem] text-[#667781] dark:text-[#8696a0]"
                            >
                                {{ previewTime }}
                                <CheckCheck class="size-3.5 text-sky-500" />
                            </span>
                        </div>
                    </div>
                </Card>

                <!-- Status webhook -->
                <Card v-if="selectedProvider?.webhookUrl">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Webhook class="size-4" />
                            Webhook de status
                        </CardTitle>
                        <CardDescription>
                            Para contar as mensagens entregues e lidas, cole
                            este endereço no painel da {{ selectedProvider.label }}
                            em <strong>Webhooks → Ao receber status da mensagem</strong>.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex gap-2">
                        <code
                            class="flex-1 truncate rounded-md border bg-muted px-3 py-2 font-mono text-xs"
                            :title="selectedProvider.webhookUrl"
                        >
                            {{ selectedProvider.webhookUrl }}
                        </code>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            :aria-label="hasCopiedWebhookUrl ? 'Copiado' : 'Copiar endereço do webhook'"
                            @click="copyWebhookUrl"
                        >
                            <Check v-if="hasCopiedWebhookUrl" class="text-emerald-600" />
                            <Copy v-else />
                        </Button>
                    </CardContent>
                </Card>
            </div>
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
                        A mensagem será enviada para
                        <strong class="text-foreground">
                            {{ numberFormat.format(parsedPhones.unique) }} número(s)
                        </strong>
                        usando a {{ selectedProvider?.label }}, uma a cada
                        {{ secondsBetweenMessages }} segundos (≈ {{ estimatedDuration }}).
                        Essa ação não pode ser desfeita.
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
