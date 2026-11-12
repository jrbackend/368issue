<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    Bookmark,
    Check,
    Copy,
    ExternalLink,
    ShieldCheck,
    Wallet,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    oldDomain: string;
    newDomain: string;
    newDomainUrl: string;
    redirectSeconds: number;
}>();

const RING_RADIUS = 54;
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;

const elapsedMilliseconds = ref(0);
const isRedirecting = ref(false);
const hasCopiedAddress = ref(false);

let animationFrameId: number | null = null;
let redirectTimeoutId: ReturnType<typeof setTimeout> | null = null;
let copiedTimeoutId: ReturnType<typeof setTimeout> | null = null;

const totalMilliseconds = computed(() =>
    Math.max(props.redirectSeconds * 1000, 1),
);

const progress = computed(() =>
    Math.min(elapsedMilliseconds.value / totalMilliseconds.value, 1),
);

const secondsRemaining = computed(() =>
    Math.max(
        Math.ceil(
            (totalMilliseconds.value - elapsedMilliseconds.value) / 1000,
        ),
        0,
    ),
);

const ringDashOffset = computed(
    () => RING_CIRCUMFERENCE * (1 - progress.value),
);

/**
 * Deterministic particle layout so server-rendered and hydrated markup match.
 */
const particles = Array.from({ length: 28 }, (_, index) => {
    const seed = (index * 9301 + 49297) % 233280;
    const random = seed / 233280;

    return {
        left: `${(index * 37 + 11) % 100}%`,
        size: `${2 + (index % 4)}px`,
        delay: `${(random * -18).toFixed(2)}s`,
        duration: `${12 + (index % 7) * 2}s`,
        opacity: 0.25 + (index % 5) * 0.12,
    };
});

const benefits = [
    {
        icon: Wallet,
        title: 'Mesma conta e saldo',
        description:
            'Entre com os seus dados de sempre. Seu saldo, bônus e histórico já estão esperando por você.',
    },
    {
        icon: ShieldCheck,
        title: 'Conexão mais segura',
        description:
            'O novo endereço roda em uma infraestrutura atualizada e totalmente criptografada, mais rápida e segura.',
    },
    {
        icon: Bookmark,
        title: 'Atualize seus favoritos',
        description:
            'Salve o novo endereço agora. O domínio antigo deixará de funcionar em breve.',
    },
];

/**
 * Builds the destination URL, preserving any query string (e.g. referral codes).
 */
function destinationUrl(): string {
    const destination = new URL(props.newDomainUrl);

    new URLSearchParams(window.location.search).forEach((value, key) => {
        destination.searchParams.set(key, value);
    });

    return destination.toString();
}

function redirectToNewDomain(): void {
    if (isRedirecting.value) {
        return;
    }

    isRedirecting.value = true;
    window.location.replace(destinationUrl());
}

function tick(startedAt: number): void {
    elapsedMilliseconds.value = performance.now() - startedAt;

    if (elapsedMilliseconds.value < totalMilliseconds.value) {
        animationFrameId = requestAnimationFrame(() => tick(startedAt));
    }
}

async function copyNewAddress(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.newDomainUrl);
    } catch {
        return;
    }

    hasCopiedAddress.value = true;

    if (copiedTimeoutId) {
        clearTimeout(copiedTimeoutId);
    }

    copiedTimeoutId = setTimeout(() => {
        hasCopiedAddress.value = false;
    }, 2000);
}

onMounted(() => {
    const startedAt = performance.now();

    animationFrameId = requestAnimationFrame(() => tick(startedAt));
    redirectTimeoutId = setTimeout(
        redirectToNewDomain,
        totalMilliseconds.value,
    );
});

onBeforeUnmount(() => {
    if (animationFrameId !== null) {
        cancelAnimationFrame(animationFrameId);
    }

    if (redirectTimeoutId) {
        clearTimeout(redirectTimeoutId);
    }

    if (copiedTimeoutId) {
        clearTimeout(copiedTimeoutId);
    }
});
</script>

