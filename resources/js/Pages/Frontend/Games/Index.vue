<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    initialGames: Object,
    filters: Object,
    genres: Array,
    platforms: Array,
});

// ── Filter state seeded from URL (persists on back/forward) ─────────────
const form = ref({
    name: props.filters.name || '',
    genre_id: props.filters.genre_id || '',
    platform_id: props.filters.platform_id || '',
    developer: props.filters.developer || '',
    publisher: props.filters.publisher || '',
    release_year: props.filters.release_year || '',
    metacritic_score: props.filters.metacritic_score || '',
    sort_by: props.filters.sort_by || 'metacritic_score',
    sort_dir: props.filters.sort_dir || 'desc',
    is_multiplayer: props.filters.is_multiplayer ? '1' : '',
    is_cooperative: props.filters.is_cooperative ? '1' : '',
    is_online_multiplayer: props.filters.is_online_multiplayer ? '1' : '',
    is_local_multiplayer: props.filters.is_local_multiplayer ? '1' : '',
});

// ── Sidebar open/close on mobile ──────────────────────────────────────
const sidebarOpen = ref(false);

// ── Build sort label for display ─────────────────────────────────────
const sortOptions = [
    { value: 'metacritic_score|desc', label: 'Mejor Puntuados' },
    { value: 'metacritic_score|asc', label: 'Menor Puntuación' },
    { value: 'name|asc', label: 'A → Z' },
    { value: 'name|desc', label: 'Z → A' },
    { value: 'release_date|desc', label: 'Más Recientes' },
    { value: 'release_date|asc', label: 'Más Antiguos' },
];

const sortValue = computed({
    get: () => `${form.value.sort_by}|${form.value.sort_dir}`,
    set: (val) => {
        const [by, dir] = val.split('|');
        form.value.sort_by = by;
        form.value.sort_dir = dir;
        applyFilters();
    },
});

// ── Games & pagination from Inertia props ────────────────────────────
const games = computed(() => props.initialGames.data || []);
const meta = computed(() => props.initialGames.meta || {});
const links = computed(() => meta.value.links || []);

// ── Active filter count badge ─────────────────────────────────────────
const activeFilterCount = computed(() => {
    const f = form.value;
    return [f.name, f.genre_id, f.platform_id, f.developer, f.publisher,
    f.release_year, f.metacritic_score,
    f.is_multiplayer, f.is_cooperative,
    f.is_online_multiplayer, f.is_local_multiplayer]
        .filter(Boolean).length;
});

// ── Apply / clear ─────────────────────────────────────────────────────
const applyFilters = () => {
    // Strip falsy values so URL stays clean
    const params = Object.fromEntries(
        Object.entries(form.value).filter(([, v]) => v !== '' && v !== null)
    );
    router.get('/games', params, { preserveState: true, preserveScroll: true });
    sidebarOpen.value = false;
};

const clearFilters = () => {
    form.value = {
        name: '', genre_id: '', platform_id: '', developer: '', publisher: '',
        release_year: '', metacritic_score: '',
        sort_by: 'metacritic_score', sort_dir: 'desc',
        is_multiplayer: '', is_cooperative: '',
        is_online_multiplayer: '', is_local_multiplayer: '',
    };
    applyFilters();
};

// Year range options for the release year dropdown
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 35 }, (_, i) => currentYear - i);

// Score range options 
const scoreOptions = [50, 55, 60, 65, 70, 75, 80, 85, 90, 95];
</script>

