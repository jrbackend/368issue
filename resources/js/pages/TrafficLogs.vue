<script setup lang="ts">
import { Head, Link, router, usePoll } from '@inertiajs/vue3';
import {
    Bot,
    ChevronLeft,
    ChevronRight,
    Monitor,
    Search,
    ShieldCheck,
    ShieldX,
    Smartphone,
    Tablet,
    Users,
} from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import type { Component } from 'vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { logs } from '@/routes/traffic';

type TrafficVisit = {
    id: number;
    ip: string;
    country_code: string | null;
    region_name: string | null;
    city_name: string | null;
    device: string;
    is_bot: boolean;
    operating_system: string | null;
    browser: string | null;
    language: string | null;
    user_agent: string | null;
    was_allowed: boolean;
    blocked_reason: string | null;
    created_at: string;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type StatusFilter = 'allowed' | 'blocked' | null;

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Logs de tráfego',
                href: logs(),
            },
        ],
    },
});

const props = defineProps<{
    visits: Paginated<TrafficVisit>;
    filters: { status: StatusFilter; search: string };
    stats: { total: number; allowed: number; blocked: number };
}>();

usePoll(15000, { only: ['visits', 'stats'] });

const search = ref(props.filters.search);

const statusOptions: { value: StatusFilter; label: string }[] = [
    { value: null, label: 'Todos' },
    { value: 'allowed', label: 'Permitidos' },
    { value: 'blocked', label: 'Bloqueados' },
];

const statCards: {
    key: keyof typeof props.stats;
    label: string;
    icon: Component;
    iconClass: string;
}[] = [
    { key: 'total', label: 'Visitas', icon: Users, iconClass: 'bg-primary/10 text-primary' },
    {
        key: 'allowed',
        label: 'Permitidas',
        icon: ShieldCheck,
        iconClass: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    {
        key: 'blocked',
        label: 'Bloqueadas',
        icon: ShieldX,
        iconClass: 'bg-destructive/10 text-destructive',
    },
];

const deviceDetails: Record<string, { label: string; icon: Component }> = {
    desktop: { label: 'Computador', icon: Monitor },
    mobile: { label: 'Celular', icon: Smartphone },
    tablet: { label: 'Tablet', icon: Tablet },
};

const blockedReasonLabels: Record<string, string> = {
    device: 'Dispositivo',
    language: 'Idioma',
    country: 'País',
    unknown_country: 'País desconhecido',
};

const regionNames = new Intl.DisplayNames(['pt-BR'], { type: 'region' });
const languageNames = new Intl.DisplayNames(['pt-BR'], { type: 'language' });
const dateTimeFormat = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'medium',
});

function countryName(countryCode: string): string {
    return regionNames.of(countryCode) ?? countryCode;
}

function countryFlag(countryCode: string): string {
    return String.fromCodePoint(
        ...[...countryCode].map((letter) => 0x1f1e6 + letter.charCodeAt(0) - 65),
    );
}

function languageName(language: string): string {
    const name = languageNames.of(language) ?? language;

    return name.charAt(0).toLocaleUpperCase('pt-BR') + name.slice(1);
}

function cityAndRegion(visit: TrafficVisit): string | null {
    const parts = [visit.city_name, visit.region_name].filter(
        (part, index, all): part is string =>
            part !== null && all.indexOf(part) === index,
    );

    return parts.length > 0 ? parts.join(', ') : null;
}

