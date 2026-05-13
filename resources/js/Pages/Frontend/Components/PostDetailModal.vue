<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { usePostModal } from '../../../Composables/usePostModal';
import { useApi } from '../../../Composables/useApi';

const { state, closePostModal } = usePostModal();
const { get, post: apiPost } = useApi();

const post = ref(null);
const loadingPost = ref(false);

const comments = ref([]);
const loadingComments = ref(false);
const newComment = ref('');
const activeReplyId = ref(null);
const replyTexts = ref({});
const submittingComment = ref(false);

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// Build flat tree for comments
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

const fetchPostDetail = async (id) => {
    loadingPost.value = true;
    try {
        const res = await get(`/posts/${id}`);
        post.value = res.data || res;
    } catch (e) {
        console.error('Error fetching post:', e);
    } finally {
        loadingPost.value = false;
    }
};

const fetchComments = async (id) => {
    loadingComments.value = true;
    comments.value = [];
    try {
        const res = await get(`/posts/${id}/comments`);
        comments.value = res.data || [];
    } catch (e) {
        console.error('Error loading comments:', e);
    } finally {
        loadingComments.value = false;
    }
};

// Watch for modal open
watch(() => state.value.isOpen, async (isOpen) => {
    if (isOpen) {
        if (state.value.post) {
            post.value = state.value.post;
            fetchComments(post.value.id);
        } else if (state.value.postId) {
            await fetchPostDetail(state.value.postId);
            if (post.value) {
                fetchComments(post.value.id);
            }
        }
    } else {
        post.value = null;
        comments.value = [];
        newComment.value = '';
        activeReplyId.value = null;
        replyTexts.value = {};
    }
});

const toggleLike = async () => {
    if (!post.value) return;
    try {
        const res = await apiPost(`/posts/${post.value.id}/like`);
        post.value.user_liked = res.liked;
        post.value.likes_count = res.likes_count;
    } catch (e) {
        console.error('Error toggling like:', e);
    }
};

const toggleReplyBox = (commentId) => {
    activeReplyId.value = activeReplyId.value === commentId ? null : commentId;
    if (!replyTexts.value[commentId]) replyTexts.value[commentId] = '';
};