<template>
    <Head :title="`Mudamos para ${newDomain}`">
        <meta
            name="description"
            :content="`${oldDomain} agora é ${newDomain}. Mesma conta, mesmo saldo — novo endereço.`"
        />
        <meta name="robots" content="noindex, follow" />
        <meta name="theme-color" content="#0b3326" />
        <link rel="canonical" :href="newDomainUrl" />
        <meta
            http-equiv="refresh"
            :content="`${redirectSeconds + 2};url=${newDomainUrl}`"
        />
    </Head>

    <div
        class="relative isolate flex min-h-svh flex-col overflow-hidden bg-bet-ink text-white selection:bg-bet-mint/30"
    >
        <!-- Background: aurora blobs, grid and floating particles -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
            <div
                class="aurora absolute -top-48 -left-40 size-[38rem] rounded-full bg-bet-green/50 blur-3xl"
            />
            <div
                class="aurora aurora-delayed absolute top-1/3 -right-48 size-[34rem] rounded-full bg-bet-mint/15 blur-3xl"
            />
            <div
                class="aurora aurora-slow absolute -bottom-56 left-1/4 size-[30rem] rounded-full bg-bet-yellow/10 blur-3xl"
            />
            <div
                class="absolute inset-0 bg-[linear-gradient(to_right,rgb(255_255_255/0.035)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.035)_1px,transparent_1px)] mask-[radial-gradient(ellipse_at_center,black_30%,transparent_75%)] bg-size-[56px_56px]"
            />
            <span
                v-for="(particle, index) in particles"
                :key="index"
                class="particle absolute bottom-0 rounded-full"
                :class="index % 6 === 0 ? 'bg-bet-yellow' : 'bg-bet-mint'"
                :style="{
                    left: particle.left,
                    width: particle.size,
                    height: particle.size,
                    '--particle-opacity': particle.opacity,
                    animationDelay: particle.delay,
                    animationDuration: particle.duration,
                }"
            />
        </div>

        <!-- Top progress bar -->
        <div
            class="fixed inset-x-0 top-0 z-30 h-1 bg-black/30"
            role="progressbar"
            aria-label="Tempo até o redirecionamento"
            :aria-valuenow="Math.round(progress * 100)"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="h-full origin-left bg-linear-to-r from-bet-mint to-bet-yellow shadow-[0_0_16px_var(--color-bet-mint)]"
                :style="{ transform: `scaleX(${progress})` }"
            />
        </div>

        <!-- Brand bar -->
        <header
            class="relative z-20 border-b border-black/30 bg-linear-to-r from-bet-green-deep via-bet-green to-bet-green-deep shadow-[0_8px_30px_-12px_rgb(0_0_0/0.8)]"
        >
            <div class="mx-auto flex h-18 w-full max-w-6xl items-center justify-between px-6">
                <span
                    class="relative inline-flex items-baseline text-3xl font-extrabold tracking-tight italic"
                    aria-label="bet368"
                >
                    <span class="text-white">bet</span>
                    <span class="text-bet-yellow">368</span>
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 80 40"
                        class="pointer-events-none absolute -top-1 -right-4 h-11 w-20 text-bet-yellow"
                        fill="none"
                    >
                        <path
                            d="M8 34 C 40 34, 74 26, 76 12 C 77 5, 66 3, 58 6"
                            stroke="currentColor"
                            stroke-width="3"
                            stroke-linecap="round"
                        />
                    </svg>
                </span>

                <span
                    class="relative inline-flex h-18 items-center gap-2 text-sm font-semibold text-bet-mint"
                >
                    <span class="relative flex size-2">
                        <span
                            class="absolute inline-flex size-full rounded-full bg-bet-mint opacity-75 motion-safe:animate-ping"
                        />
                        <span class="relative inline-flex size-2 rounded-full bg-bet-mint" />
                    </span>
                    Aviso oficial
                    <span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-bet-mint" />
                </span>
            </div>
        </header>

        <main
            class="mx-auto flex w-full max-w-6xl flex-1 flex-col items-center justify-center gap-12 px-6 py-16 text-center"
        >
            <section class="reveal flex flex-col items-center gap-6">
                <p class="text-sm font-semibold tracking-[0.3em] text-bet-mint/90 uppercase">
                    Mudança de domínio
                </p>
                <h1
                    class="max-w-3xl text-4xl leading-[1.05] font-bold tracking-tight text-balance sm:text-6xl lg:text-7xl"
                >
                    Estamos em um
                    <span
                        class="shimmer bg-linear-to-r from-bet-mint via-white to-bet-yellow bg-size-[200%_auto] bg-clip-text text-transparent"
                    >
                        novo endereço
                    </span>
                </h1>
                <p class="max-w-2xl text-base text-pretty text-white/65 sm:text-lg">
                    Você está acessando nosso endereço antigo. A partir de
                    agora, tudo o que você ama está em
                    <strong class="font-semibold text-white">{{ newDomain }}</strong>.
                    Nada muda para você — só o endereço no seu navegador.
                </p>
            </section>

            <!-- Old domain → new domain -->
            <section
                class="reveal reveal-delay-1 flex w-full max-w-3xl flex-col items-center gap-4 sm:flex-row sm:gap-6"
                aria-label="Mudança de domínio"
            >
                <div
                    class="flex w-full flex-1 flex-col gap-1 rounded-2xl border border-white/10 bg-white/3 px-6 py-5 backdrop-blur"
                >
                    <span class="text-xs font-medium tracking-wider text-white/40 uppercase">
                        Endereço antigo
                    </span>
                    <span
                        class="font-mono text-lg text-white/45 line-through decoration-white/40 decoration-2 sm:text-xl"
                    >
                        {{ oldDomain }}
                    </span>
                </div>

                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-full border border-bet-mint/30 bg-bet-mint/10 text-bet-mint max-sm:rotate-90"
                >
                    <ArrowRight class="nudge size-5" />
                </div>

                <div
                    class="glow-border relative flex w-full flex-1 flex-col gap-1 rounded-2xl bg-bet-green-deep px-6 py-5"
                >
                    <span
                        class="flex items-center justify-center gap-1.5 text-xs font-medium tracking-wider text-bet-mint uppercase"
                    >
                        <BadgeCheck class="size-3.5" />
                        Novo endereço oficial
                    </span>
                    <span class="font-mono text-lg font-semibold text-white sm:text-xl">
                        {{ newDomain }}
                    </span>
                </div>
            </section>

            <!-- Countdown -->
            <section
                class="reveal reveal-delay-2 flex flex-col items-center gap-6"
                aria-live="polite"
            >
                <div class="relative size-36">
                    <svg
                        class="size-full -rotate-90"
                        viewBox="0 0 120 120"
                        aria-hidden="true"
                    >
                        <defs>
                            <linearGradient id="ring-gradient" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#3defb0" />
                                <stop offset="100%" stop-color="#f7d417" />
                            </linearGradient>
                        </defs>
                        <circle
                            cx="60"
                            cy="60"
                            :r="RING_RADIUS"
                            fill="none"
                            stroke="rgb(255 255 255 / 0.08)"
                            stroke-width="6"
                        />
                        <circle
                            cx="60"
                            cy="60"
                            :r="RING_RADIUS"
                            fill="none"
                            stroke="url(#ring-gradient)"
                            stroke-width="6"
                            stroke-linecap="round"
                            :stroke-dasharray="RING_CIRCUMFERENCE"
                            :stroke-dashoffset="ringDashOffset"
                            class="drop-shadow-[0_0_8px_rgb(61_239_176/0.6)]"
                        />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span
                            :key="secondsRemaining"
                            class="tick text-5xl font-bold tabular-nums"
                        >
                            {{ secondsRemaining }}
                        </span>
                        <span class="text-[0.65rem] tracking-[0.25em] text-white/45 uppercase">
                            segundos
                        </span>
                    </div>
                </div>

                <p class="text-sm text-white/60">
                    <template v-if="isRedirecting">
                        Levando você para {{ newDomain }}…
                    </template>
                    <template v-else>
                        Você será redirecionado automaticamente em
                        <span class="font-semibold text-white tabular-nums">{{ secondsRemaining }}</span>
                        {{ secondsRemaining === 1 ? 'segundo' : 'segundos' }}.
                    </template>
                </p>

                <div class="flex flex-col items-center gap-3 sm:flex-row">
                    <a
                        :href="newDomainUrl"
                        class="group relative inline-flex items-center gap-2 overflow-hidden rounded-full bg-bet-mint px-7 py-3.5 text-sm font-bold text-bet-green-deep shadow-[0_10px_40px_-10px_rgb(61_239_176/0.8)] transition hover:scale-[1.03] hover:shadow-[0_14px_50px_-10px_rgb(61_239_176/1)] focus-visible:ring-2 focus-visible:ring-bet-mint focus-visible:ring-offset-2 focus-visible:ring-offset-bet-ink focus-visible:outline-none"
                        @click.prevent="redirectToNewDomain"
                    >
                        <span
                            aria-hidden="true"
                            class="absolute inset-y-0 -left-1/2 w-1/3 skew-x-[-20deg] bg-white/50 blur-sm transition-[left] duration-700 group-hover:left-[120%]"
                        />
                        Ir para {{ newDomain }} agora
                        <ExternalLink class="size-4 transition group-hover:translate-x-0.5" />
                    </a>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-6 py-3.5 text-sm font-medium text-white/80 backdrop-blur transition hover:border-bet-yellow/50 hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:outline-none"
                        @click="copyNewAddress"
                    >
                        <Check v-if="hasCopiedAddress" class="size-4 text-bet-mint" />
                        <Copy v-else class="size-4" />
                        {{ hasCopiedAddress ? 'Endereço copiado!' : 'Copiar novo endereço' }}
                    </button>
                </div>
            </section>

            <!-- Benefits -->
            <section class="reveal reveal-delay-3 grid w-full max-w-5xl gap-4 text-left sm:grid-cols-3">
                <article
                    v-for="benefit in benefits"
                    :key="benefit.title"
                    class="group flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/3 p-6 backdrop-blur transition duration-300 hover:-translate-y-1 hover:border-bet-mint/40 hover:bg-bet-green/20"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-bet-mint/10 text-bet-mint transition group-hover:bg-bet-mint/20"
                    >
                        <component :is="benefit.icon" class="size-5" />
                    </span>
                    <h2 class="font-semibold">{{ benefit.title }}</h2>
                    <p class="text-sm text-white/55">{{ benefit.description }}</p>
                </article>
            </section>

            <p
                class="reveal reveal-delay-3 flex max-w-2xl items-start gap-2 rounded-xl border border-bet-yellow/25 bg-bet-yellow/5 px-5 py-4 text-left text-sm text-white/75"
            >
                <ShieldCheck class="mt-0.5 size-4 shrink-0 text-bet-yellow" />
                <span>
                    <strong class="font-semibold text-bet-yellow">Fique atento:</strong>
                    nosso único endereço oficial é
                    <strong class="font-semibold text-white">{{ newDomain }}</strong>.
                    Nunca pedimos sua senha por e-mail, chat ou redes sociais.
                    Cuidado com sites falsos parecidos.
                </span>
            </p>
        </main>

        <footer class="px-6 pb-8 text-center text-xs text-white/35">
            © {{ new Date().getFullYear() }} bet368 · {{ oldDomain }} agora é
            {{ newDomain }}
        </footer>
    </div>
