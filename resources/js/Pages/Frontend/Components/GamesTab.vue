<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useGames } from '../../../Composables/useGames';

const {
    myGames, catalog, metadata, loading, error,
    loadMyGames, loadMetadata, searchGames, toggleGame, updateUserGame
} = useGames();

// Local state
const searchQuery = ref('');
const showEditModal = ref(false);
const editingGame = ref(null);
const editForm = ref({
    game_status_id: '',
    game_platform_id: '',
    hours_played: '',
    rating: '',
    notes: ''
});

const isSearching = ref(false);

// Filter myGames to exclude from search results
const myGameIds = computed(() => {
    return Array.isArray(myGames.value) ? myGames.value.map(g => g.id) : [];
});

// Debounced search
let searchTimeout = null;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    if (newVal.length >= 3) {
        isSearching.value = true;
        searchTimeout = setTimeout(async () => {
            await searchGames(newVal);
            isSearching.value = false;
        }, 500);
    } else {
        catalog.value = [];
    }
});

const openEditModal = (game) => {
    editingGame.value = game;
    // GameResource might have user_game data nested or pivot data depending on implementation
    // Based on GameDetailResource/GameResource work:
    const userGame = game.user_game || {};

    editForm.value = {
        game_status_id: userGame.status?.id || '',
        game_platform_id: userGame.platform?.id || '',
        hours_played: userGame.hours_played || '',
        rating: userGame.rating || '',
        notes: userGame.notes || ''
    };
    showEditModal.value = true;
};

const handleUpdateUserGame = async () => {
    try {
        await updateUserGame(editingGame.value.id, editForm.value);
        showEditModal.value = false;
        editingGame.value = null;
    } catch (err) {
        // Error is handled in composable
    }
};

const handleToggleGame = async (gameId) => {
    const isAdding = !myGameIds.value.includes(gameId);

    if (!isAdding) {
        if (!confirm('¿Estás seguro de quitar este juego de tu biblioteca? Se perderá tu progreso y calificación.')) return;
    }

    await toggleGame(gameId);

    if (isAdding) {
        searchQuery.value = '';
        catalog.value = [];
    }
};

onMounted(async () => {
    await Promise.all([loadMyGames(), loadMetadata()]);
});
</script>

