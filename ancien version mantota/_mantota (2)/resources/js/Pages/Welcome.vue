<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import PublicLayout from '../Components/PublicLayout.vue';

const urlRef = new URLSearchParams(window.location.search).get('ref');
const refQuery = computed(() => urlRef ? '?ref=' + encodeURIComponent(urlRef) : '');

const props = defineProps({
    vendors_count: { type: Number, default: 0 },
    influencers_count: { type: Number, default: 0 },
    active_campaigns_count: { type: Number, default: 0 },
    active_ugc_orders: { type: Number, default: 0 },
    delivered_ugc_orders: { type: Number, default: 0 },
    total_buyers: { type: Number, default: 0 },
    hero_title: { type: String, default: 'Vendez plus, plus vite.' },
    hero_subtitle: { type: String, default: '' },
    step1_title: { type: String, default: 'Inscrivez-vous' },
    step1_desc: { type: String, default: '' },
    step2_title: { type: String, default: 'Lancez ou partagez' },
    step2_desc: { type: String, default: '' },
    step3_title: { type: String, default: 'Gagnez & Grandissez' },
    step3_desc: { type: String, default: '' },
    vendor_title: { type: String, default: 'Pour les Vendeurs' },
    vendor_desc: { type: String, default: '' },
    vendor_image: { type: String, default: '' },
    influencer_title: { type: String, default: 'Pour les Createurs de Contenu' },
    influencer_desc: { type: String, default: '' },
    influencer_image: { type: String, default: '' },
    hero_image: { type: String, default: '' },
    testimonials: { type: Array, default: () => [] },
    ambassadors: { type: Array, default: () => [] },
    partners: { type: Array, default: () => [] },
    video_vendor_guide: { type: String, default: '' },
    video_influencer_guide: { type: String, default: '' },
    video_buyer_guide: { type: String, default: '' },
    video_welcome: { type: String, default: '' },
});

function fmt(n) { return new Intl.NumberFormat('fr-FR').format(n); }

function youtubeEmbedUrl(url) {
    if (!url) return '';
    const m = url.match(/(?:youtu\.be\/|v=|\/embed\/)([a-zA-Z0-9_-]{11})/);
    return m ? 'https://www.youtube.com/embed/' + m[1] : '';
}

