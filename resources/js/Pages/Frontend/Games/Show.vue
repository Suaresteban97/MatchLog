<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({ game: Object });

const gameData = computed(() => props.game.data || props.game);
const screenshots = computed(() => gameData.value.screenshots || []);

// Lightbox
const lightboxImg = ref(null);
const openLightbox = (url) => { lightboxImg.value = url; };
const closeLightbox = () => { lightboxImg.value = null; };

const showAddedSuccess = ref(false);
const isAdding = ref(false);

const addToLibrary = async () => {
    isAdding.value = true;
    try {
        const res = await axios.post(`/api/my-games/${gameData.value.id}/toggle`, {
            game_status_id: 1
        });
        if (res.data.success) {
            showAddedSuccess.value = true;
            setTimeout(() => showAddedSuccess.value = false, 3000);
        }
    } catch (e) {
        console.error("Error toggling game", e);
    } finally {
        isAdding.value = false;
    }
};

const formatBoolean = (val) => val ? 'Sí' : 'No';
const formatDate = (dateStr) => {
    if (!dateStr) return 'TBD';
    const d = new Date(dateStr);
    return !isNaN(d) ? d.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' }) : dateStr;
};
</script>

<template>

    <Head :title="`${gameData.name} - Detalles`" />

    <AppLayout v-cloak>
        <div class="row pt-4 g-4 position-relative">
            <!-- Back Button -->
            <div class="col-12 mb-2">
                <Link href="/games"
                    class="btn btn-outline-secondary btn-sm rounded-pill text-decoration-none shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Catálogo
                </Link>
            </div>

            <div class="col-12 col-md-4 col-lg-3">
                <div class="card bg-dark border-secondary shadow-lg overflow-hidden" style="border-radius: 12px;">
                    <img v-if="gameData.cover_image_url" :src="gameData.cover_image_url" :alt="gameData.name"
                        class="w-100 object-fit-cover shadow-sm bg-black" style="aspect-ratio: 3 / 4;">
                    <div v-else
                        class="w-100 bg-black d-flex align-items-center justify-content-center text-muted col-12"
                        style="aspect-ratio: 3 / 4;">
                        <i class="fas fa-image fa-3x"></i>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button @click="addToLibrary"
                        class="btn btn-primary fw-bold p-3 rounded-pill shadow-lg d-flex align-items-center justify-content-center"
                        :disabled="isAdding">
                        <i v-if="isAdding" class="fas fa-spinner fa-spin me-2"></i>
                        <i v-else class="fas fa-folder-plus me-2"></i>
                        Añadir a Mi Espacio
                    </button>

                    <div v-if="showAddedSuccess"
                        class="alert alert-success mt-2 py-2 small text-center rounded-pill fade show active shadow-sm"
                        style="background-color: var(--neon-blue); color: #000; border: none;">
                        <i class="fas fa-check-circle me-1"></i>Biblioteca actualizada
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8 col-lg-9">
                <div class="card bg-dark border-secondary h-100" style="background: rgba(var(--bg-card-rgb), 0.9);">
                    <div class="card-body p-4 p-md-5">

                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <h1 class="fw-bold text-white display-5 mb-0"
                                style="text-shadow: 2px 2px 10px rgba(0,0,0,0.5);">{{ gameData.name }}</h1>
                            <div v-if="gameData.metacritic_score"
                                class="text-center bg-black border border-secondary p-3 rounded-circle shadow-lg"
                                style="width: 80px; height: 80px; display: flex; flex-direction: column; justify-content: center;">
                                <span class="d-block small text-muted text-uppercase"
                                    style="font-size: 0.6rem; letter-spacing: 1px;">Metascore</span>
                                <span class="fs-4 fw-bold"
                                    :class="gameData.metacritic_score >= 80 ? 'text-success' : (gameData.metacritic_score >= 60 ? 'text-warning' : 'text-danger')">
                                    {{ gameData.metacritic_score }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Pills -->
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge bg-primary text-dark rounded-pill py-2 px-3 fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>{{ formatDate(gameData.release_date) }}
                            </span>
                            <span v-for="genre in gameData.genres" :key="genre.id"
                                class="badge bg-secondary border border-dark rounded-pill py-2 px-3">
                                <i class="fas fa-tag me-1 text-muted"></i>{{ genre.name }}
                            </span>
                        </div>

                        <h5 class="text-white border-bottom border-secondary pb-2 mb-3">
                            <i class="fas fa-align-left me-2 text-primary"></i>Descripción
                        </h5>
                        <p class="text-muted" style="line-height: 1.8;">
                            {{
                                gameData.description || 'No hay descripción disponible para este título.'
                            }}
                        </p>

                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush bg-transparent rounded">
                                    <li class="list-group-item bg-transparent text-white border-secondary px-0">
                                        <strong class="text-muted small text-uppercase d-block">Desarrollador</strong>
                                        <span class="fw-bold">{{ gameData.developer || 'Desconocido' }}</span>
                                    </li>
                                    <li class="list-group-item bg-transparent text-white border-secondary px-0">
                                        <strong class="text-muted small text-uppercase d-block">Publicador</strong>
                                        <span class="fw-bold">{{ gameData.publisher || 'Desconocido' }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-black border border-secondary rounded p-3 shadow-sm h-100">
                                    <h6 class="text-white small text-uppercase mb-3"><i
                                            class="fas fa-users me-2 text-info"></i>Capacidades</h6>
                                    <div class="row g-2 small">
                                        <div class="col-6 text-muted"><i class="fas fa-gamepad me-2"></i>Multijugador
                                        </div>
                                        <div class="col-6 fw-bold text-end text-white">{{
                                            formatBoolean(gameData.is_multiplayer) }}</div>

                                        <div class="col-6 text-muted"><i class="fas fa-globe me-2"></i>Online</div>
                                        <div class="col-6 fw-bold text-end text-white">{{
                                            formatBoolean(gameData.is_online_multiplayer) }}</div>

                                        <div class="col-6 text-muted"><i class="fas fa-couch me-2"></i>Local/Splitscreen
                                        </div>
                                        <div class="col-6 fw-bold text-end text-white">{{
                                            formatBoolean(gameData.is_local_multiplayer) }}</div>

                                        <div class="col-6 text-muted"><i class="fas fa-handshake me-2"></i>Cooperativo
                                        </div>
                                        <div class="col-6 fw-bold text-end text-white">{{
                                            formatBoolean(gameData.is_cooperative) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5" v-if="gameData.platforms?.length > 0">
                            <h5 class="text-white border-bottom border-secondary pb-2 mb-3">
                                <i class="fas fa-laptop-code me-2 text-primary"></i>Plataformas Compatibles
                            </h5>
                            <div class="d-flex flex-wrap gap-2">
                                <span v-for="plat in gameData.platforms" :key="plat.id"
                                    class="badge bg-dark border border-secondary text-white py-2 px-3">
                                    <i class="fas fa-microchip me-2 text-muted"></i>{{ plat.name }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- ══════ SCREENSHOT GALLERY ══════ -->
        <div v-if="screenshots.length > 0" class="mt-5 pb-5 mb-4">
            <h5 class="text-white border-bottom border-secondary pb-2 mb-4">
                <i class="fas fa-images me-2 text-primary"></i>Capturas de Pantalla
            </h5>
            <div class="screenshot-grid">
                <div v-for="shot in screenshots" :key="shot.id" class="screenshot-thumb"
                    @click="openLightbox(shot.image_url)">
                    <img :src="shot.image_url" :alt="gameData.name" loading="lazy">
                    <div class="thumb-overlay"><i class="fas fa-search-plus"></i></div>
                </div>
            </div>
        </div>

    </AppLayout>

    <!-- ══════ LIGHTBOX ══════ -->
    <Teleport to="body">
        <div v-if="lightboxImg" class="lightbox" @click.self="closeLightbox">
            <button class="lightbox-close" @click="closeLightbox">
                <i class="fas fa-times"></i>
            </button>
            <img :src="lightboxImg" class="lightbox-img" alt="Screenshot">
        </div>
    </Teleport>
</template>

<style scoped>
.display-5 {
    font-size: 2.5rem;
}

@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
        line-height: 1.2;
    }
}

/* ── Screenshot grid ─────────────────────────── */
.screenshot-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: .75rem;
}

.screenshot-thumb {
    position: relative;
    aspect-ratio: 16 / 9;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid var(--border-color);
    transition: transform .25s ease, box-shadow .25s ease;
}

.screenshot-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .35s ease;
}

.thumb-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.4rem;
    opacity: 0;
    transition: opacity .25s ease;
}

.screenshot-thumb:hover img {
    transform: scale(1.07);
}

.screenshot-thumb:hover .thumb-overlay {
    opacity: 1;
}

.screenshot-thumb:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(188, 19, 254, .25);
    border-color: var(--neon-purple);
}

/* ── Lightbox ────────────────────────────────── */
.lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .92);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
    backdrop-filter: blur(6px);
    animation: fadeIn .2s ease;
}

.lightbox-img {
    max-width: 92vw;
    max-height: 88vh;
    object-fit: contain;
    border-radius: 10px;
    box-shadow: 0 0 60px rgba(0, 0, 0, .8);
}

.lightbox-close {
    position: absolute;
    top: 1.2rem;
    right: 1.4rem;
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .2);
    color: #fff;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
}

.lightbox-close:hover {
    background: rgba(255, 255, 255, .25);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}
</style>
