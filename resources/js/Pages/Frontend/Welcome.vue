<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const slides = [
    {
        id: 1,
        icon: 'fas fa-gamepad',
        title: 'Tu Backlog Bajo Control',
        description: 'Gestiona todos tus juegos pendientes, en progreso y completados en un solo lugar.',
        color: '#6c63ff',
    },
    {
        id: 2,
        icon: 'fas fa-satellite-dish',
        title: 'Sesiones Multijugador',
        description: 'Crea grupos, coordina sesiones con amigos y encuentra compañeros de juego fácilmente.',
        color: '#00d4aa',
    },
    {
        id: 3,
        icon: 'fas fa-layer-group',
        title: 'Colecciones Personalizadas',
        description: 'Organiza tus juegos en colecciones temáticas y compártelas con la comunidad.',
        color: '#ff6b6b',
    },
    {
        id: 4,
        icon: 'fas fa-users',
        title: 'Perfil Público',
        description: 'Muestra tu historial gamer, tus redes y tus logros al mundo con un perfil único.',
        color: '#ffd93d',
    },
];

const features = [
    { icon: 'fas fa-list-ul',        title: 'Backlog Inteligente',   desc: 'Registra y filtra tus juegos por estado, plataforma y género.' },
    { icon: 'fas fa-compact-disc',   title: 'Colecciones',           desc: 'Agrupa juegos en listas personalizadas y compártelas.' },
    { icon: 'fas fa-satellite-dish', title: 'Sesiones Online',       desc: 'Crea y únete a sesiones multijugador con la comunidad.' },
    { icon: 'fas fa-share-alt',      title: 'Perfil Público',        desc: 'Un enlace para compartir todo tu mundo gamer.' },
    { icon: 'fas fa-desktop',        title: 'Mis Dispositivos',      desc: 'Registra tus consolas y equipos de juego.' },
    { icon: 'fas fa-comment-alt',    title: 'Publicaciones',         desc: 'Comparte opiniones y novedades con la comunidad.' },
    { icon: 'fas fa-globe',          title: 'Redes Sociales',        desc: 'Vincula tus perfiles de Steam, PSN, Xbox y más.' },
    { icon: 'fas fa-bell',           title: 'Notificaciones',        desc: 'Mantente al tanto de todo lo que pasa en tu red.' },
];

const currentSlide = ref(0);
const prev = () => { currentSlide.value = (currentSlide.value - 1 + slides.length) % slides.length; };
const next = () => { currentSlide.value = (currentSlide.value + 1) % slides.length; };

// Auto-advance
import { onMounted, onUnmounted } from 'vue';
let interval;
onMounted(() => { interval = setInterval(next, 4000); });
onUnmounted(() => clearInterval(interval));
</script>

