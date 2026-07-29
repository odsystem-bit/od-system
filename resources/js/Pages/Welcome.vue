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

// Dark/Light Mode
const isDark = ref(localStorage.getItem('mantota-theme') !== 'light');
const themeIcon = computed(() => isDark.value ? '☀️' : '🌙');
const scrolled = ref(false);
const mobileMenuOpen = ref(false);

function toggleTheme() {
    isDark.value = !isDark.value;
    localStorage.setItem('mantota-theme', isDark.value ? 'dark' : 'light');
    document.documentElement.style.setProperty('--bg', isDark.value ? '#060612' : '#F8F9FF');
    document.documentElement.style.setProperty('--bg2', isDark.value ? '#0D0D1F' : '#FFFFFF');
    document.documentElement.style.setProperty('--text', isDark.value ? '#FFFFFF' : '#060612');
    document.documentElement.style.setProperty('--text2', isDark.value ? 'rgba(255,255,255,0.6)' : 'rgba(6,6,18,0.6)');
}

// Animated counters
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

// Intersection Observer for scroll reveal
const revealed = ref(new Set());
let observer = null;

function onIntersect(entries) {
    entries.forEach(e => {
        if (e.isIntersecting) {
            revealed.value.add(e.target.dataset.reveal);
            revealed.value = new Set(revealed.value);

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

// Testimonials carousel
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

// Video guides
const hasAnyVideo = computed(() => props.video_vendor_guide || props.video_influencer_guide || props.video_buyer_guide);

// Canvas Particles
const canvasRef = ref(null);
let particles = [];
let animationFrame = null;

function initParticles() {
    if (!canvasRef.value || window.innerWidth < 768) return;
    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    particles = [];
    for (let i = 0; i < 50; i++) {
        particles.push({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            size: Math.random() * 2 + 1,
        });
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = isDark.value ? 'rgba(0, 212, 200, 0.3)' : 'rgba(0, 212, 200, 0.5)';

        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;

            if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
            if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fill();
        });

        animationFrame = requestAnimationFrame(animate);
    }
    animate();
}

// Cursor Glow
const cursorRef = ref(null);
function initCursor() {
    if (window.innerWidth < 768) return;
    const cursor = cursorRef.value;
    if (!cursor) return;

    document.addEventListener('mousemove', (e) => {
        cursor.style.left = e.clientX + 'px';
        cursor.style.top = e.clientY + 'px';
    });
}

onMounted(() => {
    setTimeout(() => { heroReady.value = true; }, 100);

    // Apply theme
    toggleTheme();

    observer = new IntersectionObserver(onIntersect, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('[data-reveal]').forEach(el => observer.observe(el));

    if (props.testimonials.length > 1) {
        testimonialTimer = setInterval(nextTestimonial, 4000);
    }

    initParticles();
    initCursor();
});

onUnmounted(() => {
    observer?.disconnect();
    clearInterval(testimonialTimer);
    cancelAnimationFrame(animationFrame);
});
</script>

<template>
    <Head title="MANTOTA | Reseau publicitaire 100% Performance au Benin et en Afrique">
        <meta head-key="description" name="description" content="MANTOTA, Premiere plateforme de marketing d'influence en Afrique. Connectez vendeurs et créateurs de contenu pour booster vos ventes." />
        <meta head-key="keywords" name="keywords" content="MANTOTA, marketing influence Afrique, vendeurs Benin, créateurs de contenu Afrique, publicite performance, e-commerce Benin" />
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    </Head>

    <PublicLayout>
        <!-- Cursor Glow -->
        <div ref="cursorRef" class="cursor-glow" v-if="window?.innerWidth >= 768" />

        <!-- Navbar -->
        <nav class="navbar" :class="{ 'scrolled': scrolled }">
            <div class="navbar-left">
                <span class="navbar-logo">MANTOTA</span>
            </div>
            <div class="navbar-center">
                <a href="#features" class="nav-link">Fonctionnalités</a>
                <a href="#vendors" class="nav-link">Vendeurs</a>
                <a href="#influencers" class="nav-link">Créateurs</a>
            </div>
            <div class="navbar-right">
                <button @click="toggleTheme" class="theme-toggle">{{ themeIcon }}</button>
                <div class="dropdown">
                    <button class="nav-link dropdown-btn">Se connecter ▼</button>
                    <div class="dropdown-menu">
                        <a :href="route('vendor.login') + refQuery">Vendeur</a>
                        <a :href="route('influencer.login') + refQuery">Créateur</a>
                    </div>
                </div>
                <a :href="route('vendor.register') + refQuery" class="btn-primary">Commencer gratuitement</a>
            </div>
            <button class="burger-menu" @click="mobileMenuOpen = !mobileMenuOpen">
                <span></span><span></span><span></span>
            </button>
        </nav>

        <!-- Mobile Menu -->
        <div class="mobile-menu" :class="{ 'open': mobileMenuOpen }">
            <a href="#features" @click="mobileMenuOpen = false">Fonctionnalités</a>
            <a href="#vendors" @click="mobileMenuOpen = false">Vendeurs</a>
            <a href="#influencers" @click="mobileMenuOpen = false">Créateurs</a>
            <a :href="route('vendor.login') + refQuery" @click="mobileMenuOpen = false">Connexion Vendeur</a>
            <a :href="route('influencer.login') + refQuery" @click="mobileMenuOpen = false">Connexion Créateur</a>
            <a :href="route('vendor.register') + refQuery" @click="mobileMenuOpen = false" class="btn-primary">Commencer</a>
        </div>

        <!-- Hero Section -->
        <section class="hero">
            <canvas ref="canvasRef" class="hero-canvas" v-if="!hero_image" />
            <div v-if="hero_image" class="hero-bg" :style="'background-image: url(' + hero_image + ')'" />
            <div class="hero-overlay" v-if="hero_image" />
            <div class="hero-grid" />
            
            <div class="hero-content" :class="{ 'ready': heroReady }">
                <div class="hero-badge">
                    <span class="hero-badge-dot"></span>
                    <span class="hero-badge-text">⚡ RÉSEAU PUBLICITAIRE #1 EN AFRIQUE</span>
                </div>
                
                <h1 class="hero-title">
                    <span class="hero-title-gradient">{{ hero_title.split(' ')[0] }}</span>
                    {{ hero_title.substring(hero_title.indexOf(' ') + 1) }}
                </h1>
                
                <p class="hero-subtitle">{{ hero_subtitle }}</p>
                
                <div class="hero-buttons">
                    <a :href="route('vendor.register') + refQuery" class="btn-primary-hero">
                        🏪 Je suis vendeur
                    </a>
                    <a :href="route('influencer.register') + refQuery" class="btn-secondary-hero">
                        🎥 Je crée du contenu
                    </a>
                </div>
                
                <div v-if="vendors_count > 0" class="hero-stats">
                    <span>{{ fmt(counters.v) }} Vendeurs</span>
                    <span class="divider">|</span>
                    <span>{{ fmt(counters.i) }} Créateurs</span>
                    <span class="divider">|</span>
                    <span>{{ fmt(counters.c) }} Campagnes</span>
                    <span class="divider">|</span>
                    <span>{{ fmt(counters.b) }} Acheteurs</span>
                </div>
            </div>
        </section>

        <!-- Video Welcome Section -->
        <section v-if="video_welcome" class="video-welcome-section">
            <div class="container">
                <h2 class="section-title">Découvrez MANTOTA en 2 min</h2>
                <div class="video-wrapper">
                    <iframe :src="youtubeEmbedUrl(video_welcome)" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="how-it-works-section">
            <div class="container">
                <h2 class="section-title">{{ step1_title }} → {{ step3_title }}</h2>
                <div class="steps-container">
                    <div class="steps-line"></div>
                    <div class="step" data-reveal="step1">
                        <span class="step-number">01</span>
                        <h3>{{ step1_title }}</h3>
                        <p>{{ step1_desc }}</p>
                    </div>
                    <div class="step" data-reveal="step2">
                        <span class="step-number">02</span>
                        <h3>{{ step2_title }}</h3>
                        <p>{{ step2_desc }}</p>
                    </div>
                    <div class="step" data-reveal="step3">
                        <span class="step-number">03</span>
                        <h3>{{ step3_title }}</h3>
                        <p>{{ step3_desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Vendors vs Creators Section -->
        <section id="vendors" class="vendors-creators-section">
            <div class="container">
                <div class="two-columns">
                    <div class="card glass-card" data-reveal="vendor">
                        <img v-if="vendor_image" :src="vendor_image" alt="Vendeurs" class="card-image" />
                        <h3>{{ vendor_title }}</h3>
                        <p>{{ vendor_desc }}</p>
                        <a :href="route('vendor.register') + refQuery" class="btn-primary">Commencer</a>
                    </div>
                    <div class="card glass-card" data-reveal="influencer">
                        <img v-if="influencer_image" :src="influencer_image" alt="Créateurs" class="card-image" />
                        <h3>{{ influencer_title }}</h3>
                        <p>{{ influencer_desc }}</p>
                        <a :href="route('influencer.register') + refQuery" class="btn-primary">Commencer</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Video Guides Section -->
        <section v-if="hasAnyVideo" class="video-guides-section">
            <div class="container">
                <h2 class="section-title">Guides de démarrage</h2>
                <div class="videos-grid">
                    <div v-if="video_vendor_guide" class="video-card">
                        <iframe :src="youtubeEmbedUrl(video_vendor_guide)" frameborder="0" allowfullscreen></iframe>
                        <p>Guide Vendeur</p>
                    </div>
                    <div v-if="video_influencer_guide" class="video-card">
                        <iframe :src="youtubeEmbedUrl(video_influencer_guide)" frameborder="0" allowfullscreen></iframe>
                        <p>Guide Créateur</p>
                    </div>
                    <div v-if="video_buyer_guide" class="video-card">
                        <iframe :src="youtubeEmbedUrl(video_buyer_guide)" frameborder="0" allowfullscreen></iframe>
                        <p>Guide Acheteur</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features-section">
            <div class="container">
                <h2 class="section-title">Fonctionnalités</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-line"></div>
                        <span class="feature-icon">🔒</span>
                        <h3>Paiement sécurisé par escrow</h3>
                        <p>Vos fonds sont protégés jusqu'à validation de la commande</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-line"></div>
                        <span class="feature-icon">📊</span>
                        <h3>Suivi en temps réel</h3>
                        <p>Analytics détaillés pour optimiser vos performances</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-line"></div>
                        <span class="feature-icon">🤖</span>
                        <h3>Agent Tracy WhatsApp intégré</h3>
                        <p>Automatisation intelligente de vos conversations</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-line"></div>
                        <span class="feature-icon">🌍</span>
                        <h3>Couverture Afrique de l'Ouest</h3>
                        <p>Présence dans tout le Bénin et la sous-région</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-line"></div>
                        <span class="feature-icon">💰</span>
                        <h3>100% Performance</h3>
                        <p>Payez uniquement pour les résultats réels</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-line"></div>
                        <span class="feature-icon">⚡</span>
                        <h3>Mise en place en 5 minutes</h3>
                        <p>Interface simple et rapide à configurer</p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