// ── Animated counters ──
const counters = ref({ v: 0, i: 0, c: 0, b: 0 });
function animateCount(target, key, duration = 2000) {
    const start = performance.now();
    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        counters.value[key] = Math.floor(ease * target);
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

// ── Intersection Observer for scroll reveal ──
const revealed = ref(new Set());
let observer = null;

function onIntersect(entries) {
    entries.forEach(e => {
        if (e.isIntersecting) {
            revealed.value.add(e.target.dataset.reveal);
            revealed.value = new Set(revealed.value);

            // Trigger stat counters when stats section appears
            if (e.target.dataset.reveal === 'stats') {
                animateCount(props.vendors_count, 'v', 2200);
                animateCount(props.influencers_count, 'i', 2400);
                animateCount(props.active_campaigns_count, 'c', 2000);
                animateCount(props.total_buyers, 'b', 2600);
            }
            observer?.unobserve(e.target);
        }
    });
}

function isRevealed(key) { return revealed.value.has(key); }

const heroReady = ref(false);

// ── Testimonials carousel ──
const testimonialIdx = ref(0);
let testimonialTimer = null;
function nextTestimonial() {
    if (props.testimonials.length > 1) {
        testimonialIdx.value = (testimonialIdx.value + 1) % props.testimonials.length;
    }
}
function prevTestimonial() {
    if (props.testimonials.length > 1) {
        testimonialIdx.value = (testimonialIdx.value - 1 + props.testimonials.length) % props.testimonials.length;
    }
}

// ── Video guides ──
const hasAnyVideo = computed(() => props.video_vendor_guide || props.video_influencer_guide || props.video_buyer_guide);

onMounted(() => {
    setTimeout(() => { heroReady.value = true; }, 100);

    observer = new IntersectionObserver(onIntersect, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('[data-reveal]').forEach(el => observer.observe(el));

    if (props.testimonials.length > 1) {
        testimonialTimer = setInterval(nextTestimonial, 5000);
    }
});

onUnmounted(() => {
    observer?.disconnect();
    clearInterval(testimonialTimer);
});
</script>

<template>
    <Head title="MANTOTA | Reseau publicitaire 100% Performance au Benin et en Afrique">
        <meta head-key="description" name="description" content="MANTOTA, Premiere plateforme de marketing d'influence en Afrique. Connectez vendeurs et créateurs de contenu pour booster vos ventes." />
        <meta head-key="keywords" name="keywords" content="MANTOTA, marketing influence Afrique, vendeurs Benin, créateurs de contenu Afrique, publicite performance, e-commerce Benin" />
    </Head>

    <PublicLayout>

        <!-- ══════════════════════════════════════════════════════════
             1. HERO — Full viewport, animated gradient mesh
        ══════════════════════════════════════════════════════════ -->
        <section class="hero-wrap relative min-h-[100dvh] flex items-center justify-center overflow-hidden">

            <!-- Gradient mesh background -->
            <div class="absolute inset-0 bg-slate-950">
                <div v-if="hero_image" class="absolute inset-0 bg-cover bg-center opacity-40" :style="`background-image: url(${hero_image})`" />
                <div class="hero-orb hero-orb-1" />
                <div class="hero-orb hero-orb-2" />
                <div class="hero-orb hero-orb-3" />
            </div>

            <!-- Grid overlay -->
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 80px 80px;" />

            <!-- Floating particles -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <span v-for="n in 8" :key="n" class="hero-particle" :style="`left:${[10,25,70,85,50,35,90,5][n-1]}%; top:${[20,60,15,50,80,10,30,75][n-1]}%; animation-delay:${(n-1)*0.6}s; width:${n%3===0?'8px':'5px'}; height:${n%3===0?'8px':'5px'};`" />
            </div>

            <!-- Content -->
            <div class="relative z-10 mx-auto max-w-5xl px-4 text-center" :class="heroReady ? 'hero-content-in' : 'opacity-0'">

                <!-- Badge -->
                <div class="inline-flex items-center gap-2 rounded-full border border-teal-500/20 bg-teal-500/10 px-4 py-1.5 text-xs font-semibold text-teal-400 backdrop-blur-sm mb-8 hero-badge">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal-400 animate-pulse" />
                    Reseau publicitaire 100% Performance
                </div>

                <!-- Headline -->
                <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-5xl md:text-6xl lg:text-7xl">
                    <span class="hero-title-gradient">{{ hero_title }}</span>
                </h1>

                <!-- Subtitle -->
                <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-400 sm:text-lg md:text-xl">
                    {{ hero_subtitle }}
                </p>

                <!-- CTAs -->
                <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <a
                        :href="route('vendor.register') + refQuery"
                        class="group relative inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-teal-500 to-teal-600 px-8 py-4 text-sm font-bold text-white shadow-xl shadow-teal-500/25 transition-all duration-300 hover:shadow-teal-500/40 hover:-translate-y-0.5 hover:brightness-110"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
                        Je suis Vendeur
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                    <a
                        :href="route('influencer.register') + refQuery"
                        class="group inline-flex items-center gap-2 rounded-2xl border border-purple-500/30 bg-purple-500/10 px-8 py-4 text-sm font-bold text-purple-300 backdrop-blur-sm transition-all duration-300 hover:bg-purple-500/20 hover:border-purple-400/50 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-purple-500/10"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-8.42a14.98 14.98 0 00-5.199 2.58m0 0a14.926 14.926 0 01-2.58 5.84" /></svg>
                        Je suis Createur
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                <!-- Scroll indicator -->
                <div class="mt-16 flex justify-center hero-scroll-indicator">
                    <a href="#stats" class="flex flex-col items-center gap-2 text-slate-500 transition-colors hover:text-teal-400">
                        <span class="text-[10px] font-semibold uppercase tracking-[0.2em]">Decouvrir</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-bounce" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </a>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             2. STATS — Animated counters bar
        ══════════════════════════════════════════════════════════ -->
        <section id="stats" data-reveal="stats" class="relative border-y border-slate-800/60 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950">
            <div class="mx-auto max-w-6xl px-4 py-14 sm:py-16">

                <div class="mb-10 text-center transition-all duration-700" :class="isRevealed('stats') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-teal-500">Nos chiffres</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-white sm:text-3xl">Des resultats concrets</h2>
                </div>

                <div class="grid grid-cols-2 gap-6 sm:gap-8 lg:grid-cols-4">
                    <div v-for="(stat, idx) in [
                        { key: 'v', label: 'Vendeurs actifs', icon: 'store', color: 'teal' },
                        { key: 'i', label: 'Createurs de contenu', icon: 'rocket', color: 'purple' },
                        { key: 'c', label: 'Campagnes actives', icon: 'campaign', color: 'teal' },
                        { key: 'b', label: 'Acheteurs', icon: 'users', color: 'purple' },
                    ]" :key="stat.key"
                        class="stat-card group relative rounded-2xl border border-slate-800/60 bg-slate-900/50 p-6 text-center transition-all duration-500 hover:border-slate-700 hover:bg-slate-900/80"
                        :class="isRevealed('stats') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                        :style="`transition-delay: ${idx * 150}ms`"
                    >
                        <!-- Glow on hover -->
                        <div class="absolute inset-0 rounded-2xl opacity-0 transition-opacity duration-500 group-hover:opacity-100" :class="stat.color === 'teal' ? 'bg-gradient-to-br from-teal-500/5 to-transparent' : 'bg-gradient-to-br from-purple-500/5 to-transparent'" />

                        <!-- Icon -->
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl transition-all duration-300" :class="stat.color === 'teal' ? 'bg-teal-500/10 text-teal-400 group-hover:bg-teal-500/20' : 'bg-purple-500/10 text-purple-400 group-hover:bg-purple-500/20'">
                            <svg v-if="stat.icon === 'store'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
                            <svg v-else-if="stat.icon === 'rocket'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-8.42a14.98 14.98 0 00-5.199 2.58m0 0a14.926 14.926 0 01-2.58 5.84" /></svg>
                            <svg v-else-if="stat.icon === 'campaign'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.478m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                        </div>

                        <p class="text-3xl font-extrabold text-white sm:text-4xl">{{ fmt(counters[stat.key]) }}<span class="text-lg" :class="stat.color === 'teal' ? 'text-teal-500' : 'text-purple-500'">+</span></p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-wider text-slate-500">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             3. COMMENT ÇA MARCHE — 3 step cards (MTN Programs style)
        ══════════════════════════════════════════════════════════ -->
        <section id="comment-ca-marche" data-reveal="steps" class="relative py-24 sm:py-32 overflow-hidden">
            <!-- Decorative bg -->
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 h-[500px] w-[800px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.06),transparent_70%)] blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
                <div class="text-center mb-16 transition-all duration-700" :class="isRevealed('steps') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-teal-500">Comment ca marche</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl">Trois etapes simples</h2>
                    <p class="mx-auto mt-4 max-w-xl text-base text-slate-400">Des parcours adaptes pour chaque etape de votre croissance</p>
                </div>

                <div class="grid gap-6 sm:gap-8 md:grid-cols-3">
                    <div v-for="(step, idx) in [
                        { num: '01', title: step1_title, desc: step1_desc, gradient: 'from-teal-500 to-emerald-500', glow: 'teal', iconPath: 'M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z' },
                        { num: '02', title: step2_title, desc: step2_desc, gradient: 'from-purple-500 to-violet-500', glow: 'purple', iconPath: 'M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z' },
                        { num: '03', title: step3_title, desc: step3_desc, gradient: 'from-teal-500 to-cyan-500', glow: 'teal', iconPath: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z' },
                    ]" :key="step.num"
                        class="step-card group relative rounded-3xl border border-slate-800/60 bg-slate-900/40 p-8 transition-all duration-500 hover:border-slate-700/80 hover:bg-slate-900/70 hover:-translate-y-1 hover:shadow-2xl"
                        :class="[
                            isRevealed('steps') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12',
                            step.glow === 'teal' ? 'hover:shadow-teal-500/5' : 'hover:shadow-purple-500/5',
                        ]"
                        :style="`transition-delay: ${idx * 200}ms`"
                    >
                        <!-- Step number -->
                        <div class="mb-6 flex items-center gap-4">
                            <span class="text-sm font-extrabold bg-gradient-to-r bg-clip-text text-transparent" :class="`${step.gradient}`">{{ step.num }}</span>
                            <div class="h-px flex-1" :class="step.glow === 'teal' ? 'bg-gradient-to-r from-teal-500/30 to-transparent' : 'bg-gradient-to-r from-purple-500/30 to-transparent'" />
                        </div>

                        <!-- Icon -->
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl transition-all duration-300" :class="step.glow === 'teal' ? 'bg-teal-500/10 text-teal-400 group-hover:bg-teal-500/20 group-hover:shadow-lg group-hover:shadow-teal-500/10' : 'bg-purple-500/10 text-purple-400 group-hover:bg-purple-500/20 group-hover:shadow-lg group-hover:shadow-purple-500/10'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" :d="step.iconPath" /></svg>
                        </div>

                        <h3 class="text-xl font-bold text-white">{{ step.title }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             4. POUR LES VENDEURS — Split section
        ══════════════════════════════════════════════════════════ -->
        <section data-reveal="vendor" class="relative py-24 sm:py-32 overflow-hidden border-t border-slate-800/40">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -left-1/4 top-1/4 h-[500px] w-[500px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.08),transparent_70%)] blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
                    <!-- Text -->
                    <div class="transition-all duration-700" :class="isRevealed('vendor') ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-10'">
                        <span class="inline-flex items-center gap-2 rounded-full border border-teal-500/20 bg-teal-500/10 px-3 py-1 text-xs font-semibold text-teal-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
                            Espace Vendeur
                        </span>
                        <h2 class="mt-6 text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl leading-tight">{{ vendor_title }}</h2>
                        <p class="mt-5 text-base leading-relaxed text-slate-400 sm:text-lg">{{ vendor_desc }}</p>

                        <ul class="mt-8 space-y-4">
                            <li v-for="(feat, fi) in [
                                'Creez des campagnes CPC en quelques clics',
                                'Suivez chaque clic et conversion en temps reel',
                                'Budget protege par sequestre intelligent',
                                'Vendez vos produits via notre marketplace',
                            ]" :key="fi" class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </div>
                                <span class="text-sm text-slate-300">{{ feat }}</span>
                            </li>
                        </ul>

                        <a :href="route('vendor.register') + refQuery" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-teal-500/20 transition-all hover:shadow-teal-500/30 hover:-translate-y-0.5">
                            Devenir vendeur
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </a>
                    </div>

                    <!-- Visual: Image or Abstract dashboard illustration -->
                    <div class="transition-all duration-700 delay-200" :class="isRevealed('vendor') ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-10'">
                        <div v-if="vendor_image" class="relative rounded-3xl border border-slate-800/60 overflow-hidden shadow-2xl">
                            <img :src="vendor_image" alt="Espace Vendeur" class="w-full h-auto" />
                        </div>
                        <div v-else class="relative rounded-3xl border border-slate-800/60 bg-gradient-to-br from-slate-900 to-slate-900/50 p-6 shadow-2xl">
                            <!-- Mock dashboard -->
                            <div class="flex items-center gap-2 mb-4">
                                <div class="h-3 w-3 rounded-full bg-red-400/60" /><div class="h-3 w-3 rounded-full bg-amber-400/60" /><div class="h-3 w-3 rounded-full bg-green-400/60" />
                                <div class="ml-2 h-5 flex-1 rounded bg-slate-800/80" />
                            </div>
                            <!-- Stats row -->
                            <div class="grid grid-cols-3 gap-3 mb-4">
                                <div class="rounded-xl bg-teal-500/10 border border-teal-500/20 p-3 text-center">
                                    <p class="text-lg font-bold text-teal-400">2.4K</p><p class="text-[10px] text-slate-500">Clics</p>
                                </div>
                                <div class="rounded-xl bg-purple-500/10 border border-purple-500/20 p-3 text-center">
                                    <p class="text-lg font-bold text-purple-400">89%</p><p class="text-[10px] text-slate-500">Valides</p>
                                </div>
                                <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/20 p-3 text-center">
                                    <p class="text-lg font-bold text-emerald-400">47</p><p class="text-[10px] text-slate-500">Ventes</p>
                                </div>
                            </div>
                            <!-- Chart mock -->
                            <div class="h-32 rounded-xl bg-slate-800/40 border border-slate-800/60 p-3 flex items-end gap-1.5">
                                <div v-for="h in [40,55,35,70,85,60,90,45,75,65,80,50]" :key="h" class="flex-1 rounded-t bg-gradient-to-t from-teal-500/60 to-teal-400/30 transition-all duration-1000" :style="`height:${isRevealed('vendor') ? h : 0}%`" />
                            </div>
                            <!-- Table mock -->
                            <div class="mt-4 space-y-2">
                                <div v-for="n in 3" :key="n" class="flex items-center gap-3 rounded-lg bg-slate-800/30 px-3 py-2">
                                    <div class="h-7 w-7 rounded-lg bg-slate-700/60" />
                                    <div class="flex-1 space-y-1"><div class="h-2.5 w-3/4 rounded bg-slate-700/60" /><div class="h-2 w-1/2 rounded bg-slate-800/80" /></div>
                                    <div class="h-5 w-14 rounded-full" :class="n === 1 ? 'bg-teal-500/20' : n === 2 ? 'bg-amber-500/20' : 'bg-emerald-500/20'" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             5. POUR LES CRÉATEURS — Split section reversed
        ══════════════════════════════════════════════════════════ -->
        <section data-reveal="influencer" class="relative py-24 sm:py-32 overflow-hidden border-t border-slate-800/40">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -right-1/4 top-1/4 h-[500px] w-[500px] rounded-full bg-[radial-gradient(circle,rgba(147,51,234,0.08),transparent_70%)] blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
                    <!-- Visual: Image or Earnings illustration -->
                    <div class="order-2 lg:order-1 transition-all duration-700 delay-200" :class="isRevealed('influencer') ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-10'">
                        <div v-if="influencer_image" class="relative rounded-3xl border border-slate-800/60 overflow-hidden shadow-2xl">
                            <img :src="influencer_image" alt="Espace Créateur" class="w-full h-auto" />
                        </div>
                        <div v-else class="relative rounded-3xl border border-slate-800/60 bg-gradient-to-br from-slate-900 to-slate-900/50 p-6 shadow-2xl">
                            <!-- Mock phone -->
                            <div class="mx-auto max-w-[260px]">
                                <div class="rounded-3xl border-2 border-slate-700/60 bg-slate-900 p-4">
                                    <!-- Phone top -->
                                    <div class="mx-auto mb-3 h-4 w-20 rounded-full bg-slate-800" />
                                    <!-- Profile -->
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-violet-600" />
                                        <div><div class="h-3 w-20 rounded bg-slate-700/80" /><div class="mt-1 h-2 w-14 rounded bg-slate-800" /></div>
                                    </div>
                                    <!-- Earnings card -->
                                    <div class="rounded-xl bg-gradient-to-r from-purple-500/20 to-violet-500/20 border border-purple-500/30 p-4 mb-3">
                                        <p class="text-[10px] text-purple-400 font-semibold">Gains du mois</p>
                                        <p class="text-2xl font-extrabold text-white mt-1">125 400 <span class="text-sm text-purple-400">FCFA</span></p>
                                        <div class="flex items-center gap-1 mt-1"><span class="text-[10px] font-bold text-emerald-400">+32%</span><span class="text-[10px] text-slate-500">vs mois precedent</span></div>
                                    </div>
                                    <!-- Links list -->
                                    <div class="space-y-2">
                                        <div v-for="(l, li) in [{clicks: '1.2K', earned: '18 000'}, {clicks: '856', earned: '12 840'}, {clicks: '634', earned: '9 510'}]" :key="li" class="flex items-center justify-between rounded-lg bg-slate-800/50 px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <div class="h-6 w-6 rounded bg-slate-700/80" />
                                                <div><div class="h-2 w-16 rounded bg-slate-700/60" /><div class="mt-1 h-1.5 w-10 rounded bg-slate-800/80" /></div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[10px] font-bold text-purple-400">{{ l.clicks }} clics</p>
                                                <p class="text-[9px] text-emerald-400">{{ l.earned }} F</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text -->
                    <div class="order-1 lg:order-2 transition-all duration-700" :class="isRevealed('influencer') ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-10'">
                        <span class="inline-flex items-center gap-2 rounded-full border border-purple-500/20 bg-purple-500/10 px-3 py-1 text-xs font-semibold text-purple-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-8.42a14.98 14.98 0 00-5.199 2.58m0 0a14.926 14.926 0 01-2.58 5.84" /></svg>
                            Espace Createur
                        </span>
                        <h2 class="mt-6 text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl leading-tight">{{ influencer_title }}</h2>
                        <p class="mt-5 text-base leading-relaxed text-slate-400 sm:text-lg">{{ influencer_desc }}</p>

                        <ul class="mt-8 space-y-4">
                            <li v-for="(feat, fi) in [
                                'Generez des liens de campagne en un clic',
                                'Chaque clic valide vous rapporte de l\'argent',
                                'Suivi transparent de vos performances',
                                'Retirez vos gains facilement via Mobile Money',
                            ]" :key="fi" class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </div>
                                <span class="text-sm text-slate-300">{{ feat }}</span>
                            </li>
                        </ul>

                        <a :href="route('influencer.register') + refQuery" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-violet-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-purple-500/20 transition-all hover:shadow-purple-500/30 hover:-translate-y-0.5">
                            Devenir createur
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             6. VIDEO WELCOME — Cinematic embed
        ══════════════════════════════════════════════════════════ -->
        <section v-if="video_welcome" data-reveal="video" class="relative py-24 sm:py-32 border-t border-slate-800/40 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[800px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.06),transparent_70%)] blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-4xl px-4 sm:px-6">
                <div class="text-center mb-12 transition-all duration-700" :class="isRevealed('video') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-teal-500">Presentation</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl">Decouvrez MANTOTA en video</h2>
                </div>

                <div class="transition-all duration-700 delay-200" :class="isRevealed('video') ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">
                    <div class="relative rounded-3xl border border-slate-800/60 bg-slate-900/50 p-2 shadow-2xl shadow-teal-500/5">
                        <div class="aspect-video w-full overflow-hidden rounded-2xl">
                            <iframe
                                :src="youtubeEmbedUrl(video_welcome)"
                                class="h-full w-full"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             7. VIDEO GUIDES — 3 cards
        ══════════════════════════════════════════════════════════ -->
        <section v-if="hasAnyVideo" data-reveal="guides" class="relative py-24 sm:py-32 border-t border-slate-800/40 overflow-hidden">
            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
                <div class="text-center mb-14 transition-all duration-700" :class="isRevealed('guides') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-purple-500">Guides video</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl">Apprenez a utiliser la plateforme</h2>
                </div>

                <div class="grid gap-6 sm:gap-8 md:grid-cols-3">
                    <div v-for="(guide, gi) in [
                        { url: video_vendor_guide, label: 'Guide Vendeur', color: 'teal', icon: 'store' },
                        { url: video_influencer_guide, label: 'Guide Createur', color: 'purple', icon: 'rocket' },
                        { url: video_buyer_guide, label: 'Guide Acheteur', color: 'teal', icon: 'cart' },
                    ].filter(g => g.url)" :key="gi"
                        class="rounded-2xl border border-slate-800/60 bg-slate-900/40 overflow-hidden transition-all duration-500 hover:border-slate-700 hover:-translate-y-1"
                        :class="isRevealed('guides') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                        :style="`transition-delay: ${gi * 150}ms`"
                    >
                        <div class="aspect-video w-full">
                            <iframe :src="youtubeEmbedUrl(guide.url)" class="h-full w-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen />
                        </div>
                        <div class="flex items-center gap-3 px-5 py-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg" :class="guide.color === 'teal' ? 'bg-teal-500/10 text-teal-400' : 'bg-purple-500/10 text-purple-400'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                            </div>
                            <span class="text-sm font-bold text-white">{{ guide.label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             8. TESTIMONIALS — Carousel
        ══════════════════════════════════════════════════════════ -->
        <section v-if="testimonials.length" data-reveal="testimonials" class="relative py-24 sm:py-32 border-t border-slate-800/40 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -right-1/4 bottom-0 h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle,rgba(147,51,234,0.06),transparent_70%)] blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-4xl px-4 sm:px-6">
                <div class="text-center mb-14 transition-all duration-700" :class="isRevealed('testimonials') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-teal-500">Temoignages</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl">Ce qu'ils disent de nous</h2>
                </div>

                <div class="relative transition-all duration-700 delay-200" :class="isRevealed('testimonials') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'">
                    <!-- Card -->
                    <div class="rounded-3xl border border-slate-800/60 bg-gradient-to-br from-slate-900 to-slate-900/50 p-8 sm:p-12">
                        <div class="mb-6">
                            <svg class="h-10 w-10 text-teal-500/30" fill="currentColor" viewBox="0 0 32 32"><path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" /></svg>
                        </div>

                        <TransitionGroup name="testimonial-fade" tag="div" class="relative min-h-[120px]">
                            <div v-for="(t, ti) in testimonials" :key="t.id" v-show="ti === testimonialIdx" class="testimonial-slide">
                                <p class="text-lg leading-relaxed text-slate-300 sm:text-xl italic">"{{ t.content }}"</p>
                                <div class="mt-6 flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-purple-600 text-sm font-bold text-white">
                                        {{ (t.name || '?')[0].toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-white">{{ t.name }}</p>
                                        <p class="text-xs text-slate-500">{{ t.role }}</p>
                                    </div>
                                    <div v-if="t.rating" class="ml-auto flex gap-0.5">
                                        <svg v-for="s in 5" :key="s" class="h-4 w-4" :class="s <= t.rating ? 'text-amber-400' : 'text-slate-700'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                    </div>
                                </div>
                            </div>
                        </TransitionGroup>

                        <!-- Nav dots -->
                        <div v-if="testimonials.length > 1" class="mt-8 flex items-center justify-center gap-2">
                            <button @click="prevTestimonial" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-800 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                            </button>
                            <span v-for="(_, di) in testimonials" :key="di" @click="testimonialIdx = di" class="h-2 w-2 rounded-full cursor-pointer transition-all" :class="di === testimonialIdx ? 'bg-teal-500 w-6' : 'bg-slate-700 hover:bg-slate-600'" />
                            <button @click="nextTestimonial" class="rounded-full p-2 text-slate-500 transition hover:bg-slate-800 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             9. AMBASSADORS — Scrolling avatars
        ══════════════════════════════════════════════════════════ -->
        <section v-if="ambassadors.length" data-reveal="ambassadors" class="relative py-24 sm:py-32 border-t border-slate-800/40 overflow-hidden">
            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
                <div class="text-center mb-14 transition-all duration-700" :class="isRevealed('ambassadors') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-purple-500">Ambassadeurs</p>
                    <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl">Nos meilleurs partenaires</h2>
                </div>

                <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-6">
                    <div v-for="(amb, ai) in ambassadors" :key="amb.id"
                        class="group flex flex-col items-center gap-3 rounded-2xl border border-slate-800/40 bg-slate-900/30 p-4 transition-all duration-500 hover:border-slate-700 hover:bg-slate-900/60"
                        :class="isRevealed('ambassadors') ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                        :style="`transition-delay: ${ai * 80}ms`"
                    >
                        <div class="relative">
                            <img
                                v-if="amb.profile_photo || amb.shop_logo_path"
                                :src="`/storage/${amb.shop_logo_path || amb.profile_photo}`"
                                :alt="amb.name"
                                class="h-14 w-14 rounded-full object-cover ring-2 ring-slate-800 group-hover:ring-teal-500/40 transition-all"
                            />
                            <div v-else class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-purple-600 text-lg font-bold text-white ring-2 ring-slate-800 group-hover:ring-teal-500/40 transition-all">
                                {{ (amb.name || '?')[0].toUpperCase() }}
                            </div>
                            <div class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 ring-2 ring-slate-950">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            </div>
                        </div>
                        <p class="text-xs font-medium text-slate-400 text-center truncate w-full group-hover:text-white transition-colors">{{ amb.shop_name || amb.business_name || amb.name }}</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             10. PARTNERS — Infinite horizontal carousel (MTN style)
        ══════════════════════════════════════════════════════════ -->
        <section v-if="partners.length" data-reveal="partners" class="relative py-20 sm:py-24 border-t border-slate-800/40 overflow-hidden">
            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
                <div class="text-center mb-12 transition-all duration-700" :class="isRevealed('partners') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-teal-500">Partenaires</p>
                    <h2 class="mt-3 text-2xl font-extrabold text-white sm:text-3xl">Ils nous font confiance</h2>
                </div>
            </div>

            <!-- Infinite scroll track -->
            <div class="partners-track transition-all duration-700" :class="isRevealed('partners') ? 'opacity-100' : 'opacity-0'">
                <div class="partners-scroll">
                    <template v-for="repeat in 4" :key="repeat">
                        <a v-for="partner in partners" :key="`${repeat}-${partner.id}`"
                            :href="partner.url || '#'" target="_blank" rel="noopener"
                            class="mx-6 inline-flex h-16 w-28 shrink-0 items-center justify-center rounded-xl border border-slate-800/40 bg-slate-900/30 px-4 transition-all duration-300 hover:border-slate-700 hover:bg-slate-900/60 grayscale hover:grayscale-0"
                        >
                            <img :src="`/storage/${partner.logo}`" :alt="partner.name" class="max-h-10 max-w-full object-contain opacity-60 transition-opacity hover:opacity-100" />
                        </a>
                    </template>
                </div>
            </div>
        </section>


        <!-- ══════════════════════════════════════════════════════════
             11. FINAL CTA — Gradient section
        ══════════════════════════════════════════════════════════ -->
        <section data-reveal="cta" class="relative py-24 sm:py-32 overflow-hidden">
            <!-- Gradient background -->
            <div class="absolute inset-0 bg-gradient-to-br from-teal-600/10 via-slate-950 to-purple-600/10" />
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[500px] w-[500px] rounded-full bg-[radial-gradient(circle,rgba(20,184,166,0.1),transparent_60%)] blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-3xl px-4 sm:px-6 text-center">
                <div class="transition-all duration-700" :class="isRevealed('cta') ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
                    <h2 class="text-3xl font-extrabold text-white sm:text-4xl lg:text-5xl leading-tight">
                        Pret a booster votre <span class="hero-title-gradient">croissance</span> ?
                    </h2>
                    <p class="mt-5 text-base text-slate-400 sm:text-lg">
                        Rejoignez des centaines de vendeurs et createurs de contenu qui utilisent deja MANTOTA pour developper leur activite.
                    </p>

                    <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                        <a :href="route('vendor.register') + refQuery" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-teal-500 to-teal-600 px-8 py-4 text-sm font-bold text-white shadow-xl shadow-teal-500/25 transition-all hover:shadow-teal-500/40 hover:-translate-y-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" /></svg>
                            Creer mon compte Vendeur
                        </a>
                        <a :href="route('influencer.register') + refQuery" class="inline-flex items-center gap-2 rounded-2xl border border-purple-500/30 bg-purple-500/10 px-8 py-4 text-sm font-bold text-purple-300 transition-all hover:bg-purple-500/20 hover:border-purple-400/50 hover:-translate-y-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-8.42a14.98 14.98 0 00-5.199 2.58m0 0a14.926 14.926 0 01-2.58 5.84" /></svg>
                            Creer mon compte Createur
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </PublicLayout>
</template>


<style scoped>
/* ══════ HERO ORBS ══════ */
.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
}
.hero-orb-1 {
    top: -20%;
    left: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(20, 184, 166, 0.15), transparent 70%);
    animation: orbFloat1 20s ease-in-out infinite;
}
.hero-orb-2 {
    top: -10%;
    right: -15%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(147, 51, 234, 0.12), transparent 70%);
    animation: orbFloat2 25s ease-in-out infinite;
}
.hero-orb-3 {
    bottom: -30%;
    left: 30%;
    width: 550px;
    height: 550px;
    background: radial-gradient(circle, rgba(20, 184, 166, 0.08), transparent 70%);
    animation: orbFloat3 22s ease-in-out infinite;
}

@keyframes orbFloat1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(50px, 30px) scale(1.1); }
    66% { transform: translate(-30px, -20px) scale(0.95); }
}
@keyframes orbFloat2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(-40px, 40px) scale(1.05); }
    66% { transform: translate(30px, -30px) scale(0.9); }
}
@keyframes orbFloat3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(40px, -40px) scale(1.08); }
}

