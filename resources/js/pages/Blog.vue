<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowUpRight, Clock, Flame, Globe2, Shield, Trophy, Users } from '@lucide/vue';
import type { Directive } from 'vue';
import { computed, onBeforeUnmount, ref } from 'vue';

type League = {
    key: string;
    name: string;
    region: string;
    clubs: number;
    since: number;
    gradient: string;
    glow: string;
    summary: string;
};

type Post = {
    league: string;
    title: string;
    excerpt: string;
    readingMinutes: number;
};

const leagues: League[] = [
    {
        key: 'champions-league',
        name: 'Champions League',
        region: 'Europa',
        clubs: 36,
        since: 1955,
        gradient: 'from-indigo-600 via-blue-700 to-slate-900',
        glow: 'rgb(99 102 241 / 0.55)',
        summary: 'O torneio de clubes mais cobiçado do planeta, agora com fase de liga única e 36 equipes.',
    },
    {
        key: 'premier-league',
        name: 'Premier League',
        region: 'Inglaterra',
        clubs: 20,
        since: 1992,
        gradient: 'from-fuchsia-600 via-purple-700 to-violet-950',
        glow: 'rgb(192 38 211 / 0.55)',
        summary: 'Ritmo intenso, estádios lotados e a liga mais assistida do mundo.',
    },
    {
        key: 'la-liga',
        name: 'La Liga',
        region: 'Espanha',
        clubs: 20,
        since: 1929,
        gradient: 'from-orange-500 via-red-600 to-rose-900',
        glow: 'rgb(249 115 22 / 0.55)',
        summary: 'Técnica, posse de bola e alguns dos maiores clássicos da história.',
    },
    {
        key: 'bundesliga',
        name: 'Bundesliga',
        region: 'Alemanha',
        clubs: 18,
        since: 1963,
        gradient: 'from-red-600 via-rose-700 to-neutral-900',
        glow: 'rgb(220 38 38 / 0.55)',
        summary: 'Média de gols alta, ingressos acessíveis e torcidas que fazem festa.',
    },
    {
        key: 'serie-a',
        name: 'Serie A',
        region: 'Itália',
        clubs: 20,
        since: 1898,
        gradient: 'from-sky-500 via-blue-700 to-indigo-950',
        glow: 'rgb(14 165 233 / 0.55)',
        summary: 'A escola da tática: organização defensiva e muita inteligência de jogo.',
    },
    {
        key: 'brasileirao',
        name: 'Brasileirão',
        region: 'Brasil',
        clubs: 20,
        since: 1971,
        gradient: 'from-emerald-500 via-green-700 to-yellow-600',
        glow: 'rgb(16 185 129 / 0.55)',
        summary: 'Equilíbrio, rivalidades históricas e uma das ligas mais imprevisíveis do mundo.',
    },
];

const leaguesByKey = Object.fromEntries(leagues.map((league) => [league.key, league]));

const posts: Post[] = [
    {
        league: 'champions-league',
        title: 'O novo formato da Champions League, explicado de um jeito simples',
        excerpt: 'Fase de liga com 36 clubes, oito jogos contra adversários diferentes e playoff para quem fica entre o 9º e o 24º lugar. Entenda o que mudou e por que cada ponto vale ouro.',
        readingMinutes: 7,
    },
    {
        league: 'premier-league',
        title: 'Por que a Premier League virou a liga mais assistida do mundo',
        excerpt: 'Direitos de TV globais, elencos estrelados e jogos imprevisíveis do primeiro ao último colocado: os ingredientes do fenômeno inglês.',
        readingMinutes: 6,
    },
    {
        league: 'la-liga',
        title: 'La Liga e a arte do jogo de posição',
        excerpt: 'Da formação nas categorias de base à ocupação inteligente dos espaços: como o futebol espanhol influenciou treinadores no mundo inteiro.',
        readingMinutes: 8,
    },
    {
        league: 'bundesliga',
        title: 'A regra 50+1: o modelo que mantém os torcedores no comando',
        excerpt: 'Na Alemanha, os sócios precisam ter a maioria dos votos nos clubes. Veja como isso mantém ingressos acessíveis e estádios sempre cheios.',
        readingMinutes: 5,
    },
    {
        league: 'serie-a',
        title: 'Serie A: da retranca ao futebol propositivo',
        excerpt: 'A Itália continua sendo a grande escola tática da Europa — mas a nova geração de técnicos trouxe pressão alta e linhas mais ousadas.',
        readingMinutes: 6,
    },
    {
        league: 'brasileirao',
        title: 'Brasileirão: por que é tão difícil prever o campeão',
        excerpt: 'Calendário apertado, viagens longas e muitos clubes com orçamento forte tornam a disputa por pontos corridos uma das mais equilibradas do mundo.',
        readingMinutes: 5,
    },
    {
        league: 'premier-league',
        title: 'Bola parada: a arma silenciosa dos times ingleses',
        excerpt: 'Treinadores especialistas em escanteios e faltas viraram peça-chave. Os números mostram quanto essas jogadas decidem partidas.',
        readingMinutes: 4,
    },
    {
        league: 'champions-league',
        title: 'Noites europeias: o que torna o mata-mata tão especial',
        excerpt: 'Ida e volta, pressão da torcida e detalhes que mudam tudo: a mística das fases finais do maior torneio de clubes.',
        readingMinutes: 5,
    },
    {
        league: 'la-liga',
        title: 'Clássicos que param o mundo',
        excerpt: 'Rivalidades centenárias, estádios lendários e jogos que definem temporadas — um passeio pelos grandes duelos do futebol espanhol.',
        readingMinutes: 6,
    },
];