function applyFilters(filters: { status: StatusFilter; search: string }): void {
    router.get(
        logs.url({
            query: {
                status: filters.status ?? undefined,
                search: filters.search.trim() || undefined,
            },
        }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function setStatus(status: StatusFilter): void {
    applyFilters({ status, search: search.value });
}

const searchVisits = useDebounceFn(() => {
    applyFilters({ status: props.filters.status, search: search.value });
}, 350);
</script>

<template>
    <Head title="Logs de tráfego" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Heading
            title="Logs de tráfego"
            description="Visitas à página inicial, permitidas e bloqueadas pelo controle de tráfego. Atualiza automaticamente."
        />

        <!-- Stats (last 24 hours) -->
        <div class="grid gap-4 sm:grid-cols-3">
            <Card v-for="card in statCards" :key="card.key" class="py-4">
                <CardContent class="flex items-center gap-4 px-4">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                        :class="card.iconClass"
                    >
                        <component :is="card.icon" class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ stats[card.key].toLocaleString('pt-BR') }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ card.label }} nas últimas 24h
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="inline-flex rounded-lg border p-1" role="tablist">
                <button
                    v-for="option in statusOptions"
                    :key="option.label"
                    type="button"
                    role="tab"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                    :class="
                        filters.status === option.value
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                    "
                    :aria-selected="filters.status === option.value"
                    @click="setStatus(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>

            <div class="relative sm:w-80">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-9"
                    placeholder="Buscar por IP, cidade, estado ou país (BR)..."
                    @update:model-value="searchVisits"
                />
            </div>
        </div>

        <!-- Visits -->
        <Card class="gap-0 overflow-hidden py-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Data/hora</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">IP</th>
                            <th class="px-4 py-3 font-medium">Localização</th>
                            <th class="px-4 py-3 font-medium">Dispositivo</th>
                            <th class="px-4 py-3 font-medium">Sistema / navegador</th>
                            <th class="px-4 py-3 font-medium">Idioma</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="visit in visits.data"
                            :key="visit.id"
                            class="transition hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 whitespace-nowrap tabular-nums">
                                {{ dateTimeFormat.format(new Date(visit.created_at)) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge
                                    v-if="visit.was_allowed"
                                    class="border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400"
                                >
                                    <ShieldCheck />
                                    Permitido
                                </Badge>
                                <div v-else class="flex flex-col items-start gap-1">
                                    <Badge variant="destructive">
                                        <ShieldX />
                                        Bloqueado
                                    </Badge>
                                    <span
                                        v-if="visit.blocked_reason"
                                        class="text-xs text-muted-foreground"
                                    >
                                        Motivo:
                                        {{
                                            blockedReasonLabels[visit.blocked_reason] ??
                                            visit.blocked_reason
                                        }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">
                                {{ visit.ip }}
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="visit.country_code" class="flex items-center gap-2">
                                    <span class="text-lg leading-none">
                                        {{ countryFlag(visit.country_code) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">
                                            {{ cityAndRegion(visit) ?? countryName(visit.country_code) }}
                                        </p>
                                        <p
                                            v-if="cityAndRegion(visit)"
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ countryName(visit.country_code) }}
                                        </p>
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground">Desconhecida</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-2">
                                    <component
                                        :is="deviceDetails[visit.device]?.icon ?? Monitor"
                                        class="size-4 text-muted-foreground"
                                    />
                                    {{ deviceDetails[visit.device]?.label ?? visit.device }}
                                </span>
                                <Badge
                                    v-if="visit.is_bot"
                                    variant="outline"
                                    class="ml-2"
                                >
                                    <Bot />
                                    Robô
                                </Badge>
                            </td>
                            <td class="px-4 py-3" :title="visit.user_agent ?? undefined">
                                <p class="font-medium">
                                    {{ visit.operating_system ?? 'Desconhecido' }}
                                </p>
                                <p v-if="visit.browser" class="text-xs text-muted-foreground">
                                    {{ visit.browser }}
                                </p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ visit.language ? languageName(visit.language) : '—' }}
                            </td>
                        </tr>
                        <tr v-if="visits.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                                Nenhuma visita encontrada.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="visits.total > 0"
                class="flex items-center justify-between gap-4 border-t px-4 py-3 text-sm"
            >
                <p class="text-muted-foreground">
                    {{ visits.from }}–{{ visits.to }} de
                    {{ visits.total.toLocaleString('pt-BR') }} visitas
                </p>
                <div class="flex items-center gap-2">
                    <component
                        :is="visits.prev_page_url ? Link : 'span'"
                        :href="visits.prev_page_url ?? undefined"
                        preserve-scroll
                        class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5"
                        :class="
                            visits.prev_page_url
                                ? 'hover:bg-accent'
                                : 'pointer-events-none opacity-50'
                        "
                    >
                        <ChevronLeft class="size-4" />
                        Anterior
                    </component>
                    <span class="text-muted-foreground tabular-nums">
                        {{ visits.current_page }} / {{ visits.last_page }}
                    </span>
                    <component
                        :is="visits.next_page_url ? Link : 'span'"
                        :href="visits.next_page_url ?? undefined"
                        preserve-scroll
                        class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5"
                        :class="
                            visits.next_page_url
                                ? 'hover:bg-accent'
                                : 'pointer-events-none opacity-50'
                        "
                    >
                        Próxima
                        <ChevronRight class="size-4" />
                    </component>
                </div>
            </div>
        </Card>
    </div>
</template>