/* ══════ HERO PARTICLES ══════ */
.hero-particle {
    position: absolute;
    border-radius: 50%;
    background: rgba(20, 184, 166, 0.5);
    animation: particleFloat 6s ease-in-out infinite;
}
.hero-particle:nth-child(even) {
    background: rgba(147, 51, 234, 0.4);
}

@keyframes particleFloat {
    0%, 100% { transform: translateY(0) scale(1); opacity: 0.4; }
    50% { transform: translateY(-30px) scale(1.3); opacity: 0.8; }
}

/* ══════ HERO CONTENT ENTRANCE ══════ */
.hero-content-in {
    animation: heroContentAppear 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes heroContentAppear {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

.hero-badge {
    animation: badgePulse 3s ease-in-out infinite;
}
@keyframes badgePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.1); }
    50% { box-shadow: 0 0 0 8px rgba(20, 184, 166, 0); }
}

/* ══════ HERO TITLE GRADIENT ══════ */
.hero-title-gradient {
    background: linear-gradient(135deg, #14b8a6, #a855f7, #14b8a6);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: shimmer 4s linear infinite;
}
@keyframes shimmer {
    0% { background-position: 0% center; }
    100% { background-position: 200% center; }
}

/* ══════ SCROLL INDICATOR ══════ */
.hero-scroll-indicator {
    animation: fadeInDelayed 2s ease-out 1.5s both;
}
@keyframes fadeInDelayed {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ══════ TESTIMONIAL TRANSITIONS ══════ */
.testimonial-slide {
    position: absolute;
    width: 100%;
    top: 0;
    left: 0;
}
.testimonial-fade-enter-active,
.testimonial-fade-leave-active {
    transition: all 0.5s ease;
}
.testimonial-fade-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.testimonial-fade-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

/* ══════ PARTNERS INFINITE SCROLL ══════ */
.partners-track {
    overflow: hidden;
    mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent);
    -webkit-mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent);
}
.partners-scroll {
    display: flex;
    width: max-content;
    animation: partnersScroll 30s linear infinite;
}
.partners-scroll:hover {
    animation-play-state: paused;
}
@keyframes partnersScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
</style>
