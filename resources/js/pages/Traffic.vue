<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Ban,
    CornerUpRight,
    Globe,
    Languages,
    Monitor,
    ShieldCheck,
    Smartphone,
    Tablet,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TrafficOptionPicker from '@/components/TrafficOptionPicker.vue';
import type { PickerOption } from '@/components/TrafficOptionPicker.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit, update } from '@/routes/traffic';

type BlockedAction = 'block' | 'redirect';

type TrafficRule = {
    is_enabled: boolean;
    allowed_devices: string[];
    allowed_languages: string[];
    allowed_countries: string[];
    allow_unknown_country: boolean;
    blocked_action: BlockedAction;
    blocked_redirect_url: string | null;
};

type TrafficRuleForm = Omit<TrafficRule, 'blocked_redirect_url'> & {
    blocked_redirect_url: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Controle de tráfego',
                href: edit(),
            },
        ],
    },
});

const props = defineProps<{
    trafficRule: TrafficRule;
    deviceOptions: string[];
    languageOptions: string[];
    countryOptions: string[];
}>();

/**
 * Copy the rule so editing the form never mutates the page props.
 */
function copyTrafficRule(trafficRule: TrafficRule): TrafficRuleForm {
    return {
        ...trafficRule,
        allowed_devices: [...trafficRule.allowed_devices],
        allowed_languages: [...trafficRule.allowed_languages],
        allowed_countries: [...trafficRule.allowed_countries],
        blocked_redirect_url: trafficRule.blocked_redirect_url ?? '',
    };
}

const form = useForm<TrafficRuleForm>(copyTrafficRule(props.trafficRule));

/**
 * Keep the form in sync with the saved values after each update.
 */
watch(
    () => props.trafficRule,
    (trafficRule) => {
        form.defaults(copyTrafficRule(trafficRule));
        form.reset();
    },
);

const redirectUrlPlaceholder = 'https://exemplo.com';

const deviceDetails: Record<string, { label: string; icon: Component }> = {
    desktop: { label: 'Computador', icon: Monitor },
    mobile: { label: 'Celular', icon: Smartphone },
    tablet: { label: 'Tablet', icon: Tablet },
};

const blockedActions: {
    value: BlockedAction;
    title: string;
    description: string;
    icon: Component;
}[] = [
    {
        value: 'block',
        title: 'Bloquear acesso',
        description: 'Mostra a página "Conteúdo indisponível" (HTTP 403).',
        icon: Ban,
    },
    {
        value: 'redirect',
        title: 'Redirecionar',
        description: 'Envia o visitante para outro endereço.',
        icon: CornerUpRight,
    },
];

const languageNames = new Intl.DisplayNames(['pt-BR'], { type: 'language' });
const regionNames = new Intl.DisplayNames(['pt-BR'], { type: 'region' });

function capitalize(text: string): string {
    return text.charAt(0).toLocaleUpperCase('pt-BR') + text.slice(1);
}

function countryFlag(countryCode: string): string {
    return String.fromCodePoint(
        ...[...countryCode].map((letter) => 0x1f1e6 + letter.charCodeAt(0) - 65),
    );
}

function byLabel(first: PickerOption, second: PickerOption): number {
    return first.label.localeCompare(second.label, 'pt-BR');
}

const languagePickerOptions = computed<PickerOption[]>(() =>
    props.languageOptions
        .map((code) => ({
            value: code,
            label: capitalize(languageNames.of(code) ?? code),
        }))
        .sort(byLabel),
);

const countryPickerOptions = computed<PickerOption[]>(() =>
    props.countryOptions
        .map((code) => ({
            value: code,
            label: regionNames.of(code) ?? code,
            prefix: countryFlag(code),
        }))
        .sort(byLabel),
);

function toggleDevice(device: string): void {
    form.allowed_devices = form.allowed_devices.includes(device)
        ? form.allowed_devices.filter((selected) => selected !== device)
        : [...form.allowed_devices, device];
}

function describeSelection(selected: string[], emptyLabel: string): string {
    return selected.length === 0 ? emptyLabel : `${selected.length} selecionado(s)`;
}

const summary = computed(() => [
    {
        icon: Monitor,
        label: 'Dispositivos',
        value:
            form.allowed_devices.length === 0
                ? 'Todos'
                : form.allowed_devices
                      .map((device) => deviceDetails[device]?.label ?? device)
                      .join(', '),
    },
    {
        icon: Languages,
        label: 'Idiomas',
        value: describeSelection(form.allowed_languages, 'Todos'),
    },
    {
        icon: Globe,
        label: 'Países',
        value: describeSelection(form.allowed_countries, 'Todos'),
    },
]);