<template>
    <Head title="MatchLog — Tu mundo gamer, organizado" />

    <div class="welcome-root">
        <!-- ===== NAVBAR ===== -->
        <nav class="navbar navbar-expand-lg navbar-dark welcome-nav px-4">
            <a class="navbar-brand fw-bold fs-4 brand-glow" href="/">
                <i class="fas fa-gamepad me-2 text-primary"></i>MatchLog
            </a>
            <div class="ms-auto d-flex gap-2">
                <Link href="/login" class="btn btn-outline-primary btn-sm rounded-pill px-4">Iniciar Sesión</Link>
                <Link href="/login" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Registrarse</Link>
            </div>
        </nav>

        <!-- ===== HERO SLIDER ===== -->
        <section class="hero-section position-relative overflow-hidden">
            <div class="hero-bg-gradient"></div>
            <div class="container position-relative z-1 py-5">
                <div class="row align-items-center min-vh-75 py-5">
                    <!-- Left: text -->
                    <div class="col-lg-6 mb-5 mb-lg-0">
                        <div class="slide-content" :key="currentSlide">
                            <div class="slide-icon-wrapper mb-4" :style="{ '--slide-color': slides[currentSlide].color }">
                                <i :class="slides[currentSlide].icon" class="slide-icon"></i>
                            </div>
                            <h1 class="display-4 fw-bold text-white mb-4 slide-title">
                                {{ slides[currentSlide].title }}
                            </h1>
                            <p class="lead text-light opacity-80 mb-5">
                                {{ slides[currentSlide].description }}
                            </p>
                            <div class="d-flex gap-3 flex-wrap">
                                <Link href="/login" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-glow">
                                    <i class="fas fa-rocket me-2"></i>Comenzar Gratis
                                </Link>
                                <a href="#features" class="btn btn-outline-light btn-lg rounded-pill px-4">
                                    <i class="fas fa-play-circle me-2"></i>Ver más
                                </a>
                            </div>
                        </div>

                        <!-- Slide dots -->
                        <div class="d-flex gap-2 mt-5">
                            <button v-for="(s, i) in slides" :key="i"
                                @click="currentSlide = i"
                                class="slide-dot"
                                :class="{ active: i === currentSlide }"
                            ></button>
                        </div>
                    </div>

                    <!-- Right: floating card -->
                    <div class="col-lg-6 d-flex justify-content-center">
                        <div class="hero-card glass-card p-4 rounded-4" :key="currentSlide">
                            <div class="d-flex align-items-center mb-4 gap-3">
                                <div class="hero-avatar" :style="{ background: slides[currentSlide].color }">
                                    <i :class="slides[currentSlide].icon" class="text-white fs-4"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-white">MatchLog</div>
                                    <div class="text-muted small">Tu plataforma gamer</div>
                                </div>
                            </div>
                            <div class="progress mb-2 bg-dark" style="height:6px">
                                <div class="progress-bar" :style="{ width: ((currentSlide+1)/slides.length*100)+'%', background: slides[currentSlide].color }"></div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div v-for="f in features.slice(0,4)" :key="f.title" class="col-6">
                                    <div class="mini-feature-card rounded-3 p-3 text-center">
                                        <i :class="f.icon" class="text-primary mb-2 fs-5"></i>
                                        <div class="small text-light fw-bold">{{ f.title }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Arrows -->
                <button class="slider-arrow left" @click="prev"><i class="fas fa-chevron-left"></i></button>
                <button class="slider-arrow right" @click="next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </section>

        <!-- ===== FEATURES ===== -->
        <section id="features" class="features-section py-6">
            <div class="container py-5">
                <div class="text-center mb-5">
                    <span class="badge bg-primary bg-opacity-20 text-primary rounded-pill px-4 py-2 mb-3 fs-6">¿Qué puedes hacer?</span>
                    <h2 class="display-5 fw-bold text-white">Todo lo que necesitas como gamer</h2>
                    <p class="text-muted lead mx-auto" style="max-width:600px">
                        MatchLog es tu centro de control: organiza, conecta y comparte todo tu mundo gamer en un solo lugar.
                    </p>
                </div>
                <div class="row g-4">
                    <div v-for="f in features" :key="f.title" class="col-sm-6 col-lg-3">
                        <div class="feature-card glass-card p-4 rounded-4 h-100 text-center hover-lift">
                            <div class="feature-icon-wrap mb-3">
                                <i :class="f.icon" class="fs-2 text-primary"></i>
                            </div>
                            <h5 class="fw-bold text-white mb-2">{{ f.title }}</h5>
                            <p class="text-muted small mb-0">{{ f.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW IT WORKS ===== -->
        <section class="how-section py-5">
            <div class="container py-5">
                <div class="text-center mb-5">
                    <h2 class="display-5 fw-bold text-white">¿Cómo funciona?</h2>
                </div>
                <div class="row g-4 align-items-center">
                    <div class="col-md-4 text-center">
                        <div class="step-circle mb-3">1</div>
                        <h5 class="text-white fw-bold">Regístrate gratis</h5>
                        <p class="text-muted">Crea tu cuenta en segundos y configura tu perfil gamer.</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="step-circle mb-3">2</div>
                        <h5 class="text-white fw-bold">Organiza tu backlog</h5>
                        <p class="text-muted">Agrega tus juegos, vincula plataformas y crea colecciones.</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="step-circle mb-3">3</div>
                        <h5 class="text-white fw-bold">Conecta y comparte</h5>
                        <p class="text-muted">Comparte tu perfil público y únete a sesiones con amigos.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CTA FINAL ===== -->
        <section class="cta-section py-6">
            <div class="container py-5 text-center">
                <div class="cta-card glass-card rounded-4 p-5 mx-auto" style="max-width:700px">
                    <i class="fas fa-gamepad fa-3x text-primary mb-4"></i>
                    <h2 class="display-5 fw-bold text-white mb-3">¿Listo para empezar?</h2>
                    <p class="text-muted lead mb-5">Únete a la comunidad gamer y toma el control de tu historial.</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <Link href="/login" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-glow">
                            <i class="fas fa-user-plus me-2"></i>Crear Cuenta Gratis
                        </Link>
                        <Link href="/login" class="btn btn-outline-light btn-lg rounded-pill px-4">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer class="footer-bar text-center py-4 text-muted">
            <small>© {{ new Date().getFullYear() }} MatchLog — Todos los derechos reservados</small>
        </footer>
    </div>
</template>

<style scoped>
.welcome-root {
    background: #0a0a12;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
}

/* NAVBAR */
.welcome-nav {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(10, 10, 18, 0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.brand-glow {
    text-shadow: 0 0 20px rgba(108, 99, 255, 0.5);
    color: #fff;
}

/* HERO */
.hero-section {
    background: radial-gradient(ellipse at 20% 50%, rgba(108,99,255,0.18) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(0,212,170,0.12) 0%, transparent 60%),
                #0a0a12;
    min-height: 85vh;
    display: flex;
    align-items: center;
}
.hero-bg-gradient {
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%236c63ff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.z-1 { z-index: 1; }
.min-vh-75 { min-height: 75vh; }

/* SLIDE ANIMATION */
.slide-content { animation: slideIn 0.5s ease; }
@keyframes slideIn {
    from { opacity: 0; transform: translateX(-30px); }
    to   { opacity: 1; transform: translateX(0); }
}

.slide-icon-wrapper {
    width: 80px; height: 80px;
    border-radius: 20px;
    background: var(--slide-color, #6c63ff);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 30px rgba(108,99,255,0.4);
    transition: background 0.4s;
}
.slide-icon { font-size: 2.2rem; color: #fff; }
.slide-title { animation: slideIn 0.5s ease; }

.slide-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    border: none; cursor: pointer;
    transition: all 0.3s;
    padding: 0;
}
.slide-dot.active {
    background: #6c63ff;
    width: 28px;
    border-radius: 5px;
}

/* HERO CARD */
.hero-card {
    width: 100%;
    max-width: 440px;
    animation: floatCard 3s ease-in-out infinite;
}
@keyframes floatCard {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-12px); }
}
.hero-avatar {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
}
.mini-feature-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    transition: background 0.2s;
}
.mini-feature-card:hover { background: rgba(108,99,255,0.15); }

/* GLASS CARD */
.glass-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.09);
    backdrop-filter: blur(12px);
}

/* ARROWS */
.slider-arrow {
    position: absolute;
    top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
    z-index: 10;
}
.slider-arrow:hover { background: rgba(108,99,255,0.4); }
.slider-arrow.left  { left: 10px; }
.slider-arrow.right { right: 10px; }

/* FEATURES */
.features-section { background: #0d0d18; }
.feature-card { transition: transform 0.2s, box-shadow 0.2s; }
.feature-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(108,99,255,0.2);
}
.feature-icon-wrap {
    width: 60px; height: 60px;
    border-radius: 16px;
    background: rgba(108,99,255,0.1);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto;
}

/* HOW */
.how-section { background: #0a0a12; }
.step-circle {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6c63ff, #00d4aa);
    color: #fff;
    font-size: 1.8rem; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto;
    box-shadow: 0 8px 30px rgba(108,99,255,0.35);
}

/* CTA */
.cta-section { background: radial-gradient(ellipse at center, rgba(108,99,255,0.12) 0%, #0a0a12 70%); }
.shadow-glow { box-shadow: 0 0 30px rgba(108,99,255,0.45); }

/* FOOTER */
.footer-bar { background: #07070f; border-top: 1px solid rgba(255,255,255,0.06); }

.py-6 { padding-top: 5rem; padding-bottom: 5rem; }
.opacity-80 { opacity: 0.8; }
</style>
