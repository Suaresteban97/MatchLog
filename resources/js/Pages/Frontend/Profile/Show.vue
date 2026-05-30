<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    userProfile: {
        type: Object,
        required: true
    },
    visibility: {
        type: Object,
        default: () => ({})
    },
    gamingStats: {
        type: Object,
        default: () => ({})
    }
});

const activeTab = ref('stats'); // Set 'stats' as default to highlight the new feature

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

// Computeds for easy access
const posts = computed(() => props.userProfile.posts || []);
const games = computed(() => props.userProfile.games || []);
const collections = computed(() => props.userProfile.collections || []);
const socialProfiles = computed(() => props.userProfile.social_profiles || []);
const devices = computed(() => props.userProfile.devices || []);
const groups = computed(() => {
    const hosting = props.userProfile.sessions_hosting || [];
    const participating = props.userProfile.sessions_participating || [];
    const all = [...hosting, ...participating];
    return Array.from(new Map(all.map(item => [item.id, item])).values());
});

const initialLetter = computed(() => {
    return props.userProfile.name ? props.userProfile.name.charAt(0).toUpperCase() : '?';
});

const getGameCover = (game) => {
    return game.cover_image_url || null; 
};

// Background Banner (Use currently playing or a default cool gaming background)
const bannerUrl = computed(() => {
    if (props.gamingStats?.currently_playing?.cover_image_url) {
        return props.gamingStats.currently_playing.cover_image_url;
    }
    return 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?q=80&w=2000&auto=format&fit=crop';
});

// Collection Modal State
const selectedCollection = ref(null);
const showCollectionModal = ref(false);

const openCollectionModal = (collection) => {
    selectedCollection.value = collection;
    showCollectionModal.value = true;
};

const closeCollectionModal = () => {
    showCollectionModal.value = false;
    selectedCollection.value = null;
};
</script>