<template>
    <div class="card bg-dark text-white border-secondary mb-4">
        <div class="card-header border-secondary d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-gamepad me-2 text-primary"></i>Buscar Juegos</h5>
        </div>
        <div class="card-body">
            <div class="input-group mb-3">
                <span class="input-group-text bg-transparent border-secondary text-primary">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" v-model="searchQuery" class="form-control bg-black border-secondary text-white"
                    placeholder="Busca un juego para añadirlo (ej. Zelda, Halo...)">
            </div>

            <div v-if="isSearching" class="text-center py-2">
                <i class="fas fa-spinner fa-spin me-2"></i>Buscando...
            </div>

            <!-- Search Results -->
            <div v-if="Array.isArray(catalog) && catalog.length > 0" class="row g-3">
                <div v-for="game in catalog" :key="game.id" class="col-md-6 col-lg-4">
                    <div class="card bg-black border-secondary h-100 game-card">
                        <div class="row g-0 h-100">
                            <div class="col-4">
                                <img :src="game.cover_image_url || '/images/game-placeholder.jpg'"
                                    class="img-fluid rounded-start h-100 object-fit-cover" :alt="game.name">
                            </div>
                            <div class="col-8">
                                <div class="card-body p-2 d-flex flex-column h-100">
                                    <h6 class="card-title text-truncate mb-1" :title="game.name">{{ game.name }}</h6>
                                    <div class="mt-auto">
                                        <button @click="handleToggleGame(game.id)" class="btn btn-sm w-100"
                                            :class="myGameIds.includes(game.id) ? 'btn-outline-danger' : 'btn-primary'"
                                            style="font-size: 0.75rem;">
                                            <i class="fas"
                                                :class="myGameIds.includes(game.id) ? 'fa-minus-circle' : 'fa-plus-circle'"></i>
                                            {{ myGameIds.includes(game.id) ? 'Quitar' : 'Añadir' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p v-else-if="searchQuery.length >= 3 && !isSearching" class="text-muted text-center small">No se
                encontraron juegos con ese nombre.</p>
        </div>
    </div>

    <!-- My Games List -->
    <div class="card bg-dark text-white border-secondary">
        <div class="card-header border-secondary">
            <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Mi Biblioteca</h5>
        </div>
        <div class="card-body">
            <div v-if="loading && myGames.length === 0" class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2 text-muted">Cargando biblioteca...</p>
            </div>

            <div v-else-if="Array.isArray(myGames) && myGames.length > 0" class="table-responsive">
                <table class="table table-dark table-hover align-middle">
                    <thead class="text-secondary border-secondary">
                        <tr>
                            <th>Juego</th>
                            <th>Estado</th>
                            <th>Rating</th>
                            <th>Horas</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="border-secondary">
                        <tr v-for="game in myGames" :key="game.id">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img :src="game.cover_image_url || '/images/game-placeholder.jpg'"
                                        class="rounded me-3" style="width: 40px; height: 50px; object-fit: cover;">
                                    <span class="fw-bold">{{ game.name }}</span>
                                </div>
                            </td>
                            <td>
                                <span v-if="game.user_game?.status" class="badge rounded-pill" :class="{
                                    'bg-success': game.user_game.status.slug === 'completed',
                                    'bg-primary': game.user_game.status.slug === 'playing',
                                    'bg-warning text-dark': game.user_game.status.slug === 'on-hold',
                                    'bg-danger': game.user_game.status.slug === 'dropped',
                                    'bg-secondary': game.user_game.status.slug === 'backlog' || game.user_game.status.slug === 'wishlist'
                                }">
                                    {{ game.user_game.status.name }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>
                            <td>
                                <span v-if="game.user_game?.rating" class="text-warning">
                                    <i class="fas fa-star me-1"></i>{{ game.user_game.rating }}/100
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>
                            <td>
                                <span v-if="game.user_game?.hours_played">{{ game.user_game.hours_played }}h</span>
                                <span v-else class="text-muted small">-</span>
                            </td>
                            <td class="text-end">
                                <button @click="openEditModal(game)" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="handleToggleGame(game.id)" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-5">
                <i class="fas fa-ghost fa-3x text-muted mb-3"></i>
                <p class="text-muted">No tienes juegos vinculados todavía. ¡Busca uno arriba!</p>
            </div>
        </div>
    </div>

    <!-- Edit Modal (Simple overlay for now) -->
    <div v-if="showEditModal" class="modal-overlay d-flex align-items-center justify-content-center">
        <div class="modal-container card bg-dark border-secondary shadow-lg" style="max-width: 500px; width: 90%;">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white">Editar Progreso: {{ editingGame?.name }}</h5>
                <button @click="showEditModal = false" class="btn-close btn-close-white"></button>
            </div>
            <div class="card-body text-white">
                <div v-if="error" class="alert alert-danger mb-3 small">{{ error }}</div>

                <form @submit.prevent="handleUpdateUserGame">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estado</label>
                            <select v-model="editForm.game_status_id"
                                class="form-select bg-black border-secondary text-white">
                                <option value="">Selecciona estado...</option>
                                <option v-for="status in metadata.statuses" :key="status.id" :value="status.id">{{
                                    status.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Plataforma</label>
                            <select v-model="editForm.game_platform_id"
                                class="form-select bg-black border-secondary text-white">
                                <option value="">Selecciona plataforma...</option>
                                <option v-for="platform in metadata.platforms" :key="platform.id" :value="platform.id">
                                    {{ platform.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Horas Jugadas</label>
                            <input type="number" v-model="editForm.hours_played"
                                class="form-control bg-black border-secondary text-white" min="0" step="0.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Calificación (1-100)</label>
                            <input type="number" v-model="editForm.rating"
                                class="form-control bg-black border-secondary text-white" min="1" max="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Notas</label>
                            <textarea v-model="editForm.notes" class="form-control bg-black border-secondary text-white"
                                rows="3" placeholder="¿Qué te parece el juego?"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-grid">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
