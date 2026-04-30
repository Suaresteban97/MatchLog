<script setup>
import { ref, computed, onMounted, watch } from 'vue';
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

const selectedCollection = ref(null);
const loadingCollection = ref(false);

const openCollection = async (collection) => {
    selectedCollection.value = { ...collection, games: [] };
    loadingCollection.value = true;
    try {
        const res = await get(`/collections/${collection.id}`);
        selectedCollection.value = res.data || res;
    } catch (e) {
        console.error('Error loading collection:', e);
    } finally {
        loadingCollection.value = false;
    }
};

const newPost = ref({
    content: '',
    game_id: '',
    user_device_id: '',
    collection_id: '',
    share_social_profile: localStorage.getItem('share_social_profile') === 'true'
});

watch(() => newPost.value.share_social_profile, (newVal) => {
    localStorage.setItem('share_social_profile', newVal);
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
        // Note: we don't reset share_social_profile to false, it keeps the cached value
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

// ─────────────── LIKES ───────────────
const { post: apiPost } = useApi();

const toggleLike = async (post) => {
    try {
        const res = await apiPost(`/posts/${post.id}/like`);
        post.user_liked = res.liked;
        post.likes_count = res.likes_count;
    } catch (e) {
        console.error('Error toggling like:', e);
    }
};

// ─────────────── COMMENTS MODAL ───────────────
const activePost      = ref(null);
const comments        = ref([]);       // flat list from API
const loadingComments = ref(false);
const newComment      = ref('');
const activeReplyId   = ref(null);
const replyTexts      = ref({});
const submittingComment = ref(false);

// Build a flat sorted tree: root comments in order, with children interleaved
const buildFlatTree = (flat) => {
    const map = {};
    flat.forEach(c => { map[c.id] = { ...c, _children: [] }; });

    const roots = [];
    flat.forEach(c => {
        if (c.parent_id && map[c.parent_id]) {
            map[c.parent_id]._children.push(map[c.id]);
        } else if (!c.parent_id) {
            roots.push(map[c.id]);
        }
    });

    const flatten = (nodes, depth = 0) => {
        const result = [];
        nodes.forEach(node => {
            result.push({ ...node, _depth: depth });
            if (node._children.length) result.push(...flatten(node._children, depth + 1));
        });
        return result;
    };
    return flatten(roots);
};

const flatComments = computed(() => buildFlatTree(comments.value));

const openComments = async (post) => {
    activePost.value = post;
    comments.value   = [];
    activeReplyId.value = null;
    replyTexts.value = {};
    loadingComments.value = true;
    try {
        const res = await get(`/posts/${post.id}/comments`);
        comments.value = res.data || [];
    } catch (e) {
        console.error('Error loading comments:', e);
    } finally {
        loadingComments.value = false;
    }
};

const closeComments = () => {
    activePost.value = null;
    newComment.value = '';
    activeReplyId.value = null;
    replyTexts.value = {};
};

const toggleReplyBox = (commentId) => {
    activeReplyId.value = activeReplyId.value === commentId ? null : commentId;
    if (!replyTexts.value[commentId]) replyTexts.value[commentId] = '';
};

const submitComment = async (parentId = null) => {
    if (!activePost.value) return;
    const body = parentId ? replyTexts.value[parentId] : newComment.value;
    if (!body?.trim()) return;

    submittingComment.value = true;
    try {
        const payload = { body };
        if (parentId) payload.parent_id = parentId;

        const res = await apiPost(`/posts/${activePost.value.id}/comments`, payload);
        if (res.success) {
            // Initialise fields the server would compute for the current user
            res.data.user_liked  = false;
            res.data.likes_count = 0;
            // Push to flat list — buildFlatTree will place it correctly
            comments.value.push(res.data);

            if (parentId) {
                replyTexts.value[parentId] = '';
                activeReplyId.value = null;
            } else {
                newComment.value = '';
            }
            if (activePost.value) activePost.value.comments_count = (activePost.value.comments_count || 0) + 1;
        }
    } catch (e) {
        console.error('Error submitting comment:', e);
    } finally {
        submittingComment.value = false;
    }
};

const toggleCommentLike = async (comment) => {
    try {
        const res = await apiPost(`/post-comments/${comment.id}/like`);
        // Mutate in the flat list so reactivity propagates
        const item = comments.value.find(c => c.id === comment.id);
        if (item) { item.user_liked = res.liked; item.likes_count = res.likes_count; }
    } catch (e) {
        console.error('Error liking comment:', e);
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
                                    <span class="me-2 text-muted small">Colección:</span>
                                    <button @click="openCollection(post.collection)" class="btn btn-sm d-flex align-items-center gap-2 bg-dark border border-secondary text-light px-2 py-1 rounded">
                                        <i class="fas fa-layer-group text-success"></i>
                                        <span class="text-truncate" style="max-width: 180px; font-size: 0.85rem;">{{ post.collection.name }}</span>
                                        <i class="fas fa-arrow-right text-muted small"></i>
                                    </button>
                                </div>

                                <div v-if="post.share_social_profile && post.user.social_profiles && post.user.social_profiles.length > 0" class="mt-3 pt-2 border-top border-dark">
                                    <span class="d-block small mb-1 text-muted">Perfiles Sociales:</span>
                                    <template v-for="sp in post.user.social_profiles" :key="sp.id">
                                        <a v-if="sp.profile_url" :href="sp.profile_url" target="_blank" class="badge bg-dark border border-secondary me-1 text-decoration-none text-light hover-opacity">
                                            <i class="fas fa-gamepad text-primary"></i> 
                                            {{ sp.social_platform ? sp.social_platform.name : 'Plataforma' }}: {{ sp.gamertag }}
                                        </a>
                                        <span v-else class="badge bg-dark border border-secondary me-1">
                                            <i class="fas fa-gamepad text-primary"></i> 
                                            {{ sp.social_platform ? sp.social_platform.name : 'Plataforma' }}: {{ sp.gamertag }}
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-secondary d-flex gap-3 py-3">
                            <!-- Like -->
                            <button @click="toggleLike(post)" class="btn btn-sm btn-link text-decoration-none p-0 d-flex align-items-center gap-1" :class="post.user_liked ? 'text-danger' : 'text-muted'">
                                <i :class="post.user_liked ? 'fas fa-heart' : 'far fa-heart'"></i>
                                <span>{{ post.likes_count || 0 }}</span>
                            </button>
                            <!-- Comment -->
                            <button @click="openComments(post)" class="btn btn-sm btn-link text-muted text-decoration-none p-0 d-flex align-items-center gap-1">
                                <i class="far fa-comment"></i>
                                <span>{{ post.comments_count || 0 }}</span>
                            </button>
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

    <!-- Comments Modal -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="activePost" class="modal-backdrop-custom" @click.self="closeComments">
                <div class="comments-modal">
                    <!-- Header -->
                    <div class="collection-modal-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="collection-icon-wrap" style="background:rgba(15,240,252,0.1);border-color:rgba(15,240,252,0.25)">
                                <i class="fas fa-comments fa-lg text-info"></i>
                            </div>
                            <div>
                                <p class="text-muted small mb-0 text-uppercase">Comentarios</p>
                                <h5 class="mb-0 fw-bold text-truncate" style="max-width:400px">{{ activePost.content.slice(0,60) }}{{ activePost.content.length > 60 ? '...' : '' }}</h5>
                            </div>
                        </div>
                        <button @click="closeComments" class="btn-close btn-close-white"></button>
                    </div>

                    <!-- Root comment input -->
                    <div class="px-4 pt-3 pb-2 border-bottom border-secondary">
                        <div class="d-flex gap-2">
                            <textarea
                                v-model="newComment"
                                @keydown.enter.ctrl="submitComment(null)"
                                class="form-control form-control-sm bg-secondary text-light border-0"
                                placeholder="Escribe un comentario..."
                                rows="2"
                                style="resize:none"
                            ></textarea>
                            <button @click="submitComment(null)" :disabled="submittingComment || !newComment.trim()" class="btn btn-primary btn-sm px-3">
                                <i v-if="submittingComment" class="fas fa-spinner fa-spin"></i>
                                <i v-else class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <p class="text-muted small mt-1 mb-0">Ctrl+Enter para enviar</p>
                    </div>

                    <!-- Comments list -->
                    <div class="comments-list hide-scrollbar">
                        <div v-if="loadingComments" class="text-center py-5">
                            <div class="spinner-border text-info spinner-border-sm" role="status"></div>
                            <p class="text-muted small mt-2">Cargando comentarios...</p>
                        </div>

                        <div v-else-if="comments.length === 0" class="text-center py-5 text-muted">
                            <i class="fas fa-comment-slash fa-2x mb-3 opacity-50"></i>
                            <p>Sé el primero en comentar.</p>
                        </div>

                        <div v-else class="px-3 py-3 d-flex flex-column gap-3">
                            <template v-for="comment in flatComments" :key="comment.id">
                                <!-- Indent based on depth, max 240px -->
                                <div :style="{ marginLeft: Math.min(comment._depth * 32, 240) + 'px' }" class="d-flex gap-2">
                                    <!-- Depth indicator line -->
                                    <div v-if="comment._depth > 0" class="border-start border-secondary" style="width:3px; flex-shrink:0; border-radius:2px"></div>

                                    <div class="flex-grow-1">
                                        <!-- Bubble -->
                                        <div class="bg-secondary rounded p-2 px-3">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <div class="avatar-sm" style="width:26px;height:26px;font-size:0.7rem;flex-shrink:0">{{ comment.user?.name?.charAt(0).toUpperCase() }}</div>
                                                <span class="fw-bold small">{{ comment.user?.name }}</span>
                                                <span v-if="comment._depth > 0" class="text-muted small">
                                                    <i class="fas fa-reply fa-xs"></i>
                                                </span>
                                            </div>
                                            <p class="mb-0 small" style="white-space:pre-wrap; padding-left: 34px;">{{ comment.body }}</p>
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-flex align-items-center gap-3 mt-1 ms-1">
                                            <span class="text-muted" style="font-size:0.72rem">{{ formatDate(comment.created_at) }}</span>
                                            <button @click="toggleCommentLike(comment)" class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-1" :class="comment.user_liked ? 'text-danger' : 'text-muted'" style="font-size:0.72rem">
                                                <i :class="comment.user_liked ? 'fas fa-heart' : 'far fa-heart'"></i>
                                                <span>{{ comment.likes_count || 0 }}</span>
                                            </button>
                                            <button @click="toggleReplyBox(comment.id)" class="btn btn-link p-0 text-muted text-decoration-none" style="font-size:0.72rem">
                                                <i class="fas fa-reply fa-xs me-1"></i>Responder
                                            </button>
                                        </div>

                                        <!-- Inline reply box -->
                                        <div v-if="activeReplyId === comment.id" class="mt-2 d-flex gap-2">
                                            <textarea
                                                v-model="replyTexts[comment.id]"
                                                @keydown.enter.ctrl="submitComment(comment.id)"
                                                class="form-control form-control-sm bg-secondary text-light border-secondary"
                                                :placeholder="'Respondiendo a ' + (comment.user?.name || '...') + '...' "
                                                rows="2"
                                                style="resize:none"
                                            ></textarea>
                                            <div class="d-flex flex-column gap-1">
                                                <button @click="submitComment(comment.id)" :disabled="submittingComment || !replyTexts[comment.id]?.trim()" class="btn btn-primary btn-sm px-2">
                                                    <i v-if="submittingComment" class="fas fa-spinner fa-spin"></i>
                                                    <i v-else class="fas fa-paper-plane"></i>
                                                </button>
                                                <button @click="toggleReplyBox(comment.id)" class="btn btn-sm btn-secondary px-2">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Collection Detail Modal -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="selectedCollection" class="modal-backdrop-custom" @click.self="selectedCollection = null">
                <div class="collection-modal">
                    <!-- Header -->
                    <div class="collection-modal-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="collection-icon-wrap">
                                <i class="fas fa-layer-group fa-lg text-success"></i>
                            </div>
                            <div>
                                <p class="text-muted small mb-0 text-uppercase tracking-wide">Colección</p>
                                <h4 class="mb-0 fw-bold">{{ selectedCollection.name }}</h4>
                            </div>
                        </div>
                        <button @click="selectedCollection = null" class="btn-close btn-close-white" aria-label="Cerrar"></button>
                    </div>

                    <!-- Description -->
                    <p v-if="selectedCollection.description" class="text-muted px-4 pt-3 mb-0">{{ selectedCollection.description }}</p>

                    <!-- Loading -->
                    <div v-if="loadingCollection" class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="text-muted mt-3 small">Cargando juegos de la colección...</p>
                    </div>

                    <!-- Games Grid -->
                    <div v-else class="collection-games-grid px-4 pb-4 pt-3">
                        <div v-if="selectedCollection.games && selectedCollection.games.length === 0" class="text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                            <p>Esta colección no tiene juegos aún.</p>
                        </div>

                        <div v-else class="games-masonry">
                            <a
                                v-for="game in selectedCollection.games"
                                :key="game.id"
                                :href="'/games/' + (game.slug || game.id)"
                                target="_blank"
                                class="collection-game-card text-decoration-none"
                            >
                                <div class="collection-game-cover">
                                    <img v-if="game.cover_image_url" :src="game.cover_image_url" :alt="game.name" class="collection-cover-img">
                                    <div v-else class="collection-cover-placeholder">
                                        <i class="fas fa-gamepad fa-2x text-muted"></i>
                                    </div>
                                    <div class="collection-cover-overlay">
                                        <i class="fas fa-external-link-alt text-white"></i>
                                    </div>
                                </div>
                                <p class="collection-game-name">{{ game.name }}</p>
                            </a>
                        </div>

                        <p class="text-muted small mt-3 text-end">
                            <i class="fas fa-gamepad me-1"></i>
                            {{ selectedCollection.games?.length || 0 }} juego(s)
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