<template>

    <Head title="Catálogo de Juegos" />

    <AppLayout v-cloak>

        <!-- Mobile: Filter button & sort bar -->
        <div class="d-lg-none d-flex justify-content-between align-items-center mb-3 pt-3">
            <button @click="sidebarOpen = !sidebarOpen" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fas fa-sliders-h me-1"></i>Filtros
                <span v-if="activeFilterCount" class="badge rounded-pill ms-1" style="background: var(--neon-purple);">
                    {{ activeFilterCount }}
                </span>
            </button>
            <select v-model="sortValue" class="form-select form-select-sm w-auto border-secondary rounded-pill">
                <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
        </div>

        <div class="row g-4" :class="{ 'pt-4': true }">

            <!-- ══════════════════ SIDEBAR ══════════════════ -->
            <div class="col-12 col-lg-3">
                <!-- Overlay on mobile -->
                <div v-if="sidebarOpen" class="d-lg-none position-fixed top-0 start-0 w-100 h-100"
                    style="background: rgba(0,0,0,0.6); z-index:1040;" @click="sidebarOpen = false"></div>

                <div class="card border-secondary sticky-top" :class="[
                    'd-lg-block',
                    sidebarOpen ? 'd-block position-fixed top-0 start-0 h-100 overflow-auto' : 'd-none'
                ]" style="top: 70px; z-index:1045; width:300px; max-width:90vw;"
                    :style="{ width: sidebarOpen ? '300px' : undefined }">

                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros</span>
                        <div class="d-flex gap-2 align-items-center">
                            <button v-if="activeFilterCount" @click="clearFilters"
                                class="btn btn-sm btn-link text-muted p-0 text-decoration-none small">
                                Limpiar ({{ activeFilterCount }})
                            </button>
                            <button class="d-lg-none btn-close btn-close-white small"
                                @click="sidebarOpen = false"></button>
                        </div>
                    </div>

                    <div class="card-body pb-4">

                        <!-- Search -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text border-secondary text-muted"><i
                                        class="fas fa-search"></i></span>
                                <input type="text" v-model="form.name" @keyup.enter="applyFilters"
                                    class="form-control border-secondary" placeholder="Halo, FIFA…">
                            </div>
                        </div>

                        <!-- Sort (desktop) -->
                        <div class="mb-3 d-none d-lg-block">
                            <label class="form-label small fw-bold text-muted">Ordenar por</label>
                            <select v-model="sortValue" class="form-select border-secondary">
                                <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Genre -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Género</label>
                            <select v-model="form.genre_id" @change="applyFilters" class="form-select border-secondary">
                                <option value="">Todos</option>
                                <option v-for="g in genres" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>

                        <!-- Platform -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Plataforma</label>
                            <select v-model="form.platform_id" @change="applyFilters"
                                class="form-select border-secondary">
                                <option value="">Todas</option>
                                <option v-for="p in platforms" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <!-- Min Metacritic -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">
                                Puntuación mín.
                                <span v-if="form.metacritic_score" class="badge ms-1"
                                    :class="form.metacritic_score >= 80 ? 'bg-success' : (form.metacritic_score >= 60 ? 'bg-warning text-dark' : 'bg-danger')">
                                    {{ form.metacritic_score }}+
                                </span>
                            </label>
                            <select v-model="form.metacritic_score" @change="applyFilters"
                                class="form-select border-secondary">
                                <option value="">Cualquiera</option>
                                <option v-for="s in scoreOptions" :key="s" :value="s">{{ s }}+</option>
                            </select>
                        </div>

                        <!-- Release Year -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Año de lanzamiento</label>
                            <select v-model="form.release_year" @change="applyFilters"
                                class="form-select border-secondary">
                                <option value="">Cualquier año</option>
                                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>

                        <!-- Developer -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Desarrollador</label>
                            <input type="text" v-model="form.developer" @keyup.enter="applyFilters"
                                class="form-control border-secondary" placeholder="Rockstar, CD Projekt…">
                        </div>

                        <!-- Publisher -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Publisher</label>
                            <input type="text" v-model="form.publisher" @keyup.enter="applyFilters"
                                class="form-control border-secondary" placeholder="EA, Ubisoft…">
                        </div>

                        <!-- Multiplayer toggles -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Modalidades</label>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                <button @click="form.is_multiplayer = form.is_multiplayer ? '' : '1'; applyFilters()"
                                    class="btn btn-sm rounded-pill"
                                    :class="form.is_multiplayer ? 'btn-primary' : 'btn-outline-secondary'">
                                    <i class="fas fa-users me-1"></i>Multi
                                </button>
                                <button @click="form.is_cooperative = form.is_cooperative ? '' : '1'; applyFilters()"
                                    class="btn btn-sm rounded-pill"
                                    :class="form.is_cooperative ? 'btn-primary' : 'btn-outline-secondary'">
                                    <i class="fas fa-handshake me-1"></i>Co-op
                                </button>
                                <button
                                    @click="form.is_online_multiplayer = form.is_online_multiplayer ? '' : '1'; applyFilters()"
                                    class="btn btn-sm rounded-pill"
                                    :class="form.is_online_multiplayer ? 'btn-primary' : 'btn-outline-secondary'">
                                    <i class="fas fa-globe me-1"></i>Online
                                </button>
                                <button
                                    @click="form.is_local_multiplayer = form.is_local_multiplayer ? '' : '1'; applyFilters()"
                                    class="btn btn-sm rounded-pill"
                                    :class="form.is_local_multiplayer ? 'btn-primary' : 'btn-outline-secondary'">
                                    <i class="fas fa-couch me-1"></i>Local
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button @click="applyFilters" class="btn btn-primary rounded-pill">
                                <i class="fas fa-search me-2"></i>Aplicar
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ══════════════════ GRID ══════════════════ -->
            <div class="col-12 col-lg-9">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 pt-4 pt-lg-0">
                    <h4 class="mb-0">
                        <i class="fas fa-gamepad me-2 text-primary"></i>Catálogo de Juegos
                    </h4>
                    <span class="text-muted small">
                        {{ meta.from }}–{{ meta.to }} de {{ meta.total }} juegos
                    </span>
                </div>

                <!-- Active filter chips -->
                <div v-if="activeFilterCount" class="d-flex flex-wrap gap-2 mb-3">
                    <span v-if="form.name" class="badge border border-secondary text-muted rounded-pill py-2 px-3">
                        "{{ form.name }}" <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.name = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.genre_id" class="badge border border-secondary text-muted rounded-pill py-2 px-3">
                        Género: {{genres.find(g => g.id == form.genre_id)?.name}}
                        <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.genre_id = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.platform_id"
                        class="badge border border-secondary text-muted rounded-pill py-2 px-3">
                        {{platforms.find(p => p.id == form.platform_id)?.name}}
                        <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.platform_id = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.metacritic_score" class="badge bg-success rounded-pill py-2 px-3">
                        Score {{ form.metacritic_score }}+
                        <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.metacritic_score = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.release_year"
                        class="badge border border-secondary text-muted rounded-pill py-2 px-3">
                        Año: {{ form.release_year }}
                        <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.release_year = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.is_multiplayer" class="badge bg-primary rounded-pill py-2 px-3">
                        Multi <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.is_multiplayer = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.is_cooperative" class="badge bg-primary rounded-pill py-2 px-3">
                        Co-op <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.is_cooperative = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.is_online_multiplayer" class="badge bg-primary rounded-pill py-2 px-3">
                        Online <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.is_online_multiplayer = ''; applyFilters()"></button>
                    </span>
                    <span v-if="form.is_local_multiplayer" class="badge bg-primary rounded-pill py-2 px-3">
                        Local <button class="btn-close btn-close-white ms-1" style="font-size:.6rem;"
                            @click="form.is_local_multiplayer = ''; applyFilters()"></button>
                    </span>
                </div>

                <!-- Empty state -->
                <div v-if="games.length === 0" class="text-center py-5">
                    <i class="fas fa-ghost fa-3x text-muted mb-3"></i>
                    <h5>No se encontraron juegos</h5>
                    <p class="text-muted">Prueba cambiando los filtros.</p>
                    <button @click="clearFilters" class="btn btn-outline-secondary rounded-pill">
                        <i class="fas fa-times me-1"></i>Limpiar filtros
                    </button>
                </div>

                <!-- Game cards grid -->
                <div v-else class="row g-3">
                    <div v-for="game in games" :key="game.id" class="col-6 col-md-4 col-xl-3">
                        <Link :href="`/games/${game.slug || game.id}`" class="text-decoration-none">
                            <div
                                class="game-cover-card card border-secondary overflow-hidden position-relative shadow-sm">

                                <!-- Cover image -->
                                <img v-if="game.cover_image_url" :src="game.cover_image_url" :alt="game.name"
                                    class="cover-img" loading="lazy"
                                    @error="$event.target.src = 'https://placehold.co/400x550/202025/555555?text=No+Cover'">
                                <div v-else class="cover-img no-cover d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-2x text-secondary"></i>
                                </div>

                                <!-- Metacritic badge top-right -->
                                <div v-if="game.metacritic_score" class="position-absolute top-0 end-0 m-2"
                                    style="z-index:2;">
                                    <span class="badge fw-bold shadow" :class="game.metacritic_score >= 80 ? 'bg-success'
                                        : (game.metacritic_score >= 60 ? 'bg-warning text-dark'
                                            : 'bg-danger')">
                                        {{ game.metacritic_score }}
                                    </span>
                                </div>

                                <!-- Always-visible name footer -->
                                <div class="card-name-bar">
                                    <span class="card-name-text">{{ game.name }}</span>
                                </div>

                                <!-- Hover overlay (extra detail) -->
                                <div class="game-overlay d-flex flex-column justify-content-end p-3">
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        <span v-for="(genre, idx) in (game.genres || []).slice(0, 3)" :key="idx"
                                            class="badge bg-secondary rounded-pill" style="font-size:.6rem;">
                                            {{ genre }}
                                        </span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-light rounded-pill" style="font-size:.72rem;">
                                        Ver Detalles
                                    </button>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="links.length > 3" class="mt-5 d-flex justify-content-center">
                    <ul class="pagination pagination-sm mb-0">
                        <template v-for="(link, key) in links" :key="key">
                            <li class="page-item" :class="{ active: link.active, disabled: !link.url }">
                                <Link v-if="link.url" class="page-link border-secondary"
                                    :class="{ 'fw-bold': link.active }" :href="link.url" preserve-state
                                    v-html="link.label">
                                </Link>
                                <span v-else class="page-link border-secondary text-muted" v-html="link.label"></span>
                            </li>
                        </template>
                    </ul>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