const submitComment = async (parentId = null) => {
    if (!post.value) return;
    const body = parentId ? replyTexts.value[parentId] : newComment.value;
    if (!body?.trim()) return;

    submittingComment.value = true;
    try {
        const payload = { body };
        if (parentId) payload.parent_id = parentId;

        const res = await apiPost(`/posts/${post.value.id}/comments`, payload);
        if (res.success) {
            res.data.user_liked = false;
            res.data.likes_count = 0;
            comments.value.push(res.data);

            if (parentId) {
                replyTexts.value[parentId] = '';
                activeReplyId.value = null;
            } else {
                newComment.value = '';
            }
            if (post.value) post.value.comments_count = (post.value.comments_count || 0) + 1;
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
        const item = comments.value.find(c => c.id === comment.id);
        if (item) { item.user_liked = res.liked; item.likes_count = res.likes_count; }
    } catch (e) {
        console.error('Error liking comment:', e);
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="state.isOpen" class="modal-backdrop-custom d-flex justify-content-center align-items-center" @click.self="closePostModal">
                <div class="comments-modal rounded shadow-lg d-flex flex-column" style="max-height: 90vh; width: 100%; max-width: 700px; background-color: var(--bs-dark); border: 1px solid var(--bs-secondary); overflow: hidden;">
                    
                    <!-- Header -->
                    <div class="collection-modal-header d-flex justify-content-between align-items-center p-3 border-bottom border-secondary bg-dark" style="z-index: 10;">
                        <h5 class="mb-0 fw-bold text-light"><i class="fas fa-file-alt me-2 text-primary"></i> Detalle de la Publicación</h5>
                        <button @click="closePostModal" class="btn-close btn-close-white"></button>
                    </div>

                    <!-- Scrollable Content -->
                    <div class="modal-body p-0 hide-scrollbar" style="overflow-y: auto;">
                        
                        <!-- Loading Post -->
                        <div v-if="loadingPost" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando publicación...</span>
                            </div>
                        </div>

                        <!-- Post Detail -->
                        <div v-else-if="post" class="p-4 border-bottom border-secondary bg-dark text-light">
                            <!-- Post Header -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; font-weight: bold; font-size: 1.2rem;">
                                    {{ post.user?.name?.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ post.user?.name }}</h6>
                                    <small class="text-muted">{{ formatDate(post.created_at) }}</small>
                                </div>
                            </div>
                            
                            <!-- Post Content -->
                            <p class="fs-5 mb-3" style="white-space: pre-wrap;">{{ post.content }}</p>
                            
                            <!-- Linked Attachments -->
                            <div v-if="post.game || post.user_device || post.collection || post.game_session || post.share_social_profile" class="bg-secondary rounded p-3 mb-3">
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
                                    </div>
                                </div>

                                <div v-if="post.collection" class="d-flex align-items-center mb-2">
                                    <span class="me-2 text-muted small">Colección:</span>
                                    <div class="btn btn-sm d-flex align-items-center gap-2 bg-dark border border-secondary text-light px-2 py-1 rounded pe-none">
                                        <i class="fas fa-layer-group text-success"></i>
                                        <span class="text-truncate" style="max-width: 180px; font-size: 0.85rem;">{{ post.collection.name }}</span>
                                    </div>
                                </div>

                                <div v-if="post.share_social_profile && post.user?.social_profiles?.length > 0" class="mt-3 pt-2 border-top border-dark">
                                    <span class="d-block small mb-1 text-muted">Perfiles Sociales:</span>
                                    <template v-for="sp in post.user.social_profiles" :key="sp.id">
                                        <span class="badge bg-dark border border-secondary me-1">
                                            <i class="fas fa-gamepad text-primary"></i> 
                                            {{ sp.social_platform ? sp.social_platform.name : 'Plataforma' }}: {{ sp.gamertag }}
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Post Actions -->
                            <div class="d-flex gap-4 pt-2">
                                <button @click="toggleLike" class="btn btn-sm btn-link text-decoration-none p-0 d-flex align-items-center gap-1" :class="post.user_liked ? 'text-danger' : 'text-muted'">
                                    <i :class="post.user_liked ? 'fas fa-heart' : 'far fa-heart'"></i>
                                    <span>{{ post.likes_count || 0 }}</span>
                                </button>
                                <div class="text-muted d-flex align-items-center gap-1" style="font-size: 0.875rem;">
                                    <i class="far fa-comment"></i>
                                    <span>{{ post.comments_count || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div v-else class="text-center py-5 text-muted">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3 text-warning"></i>
                            <p>No se pudo cargar la publicación.</p>
                        </div>

                        <!-- Comments Section -->
                        <div v-if="post" class="comments-section bg-dark text-light">
                            <!-- Input -->
                            <div class="p-4 border-bottom border-secondary bg-dark position-sticky" style="top: 0; z-index: 5;">
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

                            <!-- Comments List -->
                            <div class="p-4">
                                <div v-if="loadingComments" class="text-center py-4">
                                    <div class="spinner-border text-info spinner-border-sm" role="status"></div>
                                    <p class="text-muted small mt-2">Cargando comentarios...</p>
                                </div>

                                <div v-else-if="comments.length === 0" class="text-center py-5 text-muted">
                                    <i class="fas fa-comment-slash fa-2x mb-3 opacity-50"></i>
                                    <p>Sé el primero en comentar.</p>
                                </div>

                                <div v-else class="d-flex flex-column gap-3">
                                    <template v-for="comment in flatComments" :key="comment.id">
                                        <div :style="{ marginLeft: Math.min(comment._depth * 32, 240) + 'px' }" class="d-flex gap-2">
                                            <div v-if="comment._depth > 0" class="border-start border-secondary" style="width:3px; flex-shrink:0; border-radius:2px"></div>

                                            <div class="flex-grow-1">
                                                <div class="bg-secondary rounded p-2 px-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <div class="bg-dark text-light rounded-circle d-flex align-items-center justify-content-center" style="width:26px;height:26px;font-size:0.7rem;flex-shrink:0; font-weight:bold;">
                                                            {{ comment.user?.name?.charAt(0).toUpperCase() }}
                                                        </div>
                                                        <span class="fw-bold small">{{ comment.user?.name }}</span>
                                                        <span v-if="comment._depth > 0" class="text-muted small">
                                                            <i class="fas fa-reply fa-xs"></i>
                                                        </span>
                                                    </div>
                                                    <p class="mb-0 small text-light" style="white-space:pre-wrap; padding-left: 34px;">{{ comment.body }}</p>
                                                </div>

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
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.comments-modal {
    display: flex;
    flex-direction: column;
}
.modal-backdrop-custom {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    backdrop-filter: blur(2px);
}
</style>
