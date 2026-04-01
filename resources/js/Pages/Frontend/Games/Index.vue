<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    initialGames: Object,
    filters: Object,
    genres: Array,
    platforms: Array
});

// Setup form for filters
const filterForm = useForm({
    name: props.filters.name || '',
    genre_id: props.filters.genre_id || '',
    platform: props.filters.platform || '',
});

const applyFilters = () => {
    filterForm.get('/games', {
        preserveState: true,
        preserveScroll: true
    });
};

const clearFilters = () => {
    filterForm.name = '';
    filterForm.genre_id = '';
    filterForm.platform = '';
    applyFilters();
};

const games = computed(() => props.initialGames.data || []);
const links = computed(() => props.initialGames.meta?.links || []);
</script>

<template>
    <Head title="Catálogo de Juegos" />

    <AppLayout v-cloak>
        <div class="row pt-4 g-4 relative">
            
            <!-- SIDEBAR FILTERS -->
            <div class="col-12 col-lg-3">
                <div class="card bg-dark border-secondary sticky-top" style="top: 80px;">
                    <div class="card-header text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-filter me-2 text-primary"></i>Filtros</span>
                        <button v-if="filterForm.name || filterForm.genre_id || filterForm.platform" 
                                @click="clearFilters" 
                                class="btn btn-sm btn-link text-muted p-0 text-decoration-none small">
                            Limpiar
                        </button>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="applyFilters">
                            <div class="mb-3">
                                <label class="text-muted small fw-bold mb-1">Buscar Juego</label>
                                <div class="input-group">
                                    <span class="input-group-text border-secondary text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" v-model="filterForm.name" class="form-control border-secondary text-white" placeholder="Halo...">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="text-muted small fw-bold mb-1">Género</label>
                                <select v-model="filterForm.genre_id" @change="applyFilters" class="form-select border-secondary text-white">
                                    <option value="">Todos los géneros</option>
                                    <option v-for="g in genres" :key="g.id" :value="g.id">{{ g.name }}</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="text-muted small fw-bold mb-1">Plataforma</label>
                                <select v-model="filterForm.platform" @change="applyFilters" class="form-select border-secondary text-white">
                                    <option value="">Todas las plataformas</option>
                                    <option v-for="p in platforms" :key="p.id" :value="p.name">{{ p.name }}</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" :disabled="filterForm.processing">
                                    <span v-if="filterForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                    Aplicar Filtros
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- GAMES GRID -->
            <div class="col-12 col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0 text-white">
                        <i class="fas fa-gamepad me-2 text-primary"></i>Catálogo de Juegos
                    </h4>
                    <span class="text-muted small">Mostrando {{ props.initialGames.meta?.to || 0 }} de {{ props.initialGames.meta?.total || 0 }}</span>
                </div>

                <div v-if="games.length === 0" class="text-center py-5">
                    <i class="fas fa-ghost fa-3x text-muted mb-3"></i>
                    <h5 class="text-white">No se encontraron juegos</h5>
                    <p class="text-muted">Prueba cambiando los filtros o buscando otro nombre.</p>
                </div>

                <div v-else class="row g-4">
                    <div v-for="game in games" :key="game.id" class="col-6 col-md-4 col-xl-3">
                        <Link :href="`/games/${game.slug || game.id}`" class="text-decoration-none">
                            <div class="game-cover-card card h-100 border-secondary bg-dark overflow-hidden position-relative shadow-sm">
                                
                                <!-- Cover Image -->
                                <img v-if="game.cover_image_url" :src="game.cover_image_url" :alt="game.name"
                                     class="card-img-top w-100 h-100 object-fit-cover absolute-img"
                                     loading="lazy"
                                     @error="$event.target.src='https://placehold.co/400x550/202025/555555?text=No+Cover'">
                                <div v-else class="w-100 h-100 d-flex align-items-center justify-content-center bg-black absolute-img">
                                    <i class="fas fa-image fa-2x text-secondary"></i>
                                </div>

                                <!-- Metacritic Badge Floating -->
                                <div v-if="game.metacritic_score" class="position-absolute top-0 end-0 m-2">
                                    <span class="badge" 
                                          :class="game.metacritic_score >= 80 ? 'bg-success' : (game.metacritic_score >= 60 ? 'bg-warning text-dark' : 'bg-danger')">
                                        {{ game.metacritic_score }}
                                    </span>
                                </div>

                                <!-- Hover Overlay Reveal -->
                                <div class="game-overlay d-flex flex-column justify-content-end p-3 text-white">
                                    <h6 class="fw-bold mb-1 text-truncate text-shadow-strong" :title="game.name">{{ game.name }}</h6>
                                    <div class="d-flex flex-wrap gap-1 mt-2 mb-2">
                                        <span v-for="(genre, idx) in game.genres?.slice(0,3)" :key="idx" 
                                              class="badge bg-secondary border border-dark rounded-pill" style="font-size: 0.65rem;">
                                            {{ genre }}
                                        </span>
                                        <span v-if="game.genres?.length > 3" class="badge bg-secondary border border-dark rounded-pill" style="font-size: 0.65rem;">+{{ game.genres.length - 3 }}</span>
                                    </div>
                                    <div class="mt-auto d-grid">
                                        <button class="btn btn-sm btn-outline-info rounded-pill" style="font-size: 0.75rem;">Ver Detalles</button>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="links.length > 3" class="mt-5 d-flex justify-content-center">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm bg-dark">
                            <template v-for="(link, key) in links" :key="key">
                                <li class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                                    <Link v-if="link.url" 
                                         class="page-link bg-black border-secondary text-white" 
                                         :class="{ 'bg-primary text-black fw-bold': link.active }"
                                         :href="link.url"
                                         v-html="link.label">
                                    </Link>
                                    <span v-else class="page-link bg-dark border-secondary text-muted" v-html="link.label"></span>
                                </li>
                            </template>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>
    </AppLayout>

    <style scoped>
    /* Aspect ratio for standard game covers (often roughly 3:4 or 2:3) */
    .game-cover-card {
        aspect-ratio: 3 / 4;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-color: var(--border-color) !important;
    }
    
    .absolute-img {
        position: absolute;
        top: 0;
        left: 0;
        transition: transform 0.4s ease;
    }

    .game-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.6) 40%, transparent 100%);
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .game-cover-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 25px rgba(188, 19, 254, 0.3) !important;
        border-color: var(--neon-purple) !important;
    }

    .theme-vintage .game-cover-card:hover {
        box-shadow: 0 10px 25px rgba(139, 69, 19, 0.3) !important;
        border-color: var(--neon-purple) !important;
    }

    .game-cover-card:hover .absolute-img {
        transform: scale(1.05);
    }

    .game-cover-card:hover .game-overlay {
        opacity: 1;
        transform: translateY(0);
    }

    .text-shadow-strong {
        text-shadow: 1px 1px 3px rgba(0,0,0,1);
    }
    </style>
</template>