</template>

<style scoped>
.aurora {
    animation: aurora 18s ease-in-out infinite alternate;
}

.aurora-delayed {
    animation-delay: -6s;
}

.aurora-slow {
    animation-duration: 24s;
}

.particle {
    animation: rise linear infinite;
    box-shadow: 0 0 8px rgb(61 239 176 / 0.7);
}

.shimmer {
    animation: shimmer 6s linear infinite;
}

.glow-border::before {
    content: '';
    position: absolute;
    inset: -1px;
    z-index: -1;
    border-radius: inherit;
    background: conic-gradient(
        from var(--glow-angle),
        #3defb0,
        #13593f,
        #f7d417,
        #3defb0
    );
    animation: spin-glow 4s linear infinite;
}

.glow-border::after {
    content: '';
    position: absolute;
    inset: -1px;
    z-index: -2;
    border-radius: inherit;
    filter: blur(18px);
    opacity: 0.5;
    background: conic-gradient(
        from var(--glow-angle),
        #3defb0,
        #13593f,
        #f7d417,
        #3defb0
    );
    animation: spin-glow 4s linear infinite;
}

.reveal {
    animation: reveal 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.reveal-delay-1 {
    animation-delay: 0.15s;
}

.reveal-delay-2 {
    animation-delay: 0.3s;
}

.reveal-delay-3 {
    animation-delay: 0.45s;
}

.nudge {
    animation: nudge 1.6s ease-in-out infinite;
}

.tick {
    animation: tick 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

@property --glow-angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}

@keyframes aurora {
    0% {
        transform: translate3d(0, 0, 0) scale(1);
    }
    100% {
        transform: translate3d(6rem, 4rem, 0) scale(1.15);
    }
}

@keyframes rise {
    0% {
        transform: translateY(0);
        opacity: 0;
    }
    10%,
    80% {
        opacity: var(--particle-opacity, 0.6);
    }
    100% {
        transform: translateY(-110vh);
        opacity: 0;
    }
}

@keyframes shimmer {
    to {
        background-position: 200% center;
    }
}

@keyframes spin-glow {
    to {
        --glow-angle: 360deg;
    }
}

@keyframes reveal {
    from {
        opacity: 0;
        transform: translateY(24px);
        filter: blur(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
    }
}

@keyframes tick {
    from {
        opacity: 0;
        transform: scale(1.35);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes nudge {
    0%,
    100% {
        transform: translateX(0);
    }
    50% {
        transform: translateX(4px);
    }
}

@media (prefers-reduced-motion: reduce) {
    .aurora,
    .particle,
    .shimmer,
    .glow-border::before,
    .glow-border::after,
    .reveal,
    .nudge,
    .tick {
        animation: none;
    }

    .particle {
        display: none;
    }
}
</style>
