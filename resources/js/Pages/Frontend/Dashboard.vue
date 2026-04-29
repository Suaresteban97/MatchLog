<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useDashboard } from '../../Composables/useDashboard';
import { useApi } from '../../Composables/useApi';

defineProps({
    module: Number,
});

const { posts, postsMeta, loadingPosts, suggestions, suggestionsMeta, loadingSuggestions, recentGames, recentGamesMeta, loadingRecentGames, fetchPosts, fetchSuggestions, fetchRecentGames, createPost, joinSession, loading } = useDashboard();
const { get } = useApi();

const myGames = ref([]);
const myDevices = ref([]);
const myCollections = ref([]);

const newPost = ref({
    content: '',
    game_id: '',
    user_device_id: '',
    collection_id: '',
    share_social_profile: false
});

const isSubmitting = ref(false);

onMounted(async () => {
    fetchPosts();
    fetchSuggestions();
    fetchRecentGames();

    // Fetch user options for dropdowns
    try {
        const [gamesRes, devicesRes, collectionsRes] = await Promise.all([
            get('/my-games?per_page=100'),
            get('/devices'),
            get('/collections')
        ]);
        myGames.value = gamesRes.data || [];
        myDevices.value = devicesRes.devices || devicesRes.data || [];
        myCollections.value = collectionsRes.data || [];
    } catch (e) {
        console.error("Error fetching options:", e);
    }
});

const submitPost = async () => {
    if (!newPost.value.content.trim()) return;
    
    isSubmitting.value = true;
    
    const payload = {
        content: newPost.value.content,
        share_social_profile: newPost.value.share_social_profile
    };
    if (newPost.value.game_id) payload.game_id = newPost.value.game_id;
    if (newPost.value.user_device_id) payload.user_device_id = newPost.value.user_device_id;
    if (newPost.value.collection_id) payload.collection_id = newPost.value.collection_id;

    const success = await createPost(payload);
    if (success) {
        newPost.value.content = '';
        newPost.value.game_id = '';
        newPost.value.user_device_id = '';
        newPost.value.collection_id = '';
        newPost.value.share_social_profile = false;
    }
    isSubmitting.value = false;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatDateOnly = (dateString) => {
    if (!dateString) return 'N/A';
    const [year, month, day] = dateString.split('-');
    return new Date(year, month - 1, day).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
};

const handleRecentGamesScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    // Cargar más cuando falten 20px para el final
    if (scrollTop + clientHeight >= scrollHeight - 20) {
        if (!loadingRecentGames.value && recentGamesMeta.value.current_page < recentGamesMeta.value.last_page) {
            fetchRecentGames(recentGamesMeta.value.current_page + 1);
        }
    }
};

const handlePostsScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    // Cargar más publicaciones cuando falten 100px para el final
    if (scrollTop + clientHeight >= scrollHeight - 100) {
        if (!loadingPosts.value && postsMeta.value.current_page < postsMeta.value.last_page) {
            fetchPosts(postsMeta.value.current_page + 1);
        }
    }
};

const handleSuggestionsScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollTop + clientHeight >= scrollHeight - 20) {
        if (!loadingSuggestions.value && suggestionsMeta.value.current_page < suggestionsMeta.value.last_page) {
            fetchSuggestions(suggestionsMeta.value.current_page + 1);
        }
    }
};

const requestJoinSession = async (session) => {
    const res = await joinSession(session.id);
    if (res && res.message) {
        alert(res.message);
    }
};
</script>

