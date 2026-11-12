<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, ExternalLink, Globe, Timer } from '@lucide/vue';
import { computed, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard, home } from '@/routes';
import { update } from '@/routes/domain-settings';

type DomainSetting = {
    old_domain: string;
    new_domain: string;
    new_domain_url: string;
    redirect_seconds: number;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    domainSetting: DomainSetting;
    updatedAt: string | null;
}>();

const form = useForm<DomainSetting>({ ...props.domainSetting });

const homeUrl = home.url();

const newDomainUrlPlaceholder = 'https://bet368.games';

/**
 * Keep the form in sync with the saved (normalized) values after each update.
 */
watch(
    () => props.domainSetting,
    (domainSetting) => {
        form.defaults({ ...domainSetting });
        form.reset();
    },
);

const formattedUpdatedAt = computed(() =>
    props.updatedAt
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(props.updatedAt))
        : null,
);

function submit(): void {
    form.put(update.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Heading
            title="Mudança de domínio"
            description="Gerencie o aviso exibido aos visitantes do domínio antigo e o redirecionamento automático."
        />

        <div class="grid gap-6 lg:grid-cols-5">
            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Configurações</CardTitle>
                    <CardDescription>
                        As alterações são aplicadas imediatamente na página
                        inicial.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-6" @submit.prevent="submit">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="old_domain">Domínio antigo</Label>
                                <Input
                                    id="old_domain"
                                    v-model="form.old_domain"
                                    required
                                    placeholder="bet368.cassino"
                                />
                                <InputError :message="form.errors.old_domain" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="new_domain">Novo domínio</Label>
                                <Input
                                    id="new_domain"
                                    v-model="form.new_domain"
                                    required
                                    placeholder="bet368.games"
                                />
                                <InputError :message="form.errors.new_domain" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="new_domain_url">
                                URL de redirecionamento
                            </Label>
                            <Input
                                id="new_domain_url"
                                type="url"
                                v-model="form.new_domain_url"
                                required
                                :placeholder="newDomainUrlPlaceholder"
                            />
                            <p class="text-xs text-muted-foreground">
                                Endereço para onde o visitante será enviado ao
                                fim da contagem.
                            </p>
                            <InputError :message="form.errors.new_domain_url" />
                        </div>

                        <div class="grid gap-2 sm:max-w-xs">
                            <Label for="redirect_seconds">
                                Tempo até o redirecionamento (segundos)
                            </Label>
                            <Input
                                id="redirect_seconds"
                                type="number"
                                min="5"
                                max="120"
                                v-model.number="form.redirect_seconds"
                                required
                            />
                            <InputError
                                :message="form.errors.redirect_seconds"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                data-test="update-domain-settings-button"
                            >
                                <Spinner v-if="form.processing" />
                                Salvar alterações
                            </Button>
                            <p
                                v-if="formattedUpdatedAt"
                                class="text-xs text-muted-foreground"
                            >
                                Última atualização: {{ formattedUpdatedAt }}
                            </p>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Pré-visualização</CardTitle>
                    <CardDescription>
                        Como o visitante verá a mudança.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <div
                        class="flex flex-col gap-3 rounded-xl bg-bet-ink p-5 text-white"
                    >
                        <div
                            class="rounded-lg border border-white/10 bg-white/5 px-4 py-3"
                        >
                            <p
                                class="text-[0.65rem] tracking-wider text-white/40 uppercase"
                            >
                                Endereço antigo
                            </p>
                            <p
                                class="truncate font-mono text-sm text-white/50 line-through"
                            >
                                {{ form.old_domain || '—' }}
                            </p>
                        </div>

                        <ArrowRight
                            class="mx-auto size-4 rotate-90 text-bet-mint"
                        />

                        <div
                            class="rounded-lg border border-bet-mint/40 bg-bet-green-deep px-4 py-3"
                        >
                            <p
                                class="text-[0.65rem] tracking-wider text-bet-mint uppercase"
                            >
                                Novo endereço oficial
                            </p>
                            <p class="truncate font-mono text-sm font-semibold">
                                {{ form.new_domain || '—' }}
                            </p>
                        </div>
                    </div>

                    <dl class="grid gap-3 text-sm">
                        <div class="flex items-center gap-2">
                            <Globe class="size-4 shrink-0 text-muted-foreground" />
                            <dt class="text-muted-foreground">Destino:</dt>
                            <dd class="truncate font-medium">
                                {{ form.new_domain_url || '—' }}
                            </dd>
                        </div>
                        <div class="flex items-center gap-2">
                            <Timer class="size-4 shrink-0 text-muted-foreground" />
                            <dt class="text-muted-foreground">Redireciona em:</dt>
                            <dd class="font-medium">
                                {{ form.redirect_seconds || '—' }} segundos
                            </dd>
                        </div>
                    </dl>

                    <Button variant="outline" as-child>
                        <a :href="homeUrl" target="_blank" rel="noopener">
                            Ver página de aviso
                            <ExternalLink class="size-4" />
                        </a>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