<template>
    <Head :title="`Perfil de ${userProfile.name}`" />

    <AppLayout>
        <div class="steam-profile-container pb-5">
            <!-- Full Width Banner Header -->
            <div class="profile-header-wrapper position-relative overflow-hidden mb-4 rounded shadow-lg border border-secondary" style="margin-top: 1.5rem;">
                <!-- Blurred Background for Banner -->
                <div class="profile-banner-bg position-absolute w-100 h-100" 
                     :style="`background-image: url('${bannerUrl}'); background-size: cover; background-position: center; filter: blur(8px) brightness(0.4); z-index: 0;`">
                </div>
                
                <div class="profile-header-content position-relative p-4 d-flex flex-column flex-md-row align-items-center align-items-md-end" style="z-index: 1; min-height: 250px;">
                    <!-- Avatar Area -->
                    <div class="avatar-wrapper position-relative me-md-4 mb-3 mb-md-0">
                        <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center border border-3 border-dark shadow-lg" 
                             style="width: 160px; height: 160px; font-size: 5rem; font-weight: bold;">
                            {{ initialLetter }}
                        </div>
                        <div class="position-absolute bottom-0 end-0 bg-success border border-dark rounded-circle" style="width: 25px; height: 25px; transform: translate(25%, 25%);" title="Online"></div>
                    </div>
                    
                    <!-- Profile Info -->
                    <div class="profile-info flex-grow-1 text-center text-md-start mb-3 mb-md-0">
                        <h1 class="text-white fw-bold mb-1" style="font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">{{ userProfile.name }}</h1>
                        <p class="text-light fs-5 mb-0" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.8);">Miembro desde {{ formatDate(userProfile.created_at) }}</p>
                        
                        <div class="d-flex justify-content-center justify-content-md-start gap-2 mt-3">
                            <button class="btn btn-primary px-4 shadow">
                                <i class="fas fa-user-plus me-1"></i> Añadir Amigo
                            </button>
                            <button class="btn btn-outline-light px-3" title="Copiar enlace del perfil" @click="() => navigator.clipboard.writeText(window.location.href)">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Profile Level/Stats Summary (Right Side) -->
                    <div class="profile-level text-center ms-md-4 bg-dark bg-opacity-75 p-3 rounded border border-secondary shadow-sm">
                        <div class="text-uppercase text-muted small fw-bold mb-1">Nivel</div>
                        <div class="text-white fw-bold fs-2 rounded-circle border border-primary d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                            {{ Math.max(1, Math.floor((games.length + posts.length) / 5)) }}
                        </div>
                        <div class="d-flex gap-3 justify-content-center mt-2 small text-light">
                            <div class="text-center">
                                <i class="fas fa-gamepad text-primary d-block mb-1"></i>
                                <span>{{ games.length }}</span>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-comment-alt text-info d-block mb-1"></i>
                                <span>{{ posts.length }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Sidebar (Socials & Devices) -->
                <div class="col-lg-3 mb-4">
                    <!-- Navigation / Tabs (Sidebar style on large screens) -->
                    <div class="list-group rounded shadow-sm border border-secondary mb-4 bg-dark">
                        <button class="list-group-item list-group-item-action bg-transparent text-light border-0 d-flex align-items-center py-3" 
                                :class="{ 'active-tab': activeTab === 'stats' }" @click="activeTab = 'stats'">
                            <i class="fas fa-chart-line fa-fw me-3" :class="activeTab === 'stats' ? 'text-primary' : 'text-muted'"></i> 
                            <span class="fw-bold">Gaming Stats</span>
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-light border-0 d-flex align-items-center py-3" 
                                :class="{ 'active-tab': activeTab === 'games' }" @click="activeTab = 'games'">
                            <i class="fas fa-ghost fa-fw me-3" :class="activeTab === 'games' ? 'text-primary' : 'text-muted'"></i> 
                            <span class="fw-bold">Backlog ({{ games.length }})</span>
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-light border-0 d-flex align-items-center py-3" 
                                :class="{ 'active-tab': activeTab === 'posts' }" @click="activeTab = 'posts'">
                            <i class="fas fa-comment-alt fa-fw me-3" :class="activeTab === 'posts' ? 'text-primary' : 'text-muted'"></i> 
                            <span class="fw-bold">Actividad ({{ posts.length }})</span>
                        </button>
                        <button class="list-group-item list-group-item-action bg-transparent text-light border-0 d-flex align-items-center py-3" 
                                :class="{ 'active-tab': activeTab === 'collections' }" @click="activeTab = 'collections'">
                            <i class="fas fa-layer-group fa-fw me-3" :class="activeTab === 'collections' ? 'text-primary' : 'text-muted'"></i> 
                            <span class="fw-bold">Colecciones ({{ collections.length }})</span>
                        </button>
                    </div>

                    <!-- Redes Sociales -->
                    <div class="card shadow-sm border-secondary bg-dark text-light mb-4">
                        <div class="card-header bg-black border-secondary">
                            <h6 class="mb-0 text-uppercase fw-bold text-muted small"><i class="fas fa-globe me-2"></i> Perfiles Vinculados</h6>
                        </div>
                        <div class="card-body">
                            <div v-if="socialProfiles.length > 0" class="d-flex flex-column gap-2">
                                <a v-for="sp in socialProfiles" :key="sp.id" :href="sp.profile_url || '#'" target="_blank" class="text-decoration-none bg-black rounded p-2 d-flex align-items-center border border-secondary hover-lift">
                                    <i class="fas fa-gamepad text-primary me-3 ms-1 fa-lg"></i>
                                    <div>
                                        <div class="small text-muted" style="line-height: 1;">{{ sp.social_platform?.name || 'Plataforma' }}</div>
                                        <div class="fw-bold text-light">{{ sp.gamertag }}</div>
                                    </div>
                                </a>
                            </div>
                            <p v-else class="text-muted small fst-italic mb-0">No hay perfiles vinculados.</p>
                        </div>
                    </div>

                    <!-- Dispositivos -->
                    <div class="card shadow-sm border-secondary bg-dark text-light">
                        <div class="card-header bg-black border-secondary">
                            <h6 class="mb-0 text-uppercase fw-bold text-muted small"><i class="fas fa-desktop me-2"></i> Setup & Dispositivos</h6>
                        </div>
                        <div class="card-body">
                            <div v-if="devices.length > 0" class="d-flex flex-wrap gap-2">
                                <div v-for="dev in devices" :key="dev.id" class="badge bg-black border border-secondary text-light px-3 py-2 rounded-pill shadow-sm d-flex align-items-center">
                                    <i class="fas fa-microchip me-2 text-info"></i> {{ dev.pivot?.custom_name || dev.name }}
                                </div>
                            </div>
                            <p v-else class="text-muted small fst-italic mb-0">No hay dispositivos vinculados.</p>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="col-lg-9">
                    
                    <!-- ========================================== -->
                    <!-- GAMING STATS TAB -->
                    <!-- ========================================== -->
                    <div v-show="activeTab === 'stats'" class="animate-fade-in">
                        
                        <!-- Showcase: Currently Playing -->
                        <div class="card bg-dark border-secondary shadow-lg mb-4 overflow-hidden showcase-card">
                            <div class="card-header bg-black border-secondary d-flex align-items-center">
                                <h5 class="mb-0 text-uppercase text-primary fw-bold fs-6"><i class="fas fa-star me-2"></i> Jugando Actualmente</h5>
                            </div>
                            <div class="card-body p-0 position-relative">
                                <div v-if="gamingStats.currently_playing" class="d-flex flex-column flex-md-row">
                                    <div class="showcase-cover" :style="`background-image: url('${getGameCover(gamingStats.currently_playing)}'); width: 100%; max-width: 300px; height: 350px; background-size: cover; background-position: center;`">
                                    </div>
                                    <div class="p-4 d-flex flex-column justify-content-center flex-grow-1 bg-black bg-opacity-50">
                                        <h2 class="text-white fw-bold mb-3">{{ gamingStats.currently_playing.name }}</h2>
                                        <p class="text-light mb-4">{{ gamingStats.currently_playing.description?.substring(0, 200) || 'Sin descripción disponible.' }}...</p>
                                        <div class="d-flex gap-4">
                                            <div>
                                                <div class="text-uppercase text-muted small fw-bold">Desarrollador</div>
                                                <div class="text-light">{{ gamingStats.currently_playing.developer || 'N/A' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-uppercase text-muted small fw-bold">Horas Jugadas</div>
                                                <div class="text-primary fw-bold fs-5">{{ gamingStats.currently_playing.pivot?.hours_played || 0 }} hrs</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-5 text-center text-muted">
                                    <i class="fas fa-ghost fa-3x mb-3 opacity-50"></i>
                                    <h5>No está jugando nada actualmente</h5>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="row g-4 mb-4">
                            <!-- Total Hours -->
                            <div class="col-md-6 col-lg-3">
                                <div class="card bg-dark border-secondary shadow h-100 text-center py-4 hover-lift">
                                    <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                    <h2 class="text-white fw-bold mb-0">{{ gamingStats.total_hours_played || 0 }}</h2>
                                    <p class="text-muted text-uppercase small fw-bold mb-0">Horas Totales</p>
                                </div>
                            </div>
                            <!-- Most Played Genre -->
                            <div class="col-md-6 col-lg-3">
                                <div class="card bg-dark border-secondary shadow h-100 text-center py-4 hover-lift">
                                    <i class="fas fa-fire fa-3x text-danger mb-3"></i>
                                    <h4 class="text-white fw-bold mb-0 text-truncate px-2">{{ gamingStats.most_played_genre || 'N/A' }}</h4>
                                    <p class="text-muted text-uppercase small fw-bold mb-0">Género Favorito</p>
                                </div>
                            </div>
                            <!-- Most Used Platform -->
                            <div class="col-md-6 col-lg-3">
                                <div class="card bg-dark border-secondary shadow h-100 text-center py-4 hover-lift">
                                    <i class="fas fa-gamepad fa-3x text-primary mb-3"></i>
                                    <h4 class="text-white fw-bold mb-0 text-truncate px-2">{{ gamingStats.most_used_platform || 'N/A' }}</h4>
                                    <p class="text-muted text-uppercase small fw-bold mb-0">Plataforma Principal</p>
                                </div>
                            </div>
                            <!-- Total Games -->
                            <div class="col-md-6 col-lg-3">
                                <div class="card bg-dark border-secondary shadow h-100 text-center py-4 hover-lift">
                                    <i class="fas fa-layer-group fa-3x text-success mb-3"></i>
                                    <h2 class="text-white fw-bold mb-0">{{ games.length }}</h2>
                                    <p class="text-muted text-uppercase small fw-bold mb-0">Juegos en Biblioteca</p>
                                </div>
                            </div>
                        </div>

                        <!-- Games by Status -->
                        <div class="card bg-dark border-secondary shadow-sm">
                            <div class="card-header bg-black border-secondary">
                                <h6 class="mb-0 text-uppercase fw-bold text-muted small"><i class="fas fa-chart-pie me-2"></i> Estado de la Biblioteca</h6>
                            </div>
                            <div class="card-body">
                                <div v-if="Object.keys(gamingStats.games_by_status || {}).length > 0" class="d-flex flex-wrap gap-3">
                                    <div v-for="(count, statusName) in gamingStats.games_by_status" :key="statusName" 
                                         class="flex-grow-1 bg-black border border-secondary rounded p-3 text-center">
                                        <h3 class="text-white fw-bold mb-1">{{ count }}</h3>
                                        <div class="text-muted small text-uppercase fw-bold">{{ statusName }}</div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-muted">
                                    <p class="mb-0">No hay datos de estado de juegos.</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ========================================== -->
                    <!-- GAMES TAB (BACKLOG) -->
                    <!-- ========================================== -->
                    <div v-show="activeTab === 'games'" class="animate-fade-in">
                        <div v-if="games.length === 0" class="card bg-dark border-0 shadow-sm text-center py-5 text-muted">
                            <i class="fas fa-ghost fa-3x mb-3 opacity-50"></i>
                            <h5>Backlog Vacío</h5>
                            <p>No se han agregado juegos a la lista.</p>
                        </div>
                        
                        <div v-else class="row g-3">
                            <div v-for="game in games" :key="game.id" class="col-xl-3 col-lg-4 col-sm-6">
                                <a :href="'/games/' + (game.slug || game.id)" class="text-decoration-none d-block h-100">
                                    <div class="card bg-black border-0 text-light h-100 shadow-sm game-card-hover overflow-hidden position-relative rounded">
                                        <div class="game-cover-container bg-dark d-flex align-items-center justify-content-center w-100" style="height: 250px;">
                                            <img v-if="getGameCover(game)" :src="getGameCover(game)" :alt="game.name" class="w-100 h-100" style="object-fit: cover; object-position: top;" />
                                            <i v-else class="fas fa-gamepad fa-3x text-muted opacity-50"></i>
                                        </div>
                                        <div class="position-absolute bottom-0 w-100 p-2" style="background: linear-gradient(to top, rgba(0,0,0,1) 10%, rgba(0,0,0,0.8) 50%, transparent);">
                                            <h6 class="mb-1 text-truncate fw-bold text-white shadow-text" :title="game.name">{{ game.name }}</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-primary fw-bold">{{ game.pivot?.hours_played || 0 }} hrs</small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- POSTS TAB -->
                    <!-- ========================================== -->
                    <div v-show="activeTab === 'posts'" class="animate-fade-in">
                        <div v-if="posts.length === 0" class="card bg-dark border-0 shadow-sm text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                            <h5>No hay publicaciones</h5>
                            <p>Este usuario aún no ha publicado nada.</p>
                        </div>
                        
                        <div v-else class="d-flex flex-column gap-4">
                            <div v-for="post in posts" :key="post.id" class="card shadow-sm border border-secondary bg-dark text-light">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 45px; height: 45px; font-weight: bold;">
                                            {{ initialLetter }}
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{ userProfile.name }}</h6>
                                            <small class="text-muted">{{ formatDate(post.created_at) }}</small>
                                        </div>
                                    </div>
                                    <p class="card-text mb-0 fs-5" style="white-space: pre-wrap;">{{ post.content }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- COLLECTIONS TAB -->
                    <!-- ========================================== -->
                    <div v-show="activeTab === 'collections'" class="animate-fade-in">
                        <div v-if="collections.length === 0" class="card bg-dark border-0 shadow-sm text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                            <h5>Sin Colecciones</h5>
                            <p>El usuario no ha creado ninguna colección aún.</p>
                        </div>
                        
                        <div v-else class="row g-3">
                            <div v-for="collection in collections" :key="collection.id" class="col-md-6">
                                <div class="card bg-dark border border-secondary text-light h-100 hover-lift shadow-sm" style="cursor: pointer;" @click="openCollectionModal(collection)">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="fw-bold mb-0 text-white"><i class="fas fa-layer-group me-2 text-info"></i>{{ collection.name }}</h5>
                                        </div>
                                        <p class="text-muted small mb-0">{{ collection.description || 'Sin descripción' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Collection Modal -->
        <div v-if="showCollectionModal" class="modal-overlay d-flex align-items-center justify-content-center" @click.self="closeCollectionModal" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.8); z-index: 1050; backdrop-filter: blur(5px);">
            <div class="modal-container card bg-dark border-secondary shadow-lg" style="max-width: 600px; width: 90%; max-height: 80vh; display: flex; flex-direction: column;">
                <div class="card-header bg-black border-secondary d-flex justify-content-between align-items-center p-3">
                    <h5 class="mb-0 text-white fw-bold"><i class="fas fa-layer-group me-2 text-primary"></i>{{ selectedCollection?.name }}</h5>
                    <button @click="closeCollectionModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="card-body text-white overflow-auto p-0">
                    <div class="p-3 bg-dark border-bottom border-secondary">
                        <p class="text-muted small mb-0">{{ selectedCollection?.description || 'Sin descripción' }}</p>
                    </div>
                    
                    <div v-if="selectedCollection?.games && selectedCollection.games.length > 0" class="list-group list-group-flush">
                        <div v-for="game in selectedCollection.games" :key="game.id" class="list-group-item bg-dark text-white border-secondary d-flex align-items-center p-3">
                            <img :src="getGameCover(game) || '/images/game-placeholder.jpg'" class="rounded me-3 shadow-sm" style="width: 50px; height: 65px; object-fit: cover;" :alt="game.name">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold fs-5">{{ game.name }}</h6>
                                <small class="text-muted">{{ game.developer || 'Desconocido' }}</small>
                            </div>
                            <a :href="'/games/' + (game.slug || game.id)" class="btn btn-sm btn-outline-primary rounded-pill">
                                Ver
                            </a>
                        </div>
                    </div>
                    <div v-else class="text-center py-5 text-muted">
                        <i class="fas fa-ghost fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0 fs-5">Esta colección está vacía.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.4) !important;
}

.game-card-hover {
    transition: all 0.3s ease;
    border: 1px solid transparent !important;
}
.game-card-hover:hover {
    transform: scale(1.02);
    border-color: var(--bs-primary) !important;
    box-shadow: 0 0 15px rgba(13, 110, 253, 0.3) !important;
}

.shadow-text {
    text-shadow: 0 2px 4px rgba(0,0,0,0.9);
}

.active-tab {
    background-color: rgba(13, 110, 253, 0.1) !important;
    border-left: 4px solid var(--bs-primary) !important;
}

.showcase-card {
    border: 1px solid rgba(255,255,255,0.1);
}

.list-group-item-action:hover {
    background-color: rgba(255,255,255,0.05) !important;
}
</style>