<template>
    <Head title="Social Dashboard" />

    <AppLayout v-cloak>
        <div class="row pt-4" style="height: calc(100vh - 100px);">
            
            <!-- Left Column: Suggestions -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="card shadow-sm border-0 bg-dark text-light mb-3">
                    <div class="card-header bg-secondary border-0">
                        <h5 class="mb-0"><i class="fas fa-lightbulb text-warning me-2"></i>Sugerencias para ti</h5>
                    </div>
                    <div class="card-body p-0 hide-scrollbar" style="max-height: calc(100vh - 150px); overflow-y: auto;" @scroll="handleSuggestionsScroll">
                        <ul class="list-group list-group-flush">
                            <li v-for="game in suggestions" :key="game.id" class="list-group-item bg-dark text-light border-secondary d-flex align-items-center">
                                <img v-if="game.cover_image_url" :src="game.cover_image_url" alt="cover" class="rounded me-3" style="width: 40px; height: 50px; object-fit: cover;">
                                <div v-else class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 50px;">
                                    <i class="fas fa-gamepad text-muted"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-truncate" style="max-width: 150px;">
                                        <a :href="'/games/' + (game.slug || game.id)" target="_blank" class="text-light text-decoration-none hover-text-primary">{{ game.name }}</a>
                                    </h6>
                                    <small class="text-muted"><i class="fas fa-star text-warning"></i> {{ game.metacritic_score || 'N/A' }}</small>
                                </div>
                            </li>
                            <li v-if="suggestions.length === 0 && !loadingSuggestions && !loading" class="list-group-item bg-dark text-muted text-center py-3 border-0">
                                No hay sugerencias aún.
                            </li>
                            <li v-if="loadingSuggestions" class="list-group-item bg-dark text-center py-3 border-0">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Middle Column: Feed -->
            <div class="col-lg-6 col-md-8 mb-4 pe-2 hide-scrollbar" style="height: 100%; overflow-y: auto;" @scroll="handlePostsScroll">
                <!-- Create Post -->
                <div class="card shadow-sm border-0 bg-dark text-light mb-4">
                    <div class="card-body">
                        <form @submit.prevent="submitPost">
                            <div class="mb-3">
                                <textarea class="form-control bg-secondary text-light border-0" v-model="newPost.content" rows="3" placeholder="¿Qué estás jugando? Comparte tus pensamientos..."></textarea>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <select class="form-select bg-secondary text-light border-0 form-select-sm" v-model="newPost.game_id">
                                        <option value="">Vincular Juego</option>
                                        <option v-for="ug in myGames" :key="ug.id" :value="ug.id">{{ ug.name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select bg-secondary text-light border-0 form-select-sm" v-model="newPost.user_device_id">
                                        <option value="">Vincular Dispositivo</option>
                                        <option v-for="dev in myDevices" :key="dev.id" :value="dev.id">{{ dev.custom_name || dev.device?.name || 'Mi Dispositivo' }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select bg-secondary text-light border-0 form-select-sm" v-model="newPost.collection_id">
                                        <option value="">Vincular Colección</option>
                                        <option v-for="col in myCollections" :key="col.id" :value="col.id">{{ col.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="shareProfile" v-model="newPost.share_social_profile">
                                    <label class="form-check-label small" for="shareProfile">
                                        Compartir Perfil Social
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary px-4 rounded-pill" :disabled="isSubmitting || !newPost.content.trim()">
                                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Publicar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Posts List -->
                <div v-if="loading && posts.length === 0" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
                
                <div v-else class="d-flex flex-column gap-4">
                    <div v-for="post in posts" :key="post.id" class="card shadow-sm border-0 bg-dark text-light">
                        <div class="card-body">
                            <!-- Post Header -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-weight: bold; font-size: 1.2rem;">
                                    {{ post.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ post.user.name }}</h6>
                                    <small class="text-muted">{{ formatDate(post.created_at) }}</small>
                                </div>
                            </div>
                            
                            <!-- Post Content -->
                            <p class="card-text fs-5 mb-3" style="white-space: pre-wrap;">{{ post.content }}</p>
                            
                            <!-- Linked Attachments -->
                            <div v-if="post.game || post.user_device || post.collection || post.share_social_profile" class="bg-secondary rounded p-3 mb-2">
                                <h6 class="text-uppercase text-muted small mb-2 fw-bold">Adjuntos</h6>
                                
                                <div v-if="post.game" class="d-flex align-items-center mb-2">
                                    <span class="me-2 text-muted small">Juego:</span>
                                    <a :href="'/games/' + (post.game.slug || post.game.id)" target="_blank" class="text-decoration-none d-flex align-items-center bg-dark p-1 rounded border border-secondary" style="max-width: 250px;">
                                        <img v-if="post.game.cover_image_url" :src="post.game.cover_image_url" alt="cover" class="rounded me-2" style="width: 25px; height: 35px; object-fit: cover;">
                                        <div v-else class="bg-secondary rounded me-2 d-flex align-items-center justify-content-center" style="width: 25px; height: 35px;">
                                            <i class="fas fa-gamepad text-muted small"></i>
                                        </div>
                                        <span class="text-light text-truncate" style="font-size: 0.85rem;">{{ post.game.name }}</span>
                                    </a>
                                </div>
                                
                                <div v-if="post.user_device" class="d-flex align-items-center mb-2">
                                    <span class="me-2 text-muted small">Dispositivo:</span>
                                    <span class="badge bg-secondary border border-secondary text-light">
                                        <i class="fas fa-desktop me-1"></i> {{ post.user_device.custom_name || post.user_device.device?.name || 'Mi Dispositivo' }}
                                    </span>
                                </div>
                                
                                <!-- Sesión Multijugador Vinculada -->
                                <div v-if="post.game_session" class="mt-3 p-3 bg-black rounded border border-secondary position-relative overflow-hidden session-card">
                                    <div class="position-absolute top-0 end-0 p-2 opacity-25" style="transform: translate(20%, -20%);">
                                        <i class="fas fa-satellite-dish fa-5x text-primary"></i>
                                    </div>
                                    <div class="position-relative z-1">
                                        <h6 class="text-uppercase text-primary small mb-1 fw-bold">
                                            <i class="fas fa-gamepad me-1"></i> Sesión Multijugador
                                        </h6>
                                        <h5 class="mb-2 text-white">{{ post.game_session.title }}</h5>
                                        <p class="text-muted small mb-3">{{ post.game_session.description }}</p>
                                        
                                        <div class="d-flex flex-wrap gap-3 mb-3 small align-items-center">
                                            <div class="bg-dark px-2 py-1 rounded border border-secondary">
                                                <i class="far fa-calendar-alt text-info me-1"></i>
                                                {{ formatDate(post.game_session.start_time) }}
                                            </div>
                                            <div class="bg-dark px-2 py-1 rounded border border-secondary">
                                                <i class="fas fa-users text-success me-1"></i>
                                                {{ post.game_session.participants?.length || 0 }} / {{ post.game_session.max_participants }}
                                            </div>
                                            <div v-if="post.game_session.host" class="bg-dark px-2 py-1 rounded border border-secondary">
                                                <i class="fas fa-crown text-warning me-1"></i>
                                                {{ post.game_session.host.name }}
                                            </div>
                                        </div>
                                        
                                        <button @click="requestJoinSession(post.game_session)" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                            <i class="fas fa-plus-circle me-1"></i> Solicitar Unirse
                                        </button>
                                    </div>
                                </div>

                                <div v-if="post.user_device && post.user_device.characteristics && post.user_device.characteristics.length > 0" class="ms-4 mt-1 small text-muted">
                                    <span v-for="char in post.user_device.characteristics.slice(0, 3)" :key="char.id" class="me-2">
                                        &bull; {{ char.value }}
                                    </span>
                                </div>
                                
                                <div v-if="post.collection" class="d-flex align-items-center mb-2">
                                    <i class="fas fa-layer-group text-success me-2"></i>
                                    <span>Colección: <strong>{{ post.collection.name }}</strong></span>
                                </div>

                                <div v-if="post.share_social_profile && post.user.social_profiles && post.user.social_profiles.length > 0" class="mt-3 pt-2 border-top border-dark">
                                    <span class="d-block small mb-1 text-muted">Perfiles Sociales:</span>
                                    <span v-for="sp in post.user.social_profiles" :key="sp.id" class="badge bg-dark border border-secondary me-1">
                                        <i class="fas fa-gamepad text-primary"></i> 
                                        {{ sp.social_platform ? sp.social_platform.name : 'Plataforma' }}: {{ sp.gamertag }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-secondary d-flex gap-3 py-3">
                            <button class="btn btn-sm btn-link text-light text-decoration-none p-0"><i class="far fa-heart me-1"></i> Me gusta</button>
                            <button class="btn btn-sm btn-link text-light text-decoration-none p-0"><i class="far fa-comment me-1"></i> Comentar</button>
                        </div>
                    </div>
                    
                    <div v-if="loadingPosts" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                            <span class="visually-hidden">Cargando publicaciones...</span>
                        </div>
                    </div>

                    <div v-if="posts.length === 0 && !loadingPosts && !loading" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <h5>No hay publicaciones</h5>
                        <p>Sé el primero en compartir algo con la comunidad.</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Recent Games -->
            <div class="col-lg-3 col-md-12 mb-4">
                <div class="card shadow-sm border-0 bg-dark text-light mb-3">
                    <div class="card-header bg-secondary border-0">
                        <h5 class="mb-0"><i class="fas fa-fire text-danger me-2"></i>Lanzamientos Recientes</h5>
                    </div>
                    <div class="card-body p-0 hide-scrollbar" style="max-height: calc(100vh - 150px); overflow-y: auto;" @scroll="handleRecentGamesScroll">
                        <ul class="list-group list-group-flush">
                            <li v-for="game in recentGames" :key="game.id" class="list-group-item bg-dark text-light border-secondary d-flex align-items-center py-2">
                                <img v-if="game.cover_image_url" :src="game.cover_image_url" alt="cover" class="rounded me-2" style="width: 35px; height: 45px; object-fit: cover;">
                                <div v-else class="bg-secondary rounded me-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 45px;">
                                    <i class="fas fa-gamepad text-muted small"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-truncate" style="max-width: 160px; font-size: 0.9rem;">
                                        <a :href="'/games/' + (game.slug || game.id)" target="_blank" class="text-light text-decoration-none hover-text-primary">{{ game.name }}</a>
                                    </h6>
                                    <small class="text-muted" style="font-size: 0.8rem;"><i class="far fa-calendar-alt me-1"></i> {{ formatDateOnly(game.release_date) }}</small>
                                </div>
                            </li>
                            <li v-if="recentGames.length === 0 && !loadingRecentGames && !loading" class="list-group-item bg-dark text-muted text-center py-3 border-0">
                                No hay juegos recientes.
                            </li>
                            <li v-if="loadingRecentGames" class="list-group-item bg-dark text-center py-3 border-0">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