function submit(): void {
    form.put(update.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Controle de tráfego" />

    <form
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6"
        @submit.prevent="submit"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Controle de tráfego"
                description="Defina quais visitantes podem ver a página inicial, por dispositivo, idioma e país."
            />
            <Button
                type="submit"
                :disabled="form.processing"
                data-test="update-traffic-rules-button"
            >
                <Spinner v-if="form.processing" />
                Salvar alterações
            </Button>
        </div>

        <!-- Status -->
        <Card>
            <CardContent class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <Label
                    for="is_enabled"
                    class="flex flex-1 cursor-pointer items-start gap-3"
                >
                    <Checkbox
                        id="is_enabled"
                        class="mt-0.5"
                        :model-value="form.is_enabled"
                        @update:model-value="form.is_enabled = $event === true"
                    />
                    <span class="grid gap-1">
                        <span class="flex items-center gap-2 font-medium">
                            Ativar controle de tráfego
                            <Badge
                                :variant="form.is_enabled ? 'default' : 'secondary'"
                            >
                                {{ form.is_enabled ? 'Ativo' : 'Desativado' }}
                            </Badge>
                        </span>
                        <span class="text-sm font-normal text-muted-foreground">
                            Quando desativado, todos os visitantes veem a
                            página inicial.
                        </span>
                    </span>
                </Label>

                <dl
                    class="grid grid-cols-3 gap-4 rounded-lg border bg-muted/40 px-4 py-3 text-sm sm:min-w-96"
                >
                    <div v-for="item in summary" :key="item.label" class="grid gap-0.5">
                        <dt class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <component :is="item.icon" class="size-3.5" />
                            {{ item.label }}
                        </dt>
                        <dd class="truncate font-medium">{{ item.value }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <div
            class="grid gap-6 transition-opacity lg:grid-cols-2"
            :class="{ 'opacity-60': !form.is_enabled }"
        >
            <!-- Devices -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Monitor class="size-4" />
                        Dispositivos
                    </CardTitle>
                    <CardDescription>
                        Nenhum selecionado = todos os dispositivos podem acessar.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3">
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            v-for="device in deviceOptions"
                            :key="device"
                            type="button"
                            class="flex flex-col items-center gap-2 rounded-lg border p-4 text-sm font-medium transition hover:bg-accent"
                            :class="
                                form.allowed_devices.includes(device)
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : ''
                            "
                            :aria-pressed="form.allowed_devices.includes(device)"
                            @click="toggleDevice(device)"
                        >
                            <component
                                :is="deviceDetails[device]?.icon ?? Monitor"
                                class="size-6"
                            />
                            {{ deviceDetails[device]?.label ?? device }}
                        </button>
                    </div>
                    <InputError :message="form.errors.allowed_devices" />
                </CardContent>
            </Card>

            <!-- Blocked visitors -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShieldCheck class="size-4" />
                        Visitantes bloqueados
                    </CardTitle>
                    <CardDescription>
                        O que acontece com quem não atende às regras.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-3">
                    <div class="grid gap-3 sm:grid-cols-2" role="radiogroup">
                        <button
                            v-for="action in blockedActions"
                            :key="action.value"
                            type="button"
                            role="radio"
                            class="flex items-start gap-3 rounded-lg border p-4 text-left transition hover:bg-accent"
                            :class="
                                form.blocked_action === action.value
                                    ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                    : ''
                            "
                            :aria-checked="form.blocked_action === action.value"
                            @click="form.blocked_action = action.value"
                        >
                            <component :is="action.icon" class="mt-0.5 size-4 shrink-0" />
                            <span class="grid gap-1">
                                <span class="text-sm font-medium">{{ action.title }}</span>
                                <span class="text-xs text-muted-foreground">
                                    {{ action.description }}
                                </span>
                            </span>
                        </button>
                    </div>
                    <InputError :message="form.errors.blocked_action" />

                    <div v-if="form.blocked_action === 'redirect'" class="grid gap-2">
                        <Label for="blocked_redirect_url">URL de redirecionamento</Label>
                        <Input
                            id="blocked_redirect_url"
                            type="url"
                            v-model="form.blocked_redirect_url"
                            :placeholder="redirectUrlPlaceholder"
                        />
                        <InputError :message="form.errors.blocked_redirect_url" />
                    </div>
                </CardContent>
            </Card>

            <!-- Languages -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Languages class="size-4" />
                        Idiomas
                    </CardTitle>
                    <CardDescription>
                        Idioma principal do navegador do visitante. Nenhum
                        selecionado = todos os idiomas.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-2">
                    <TrafficOptionPicker
                        v-model="form.allowed_languages"
                        :options="languagePickerOptions"
                        search-placeholder="Buscar idioma..."
                        empty-selection-label="Todos os idiomas permitidos"
                    />
                    <InputError :message="form.errors.allowed_languages" />
                </CardContent>
            </Card>

            <!-- Countries -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe class="size-4" />
                        Países
                    </CardTitle>
                    <CardDescription>
                        Identificado pelo IP do visitante. Nenhum selecionado =
                        todos os países.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <TrafficOptionPicker
                        v-model="form.allowed_countries"
                        :options="countryPickerOptions"
                        search-placeholder="Buscar país..."
                        empty-selection-label="Todos os países permitidos"
                    />
                    <InputError :message="form.errors.allowed_countries" />

                    <Label
                        for="allow_unknown_country"
                        class="flex cursor-pointer items-start gap-3"
                    >
                        <Checkbox
                            id="allow_unknown_country"
                            class="mt-0.5"
                            :model-value="form.allow_unknown_country"
                            @update:model-value="
                                form.allow_unknown_country = $event === true
                            "
                        />
                        <span class="grid gap-1">
                            <span>Permitir quando o país não for identificado</span>
                            <span class="text-xs font-normal text-muted-foreground">
                                Ex.: VPNs, redes privadas ou falha no serviço de
                                localização.
                            </span>
                        </span>
                    </Label>
                </CardContent>
            </Card>
        </div>
    </form>
</template>