const [featuredPost, ...otherPosts] = posts;

/*
 * League filter.
 */
const selectedLeagueKey = ref<string | null>(null);

const visiblePosts = computed(() =>
    selectedLeagueKey.value === null
        ? otherPosts
        : posts.filter((post) => post.league === selectedLeagueKey.value),
);

function selectLeague(leagueKey: string | null): void {
    selectedLeagueKey.value = leagueKey;
    document.getElementById('artigos')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/*
 * 3D tilt that follows the pointer.
 */
function tilt(event: PointerEvent): void {
    const card = event.currentTarget as HTMLElement;
    const bounds = card.getBoundingClientRect();
    const x = (event.clientX - bounds.left) / bounds.width;
    const y = (event.clientY - bounds.top) / bounds.height;

    card.style.setProperty('--tilt-x', `${(0.5 - y) * 10}deg`);
    card.style.setProperty('--tilt-y', `${(x - 0.5) * 12}deg`);
    card.style.setProperty('--glow-x', `${x * 100}%`);
    card.style.setProperty('--glow-y', `${y * 100}%`);
}

function resetTilt(event: PointerEvent): void {
    const card = event.currentTarget as HTMLElement;

    card.style.setProperty('--tilt-x', '0deg');
    card.style.setProperty('--tilt-y', '0deg');
}

/*
 * Reveal elements as they scroll into view.
 */
const revealObservers: IntersectionObserver[] = [];

const vReveal: Directive<HTMLElement, (() => void) | undefined> = {
    mounted(element, binding) {
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    element.classList.add('is-revealed');
                    binding.value?.();
                    observer.disconnect();
                }
            },
            { threshold: 0.15 },
        );

        observer.observe(element);
        revealObservers.push(observer);
    },
};

onBeforeUnmount(() => revealObservers.forEach((observer) => observer.disconnect()));

/*
 * Animated counters for the stats strip.
 */
const counterProgress = ref(0);

const totalClubs = leagues.filter((league) => league.key !== 'champions-league').reduce((total, league) => total + league.clubs, 0);

const counters = computed(() => [
    { label: 'Ligas acompanhadas', value: leagues.length, icon: Trophy },
    { label: 'Clubes nas ligas nacionais', value: totalClubs, icon: Shield },
    { label: 'Equipes na Champions', value: 36, icon: Globe2 },
    { label: 'Anos de história (Serie A)', value: new Date().getFullYear() - 1898, icon: Clock },
]);

