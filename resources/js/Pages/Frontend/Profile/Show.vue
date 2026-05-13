<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    userProfile: {
        type: Object,
        required: true
    }
});

const activeTab = ref('posts'); // posts, games, collections, groups

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
    // Deduplicate just in case
    const all = [...hosting, ...participating];
    return Array.from(new Map(all.map(item => [item.id, item])).values());
});

const initialLetter = computed(() => {
    return props.userProfile.name ? props.userProfile.name.charAt(0).toUpperCase() : '?';
});

// Helper for generic game cover
const getGameCover = (game) => {
    return game.cover_image_url || null; // fallback will be handled in template
};
</script>

<template>
    <Head :title="`Perfil de ${userProfile.name}`" />

    <AppLayout>
        <div class="row pt-4 pb-5">
            <!-- Left Sidebar (Profile Info, Socials, Devices) -->
            <div class="col-lg-4 col-md-5 mb-4">
                <div class="card shadow-lg border-0 bg-dark text-light mb-4 profile-card overflow-hidden">
                    <div class="profile-header-bg bg-primary opacity-50" style="height: 120px;"></div>
                    <div class="card-body text-center position-relative pt-0">
                        <!-- Generic Avatar -->
                        <div class="avatar-container position-relative d-inline-block shadow" style="margin-top: -60px;">
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center border border-4 border-dark" style="width: 120px; height: 120px; font-size: 3rem; font-weight: bold;">
                                {{ initialLetter }}
                            </div>
                            <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-dark" style="width: 25px; height: 25px;" title="Online"></div>
                        </div>

                        <h3 class="mt-3 fw-bold mb-1">{{ userProfile.name }}</h3>
                        <p class="text-muted small mb-3">Miembro desde {{ formatDate(userProfile.created_at) }}</p>

                        <div class="d-flex justify-content-center gap-2 mb-4">
                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                <i class="fas fa-user-plus me-1"></i> Seguir
                            </button>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="Copiar enlace del perfil" @click="() => navigator.clipboard.writeText(window.location.href)">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                        
                        <hr class="border-secondary">

                        <!-- Social Profiles -->
                        <div class="text-start mb-4">
                            <h6 class="text-uppercase text-muted small fw-bold mb-3"><i class="fas fa-globe me-2"></i> Redes Sociales</h6>
                            <div v-if="socialProfiles.length > 0" class="d-flex flex-column gap-2">
                                <a v-for="sp in socialProfiles" :key="sp.id" :href="sp.profile_url || '#'" target="_blank" class="text-decoration-none bg-black rounded p-2 d-flex align-items-center hover-lift border border-secondary">
                                    <div class="bg-secondary rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <i class="fas fa-gamepad text-light"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted" style="line-height: 1;">{{ sp.social_platform?.name || 'Plataforma' }}</div>
                                        <div class="fw-bold text-light">{{ sp.gamertag }}</div>
                                    </div>
                                </a>
                            </div>
                            <p v-else class="text-muted small fst-italic mb-0">No hay perfiles vinculados.</p>
                        </div>

                        <!-- Devices -->
                        <div class="text-start mb-2">
                            <h6 class="text-uppercase text-muted small fw-bold mb-3"><i class="fas fa-desktop me-2"></i> Dispositivos Vinculados</h6>
                            <div v-if="devices.length > 0" class="d-flex flex-wrap gap-2">
                                <div v-for="dev in devices" :key="dev.id" class="badge bg-black border border-secondary text-light px-3 py-2 rounded-pill shadow-sm d-flex align-items-center">
                                    <i class="fas fa-laptop me-2 text-info"></i> {{ dev.pivot?.custom_name || dev.name }}
                                </div>
                            </div>
                            <p v-else class="text-muted small fst-italic mb-0">No hay dispositivos vinculados.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-lg-8 col-md-7">
                <!-- Navigation Tabs -->
                <ul class="nav nav-pills nav-fill bg-dark p-2 rounded shadow-sm mb-4 border border-secondary">
                    <li class="nav-item">
                        <button class="nav-link text-light fw-bold rounded" :class="{ 'active bg-primary': activeTab === 'posts' }" @click="activeTab = 'posts'">
                            <i class="fas fa-comment-alt me-1"></i> Publicaciones ({{ posts.length }})
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-light fw-bold rounded" :class="{ 'active bg-primary': activeTab === 'games' }" @click="activeTab = 'games'">
                            <i class="fas fa-gamepad me-1"></i> Backlog ({{ games.length }})
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-light fw-bold rounded" :class="{ 'active bg-primary': activeTab === 'collections' }" @click="activeTab = 'collections'">
                            <i class="fas fa-layer-group me-1"></i> Colecciones ({{ collections.length }})
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link text-light fw-bold rounded" :class="{ 'active bg-primary': activeTab === 'groups' }" @click="activeTab = 'groups'">
                            <i class="fas fa-users me-1"></i> Grupos ({{ groups.length }})
                        </button>
                    </li>
                </ul>

                <!-- TAB CONTENT -->
                
                <!-- Posts Tab -->
                <div v-show="activeTab === 'posts'" class="animate-fade-in">
                    <div v-if="posts.length === 0" class="card bg-dark border-0 shadow-sm text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <h5>No hay publicaciones</h5>
                        <p>Este usuario aún no ha publicado nada.</p>
                    </div>
                    
                    <div v-else class="d-flex flex-column gap-4">
                        <div v-for="post in posts" :key="post.id" class="card shadow-sm border-0 bg-dark text-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 45px; height: 45px; font-weight: bold;">
                                        {{ initialLetter }}
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ userProfile.name }}</h6>
                                        <small class="text-muted">{{ formatDate(post.created_at) }}</small>
                                    </div>
                                </div>
                                <p class="card-text mb-0" style="white-space: pre-wrap; font-size: 1.05rem;">{{ post.content }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Games Tab (Backlog) -->
                <div v-show="activeTab === 'games'" class="animate-fade-in">
                    <div v-if="games.length === 0" class="card bg-dark border-0 shadow-sm text-center py-5 text-muted">
                        <i class="fas fa-ghost fa-3x mb-3 opacity-50"></i>
                        <h5>Backlog Vacío</h5>
                        <p>No se han agregado juegos a la lista.</p>
                    </div>
                    
                    <div v-else class="row g-3">
                        <div v-for="game in games" :key="game.id" class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="card bg-black border-0 text-light h-100 shadow-sm game-card-hover overflow-hidden position-relative rounded">
                                <div class="game-cover-container bg-secondary d-flex align-items-center justify-content-center" style="height: 180px;">
                                    <img v-if="getGameCover(game)" :src="getGameCover(game)" :alt="game.name" class="w-100 h-100 object-fit-cover" />
                                    <i v-else class="fas fa-gamepad fa-3x text-muted opacity-50"></i>
                                </div>
                                <!-- Overlay Gradient -->
                                <div class="position-absolute bottom-0 w-100 p-2" style="background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);">
                                    <h6 class="mb-0 text-truncate fw-bold text-white shadow-text" :title="game.name">{{ game.name }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Collections Tab -->
                <div v-show="activeTab === 'collections'" class="animate-fade-in">
                    <div v-if="collections.length === 0" class="card bg-dark border-0 shadow-sm text-center py-5 text-muted">
                        <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                        <h5>Sin Colecciones</h5>
                        <p>El usuario no ha creado ninguna colección aún.</p>
                    </div>
                    
                    <div v-else class="row g-3">
                        <div v-for="collection in collections" :key="collection.id" class="col-md-6">
                            <div class="card bg-dark border border-secondary text-light h-100 hover-lift shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-layer-group me-2 text-success"></i>{{ collection.name }}</h5>
                                    </div>
                                    <p class="text-muted small mb-0">{{ collection.description || 'Sin descripción' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Groups Tab -->
                <div v-show="activeTab === 'groups'" class="animate-fade-in">
                    <div v-if="groups.length === 0" class="card bg-dark border-0 shadow-sm text-center py-5 text-muted">
                        <i class="fas fa-users-slash fa-3x mb-3 opacity-50"></i>
                        <h5>No pertenece a grupos</h5>
                        <p>El usuario no participa en ninguna sesión activa.</p>
                    </div>
                    
                    <div v-else class="row g-3">
                        <div v-for="group in groups" :key="group.id" class="col-md-6">
                            <div class="card bg-black border border-secondary text-light h-100 hover-lift shadow-sm">
                                <div class="card-body">
                                    <div class="badge bg-secondary mb-2"><i class="fas fa-satellite-dish me-1"></i> Sesión Multijugador</div>
                                    <h5 class="fw-bold mb-2 text-truncate" :title="group.title">{{ group.title }}</h5>
                                    <p class="text-muted small mb-3 text-truncate">{{ group.description || 'Sin descripción' }}</p>
                                    
                                    <div class="d-flex align-items-center text-muted small">
                                        <i class="far fa-calendar-alt me-2 text-info"></i>
                                        {{ formatDate(group.start_time) }}
                                    </div>
                                </div>
                            </div>
                        </div>
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
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important;
}

.game-card-hover {
    transition: transform 0.2s ease;
    cursor: pointer;
}
.game-card-hover:hover {
    transform: scale(1.03);
}

.shadow-text {
    text-shadow: 0 2px 4px rgba(0,0,0,0.8);
}
</style>