function startCounters(): void {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        counterProgress.value = 1;

        return;
    }

    const startedAt = performance.now();
    const duration = 1600;

    const step = (now: number): void => {
        const linearProgress = Math.min((now - startedAt) / duration, 1);
        counterProgress.value = 1 - Math.pow(1 - linearProgress, 3);

        if (linearProgress < 1) {
            requestAnimationFrame(step);
        }
    };

    requestAnimationFrame(step);
}

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Blog — Futebol pelo mundo">
        <meta
            name="description"
            content="Análises, histórias e curiosidades das maiores ligas de futebol do mundo: Champions League, Premier League, La Liga, Bundesliga, Serie A e Brasileirão."
        />
        <meta name="theme-color" content="#0b3326" />
    </Head>

    <div class="relative isolate min-h-svh overflow-x-clip bg-bet-ink text-white selection:bg-bet-mint/30">
        <!-- Ambient background -->
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10">
            <div class="blob absolute -top-40 -left-32 size-[34rem] rounded-full bg-bet-green/50 blur-3xl" />
            <div class="blob blob-delayed absolute top-1/2 -right-40 size-[30rem] rounded-full bg-indigo-600/20 blur-3xl" />
            <div class="blob blob-slow absolute -bottom-48 left-1/3 size-[28rem] rounded-full bg-bet-yellow/10 blur-3xl" />
            <div
                class="absolute inset-0 bg-[radial-gradient(rgb(255_255_255/0.06)_1px,transparent_1px)] mask-[radial-gradient(ellipse_at_top,black_20%,transparent_70%)] bg-size-[22px_22px]"
            />
        </div>

        <!-- Top bar -->
        <header class="sticky top-0 z-30 border-b border-white/10 bg-bet-ink/70 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-6">
                <a href="#inicio" class="flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold tracking-tight italic">
                        <span>bet</span><span class="text-bet-yellow">368</span>
                    </span>
                    <span class="rounded-full bg-bet-mint/15 px-2 py-0.5 text-xs font-semibold text-bet-mint">
                        Blog
                    </span>
                </a>
                <nav class="hidden items-center gap-1 text-sm lg:flex" aria-label="Ligas">
                    <button
                        v-for="league in leagues"
                        :key="league.key"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-white/70 transition hover:bg-white/10 hover:text-white"
                        @click="selectLeague(league.key)"
                    >
                        {{ league.name }}
                    </button>
                </nav>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section id="inicio" class="relative mx-auto grid max-w-7xl items-center gap-12 px-6 pt-16 pb-20 lg:grid-cols-2 lg:pt-24">
                <div class="hero-text flex flex-col gap-6">
                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-bet-mint/30 bg-bet-mint/10 px-3 py-1 text-xs font-semibold tracking-wider text-bet-mint uppercase">
                        <Flame class="size-3.5" />
                        Futebol pelo mundo
                    </span>
                    <h1 class="text-5xl leading-[1.02] font-black tracking-tight text-balance sm:text-6xl lg:text-7xl">
                        Onde o futebol
                        <span class="shimmer bg-linear-to-r from-bet-mint via-white to-bet-yellow bg-size-[200%_auto] bg-clip-text text-transparent">
                            vira paixão
                        </span>
                    </h1>
                    <p class="max-w-xl text-lg text-pretty text-white/65">
                        Análises táticas, histórias e curiosidades das maiores
                        ligas do planeta — da Champions League ao Brasileirão.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="#artigos"
                            class="group inline-flex items-center gap-2 rounded-full bg-bet-mint px-6 py-3 text-sm font-bold text-bet-green-deep shadow-[0_10px_40px_-10px_rgb(61_239_176/0.8)] transition hover:scale-[1.03]"
                        >
                            Ler os artigos
                            <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                        </a>
                        <a
                            href="#ligas"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-6 py-3 text-sm font-medium text-white/80 backdrop-blur transition hover:border-white/30 hover:bg-white/10"
                        >
                            Conhecer as ligas
                        </a>
                    </div>
                </div>

                <!-- Animated pitch -->
                <div class="hero-pitch relative mx-auto aspect-[4/3] w-full max-w-xl">
                    <div class="absolute inset-0 rounded-3xl bg-linear-to-br from-bet-green to-bet-green-deep shadow-[0_30px_80px_-20px_rgb(19_89_63/0.9)]" />
                    <div class="pitch-stripes absolute inset-0 rounded-3xl opacity-40" />
                    <svg viewBox="0 0 400 300" class="absolute inset-0 size-full" fill="none" aria-hidden="true">
                        <g class="pitch-lines" stroke="rgb(255 255 255 / 0.75)" stroke-width="2">
                            <rect x="16" y="16" width="368" height="268" rx="6" />
                            <line x1="200" y1="16" x2="200" y2="284" />
                            <circle cx="200" cy="150" r="38" />
                            <rect x="16" y="90" width="56" height="120" />
                            <rect x="328" y="90" width="56" height="120" />
                            <rect x="16" y="122" width="20" height="56" />
                            <rect x="364" y="122" width="20" height="56" />
                        </g>
                        <circle cx="200" cy="150" r="3" fill="white" />
                        <path id="ball-path" d="M 70 220 C 140 80, 260 80, 330 150 S 220 250, 70 220" stroke="none" />
                        <g class="ball">
                            <circle r="9" fill="white" />
                            <circle r="9" fill="none" stroke="rgb(0 0 0 / 0.25)" stroke-width="1.5" />
                            <path d="M -3 -3 L 3 -3 L 4 2 L 0 5 L -4 2 Z" fill="#171717" />
                            <animateMotion dur="7s" repeatCount="indefinite" rotate="auto">
                                <mpath href="#ball-path" />
                            </animateMotion>
                        </g>
                    </svg>
                    <div class="float-card absolute -bottom-6 -left-4 flex items-center gap-3 rounded-2xl border border-white/10 bg-bet-ink/80 px-4 py-3 shadow-xl backdrop-blur-xl sm:-left-8">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-bet-yellow/15 text-bet-yellow">
                            <Trophy class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs text-white/50">Ligas acompanhadas</p>
                            <p class="font-bold">{{ leagues.length }} competições</p>
                        </div>
                    </div>
                    <div class="float-card float-card-delayed absolute -top-5 -right-3 flex items-center gap-3 rounded-2xl border border-white/10 bg-bet-ink/80 px-4 py-3 shadow-xl backdrop-blur-xl sm:-right-6">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-bet-mint/15 text-bet-mint">
                            <Users class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs text-white/50">Clubes</p>
                            <p class="font-bold">{{ totalClubs }}+ times</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- League ticker -->
            <div class="relative border-y border-white/10 bg-white/[0.03] py-4" aria-hidden="true">
                <div class="ticker flex w-max gap-12 whitespace-nowrap">
                    <template v-for="copy in 2" :key="copy">
                        <span
                            v-for="league in leagues"
                            :key="`${copy}-${league.key}`"
                            class="flex items-center gap-12 text-2xl font-black tracking-tight text-white/20 uppercase italic"
                        >
                            {{ league.name }}
                            <span class="text-bet-mint/50">✦</span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- Featured article -->
            <section class="mx-auto max-w-7xl px-6 pt-20">
                <article
                    v-reveal
                    class="reveal tilt-card group relative grid overflow-hidden rounded-3xl border border-white/10 lg:grid-cols-2"
                    @pointermove="tilt"
                    @pointerleave="resetTilt"
                >
                    <div
                        class="relative min-h-64 bg-linear-to-br"
                        :class="leaguesByKey[featuredPost.league].gradient"
                    >
                        <div class="cover-pattern absolute inset-0 opacity-30" />
                        <Trophy class="absolute right-8 bottom-6 size-40 text-white/15 transition duration-700 group-hover:scale-110 group-hover:-rotate-6" />
                        <span class="absolute top-6 left-6 rounded-full bg-black/30 px-3 py-1 text-xs font-semibold backdrop-blur">
                            Destaque · {{ leaguesByKey[featuredPost.league].name }}
                        </span>
                    </div>
                    <div class="flex flex-col justify-center gap-4 bg-white/[0.04] p-8 lg:p-12">
                        <h2 class="text-3xl font-bold tracking-tight text-balance lg:text-4xl">
                            {{ featuredPost.title }}
                        </h2>
                        <p class="text-white/65">{{ featuredPost.excerpt }}</p>
                        <span class="flex items-center gap-1.5 text-sm text-white/45">
                            <Clock class="size-4" />
                            {{ featuredPost.readingMinutes }} min de leitura
                        </span>
                    </div>
                    <span class="tilt-glow pointer-events-none absolute inset-0" />
                </article>
            </section>

            <!-- Articles -->
            <section id="artigos" class="mx-auto max-w-7xl scroll-mt-24 px-6 pt-20">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold tracking-widest text-bet-mint uppercase">Artigos</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Leituras da rodada</h2>
                    </div>
                    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filtrar por liga">
                        <button
                            type="button"
                            role="tab"
                            class="rounded-full border px-4 py-1.5 text-sm transition"
                            :class="selectedLeagueKey === null ? 'border-bet-mint bg-bet-mint text-bet-green-deep font-semibold' : 'border-white/15 text-white/70 hover:border-white/30 hover:text-white'"
                            :aria-selected="selectedLeagueKey === null"
                            @click="selectedLeagueKey = null"
                        >
                            Todas
                        </button>
                        <button
                            v-for="league in leagues"
                            :key="league.key"
                            type="button"
                            role="tab"
                            class="rounded-full border px-4 py-1.5 text-sm transition"
                            :class="selectedLeagueKey === league.key ? 'border-bet-mint bg-bet-mint text-bet-green-deep font-semibold' : 'border-white/15 text-white/70 hover:border-white/30 hover:text-white'"
                            :aria-selected="selectedLeagueKey === league.key"
                            @click="selectedLeagueKey = league.key"
                        >
                            {{ league.name }}
                        </button>
                    </div>
                </div>

                <TransitionGroup tag="div" name="cards" class="relative mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="post in visiblePosts"
                        :key="post.title"
                        class="tilt-card group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04]"
                        @pointermove="tilt"
                        @pointerleave="resetTilt"
                    >
                        <div
                            class="relative h-40 overflow-hidden bg-linear-to-br"
                            :class="leaguesByKey[post.league].gradient"
                        >
                            <div class="cover-pattern absolute inset-0 opacity-25 transition duration-700 group-hover:scale-110" />
                            <span class="absolute bottom-3 left-4 text-2xl font-black tracking-tight text-white/90 uppercase italic drop-shadow">
                                {{ leaguesByKey[post.league].name }}
                            </span>
                        </div>
                        <div class="flex flex-1 flex-col gap-3 p-6">
                            <h3 class="text-lg leading-snug font-bold text-balance transition group-hover:text-bet-mint">
                                {{ post.title }}
                            </h3>
                            <p class="line-clamp-3 text-sm text-white/60">{{ post.excerpt }}</p>
                            <span class="mt-auto flex items-center gap-1.5 pt-2 text-xs text-white/45">
                                <Clock class="size-3.5" />
                                {{ post.readingMinutes }} min de leitura
                            </span>
                        </div>
                        <span class="tilt-glow pointer-events-none absolute inset-0" />
                    </article>
                </TransitionGroup>
            </section>

            <!-- Leagues -->
            <section id="ligas" class="mx-auto max-w-7xl scroll-mt-24 px-6 pt-24">
                <div v-reveal class="reveal max-w-2xl">
                    <p class="text-sm font-semibold tracking-widest text-bet-mint uppercase">As ligas</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Os maiores palcos do futebol</h2>
                    <p class="mt-3 text-white/60">
                        Cada liga tem a sua identidade. Conheça o que faz de cada
                        uma delas um espetáculo à parte.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <button
                        v-for="(league, index) in leagues"
                        :key="league.key"
                        v-reveal
                        type="button"
                        class="reveal league-card group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] p-6 text-left"
                        :style="{ '--league-glow': league.glow, '--reveal-delay': `${index * 70}ms` }"
                        @click="selectLeague(league.key)"
                    >
                        <div
                            class="absolute -top-16 -right-16 size-40 rounded-full bg-linear-to-br opacity-60 blur-2xl transition duration-500 group-hover:scale-150 group-hover:opacity-90"
                            :class="league.gradient"
                        />
                        <div class="relative flex flex-col gap-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold tracking-widest text-white/45 uppercase">{{ league.region }}</p>
                                    <h3 class="text-2xl font-black tracking-tight italic">{{ league.name }}</h3>
                                </div>
                                <ArrowUpRight class="size-5 text-white/40 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-bet-mint" />
                            </div>
                            <p class="text-sm text-white/60">{{ league.summary }}</p>
                            <dl class="flex gap-6 border-t border-white/10 pt-4 text-sm">
                                <div>
                                    <dt class="text-xs text-white/45">Clubes</dt>
                                    <dd class="font-bold tabular-nums">{{ league.clubs }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-white/45">Desde</dt>
                                    <dd class="font-bold tabular-nums">{{ league.since }}</dd>
                                </div>
                            </dl>
                        </div>
                    </button>
                </div>
            </section>

            <!-- Stats strip -->
            <section class="mx-auto max-w-7xl px-6 pt-24">
                <div
                    v-reveal="startCounters"
                    class="reveal grid gap-6 rounded-3xl border border-white/10 bg-linear-to-r from-bet-green-deep via-bet-green to-bet-green-deep p-8 sm:grid-cols-2 lg:grid-cols-4 lg:p-10"
                >
                    <div v-for="counter in counters" :key="counter.label" class="flex items-center gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-bet-yellow">
                            <component :is="counter.icon" class="size-6" />
                        </span>
                        <div>
                            <p class="text-3xl font-black tabular-nums">
                                {{ Math.round(counter.value * counterProgress) }}
                            </p>
                            <p class="text-sm text-white/65">{{ counter.label }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="mx-auto mt-24 max-w-7xl border-t border-white/10 px-6 py-10 text-sm text-white/40">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <span class="text-lg font-extrabold italic">
                    <span class="text-white/70">bet</span><span class="text-bet-yellow/80">368</span>
                    <span class="ml-1 text-sm font-medium text-white/40 not-italic">Blog</span>
                </span>
                <span>© {{ currentYear }} bet368 · Conteúdo editorial sobre futebol.</span>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.blob {
    animation: blob 20s ease-in-out infinite alternate;
}

.blob-delayed {
    animation-delay: -7s;
}

.blob-slow {
    animation-duration: 26s;
}

.shimmer {
    animation: shimmer 6s linear infinite;
}

.hero-text {
    animation: rise 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.hero-pitch {
    animation: rise 1.1s 0.15s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.pitch-stripes {
    background: repeating-linear-gradient(
        90deg,
        rgb(255 255 255 / 0.06) 0 40px,
        transparent 40px 80px
    );
}

.pitch-lines > * {
    stroke-dasharray: 1200;
    stroke-dashoffset: 1200;
    animation: draw 2.4s 0.4s ease-out forwards;
}

.ball {
    filter: drop-shadow(0 0 6px rgb(255 255 255 / 0.8));
}

.float-card {
    animation: float 6s ease-in-out infinite;
}

.float-card-delayed {
    animation-delay: -3s;
}

.ticker {
    animation: ticker 40s linear infinite;
}

.cover-pattern {
    background-image:
        radial-gradient(circle at 20% 30%, rgb(255 255 255 / 0.35) 0 2px, transparent 3px),
        repeating-linear-gradient(135deg, rgb(255 255 255 / 0.12) 0 2px, transparent 2px 14px);
}

.tilt-card {
    --tilt-x: 0deg;
    --tilt-y: 0deg;
    --glow-x: 50%;
    --glow-y: 50%;
    transform: perspective(900px) rotateX(var(--tilt-x)) rotateY(var(--tilt-y));
    transition:
        transform 0.25s ease-out,
        border-color 0.3s ease,
        box-shadow 0.3s ease;
    will-change: transform;
}

.tilt-card:hover {
    border-color: rgb(61 239 176 / 0.35);
    box-shadow: 0 25px 60px -20px rgb(0 0 0 / 0.8);
}

.tilt-glow {
    opacity: 0;
    background: radial-gradient(
        400px circle at var(--glow-x) var(--glow-y),
        rgb(61 239 176 / 0.12),
        transparent 60%
    );
    transition: opacity 0.3s ease;
}

.tilt-card:hover .tilt-glow {
    opacity: 1;
}

.league-card {
    transition:
        transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        border-color 0.3s ease,
        box-shadow 0.35s ease,
        opacity 0.8s ease;
}

.league-card:hover {
    transform: translateY(-6px);
    border-color: rgb(255 255 255 / 0.25);
    box-shadow: 0 20px 50px -15px var(--league-glow);
}

.reveal {
    opacity: 0;
    translate: 0 32px;
    transition:
        opacity 0.8s ease,
        translate 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.reveal.is-revealed {
    opacity: 1;
    translate: 0 0;
}

/* Cards that also tilt/lift: keep their hover transitions; only the reveal is staggered. */
.league-card.reveal,
.tilt-card.reveal {
    transition:
        opacity 0.8s ease var(--reveal-delay, 0ms),
        translate 0.8s cubic-bezier(0.16, 1, 0.3, 1) var(--reveal-delay, 0ms),
        transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
        border-color 0.3s ease,
        box-shadow 0.35s ease;
}

.cards-move,
.cards-enter-active,
.cards-leave-active {
    transition:
        opacity 0.4s ease,
        scale 0.4s ease,
        transform 0.4s ease;
}

.cards-enter-from,
.cards-leave-to {
    opacity: 0;
    scale: 0.92;
}

.cards-leave-active {
    position: absolute;
}

@keyframes blob {
    to {
        transform: translate3d(5rem, 3rem, 0) scale(1.15);
    }
}

@keyframes shimmer {
    to {
        background-position: 200% center;
    }
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(28px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes draw {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes ticker {
    to {
        transform: translateX(-50%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .blob,
    .shimmer,
    .hero-text,
    .hero-pitch,
    .float-card,
    .ticker,
    .pitch-lines > * {
        animation: none;
    }

    .pitch-lines > * {
        stroke-dashoffset: 0;
    }

    .ball {
        display: none;
    }

    .tilt-card {
        transform: none;
    }

    .reveal {
        opacity: 1;
        translate: none;
        transition: none;
    }
}
</style>
