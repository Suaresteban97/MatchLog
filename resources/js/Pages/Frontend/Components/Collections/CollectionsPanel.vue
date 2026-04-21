<script setup>
import { ref, onMounted } from 'vue';
import { useCollections } from '../../../../Composables/useCollections';
import { useGames } from '../../../../Composables/useGames';

const {
    collections,
    currentCollection,
    loading,
    error,
    fieldErrors,
    loadCollections,
    loadCollectionDetails,
    createCollection,
    updateCollection,
    deleteCollection,
    addGameToCollection,
    removeGameFromCollection
} = useCollections();

// We need user's games to populate the "add to collection" selector
const { myGames, loadMyGames } = useGames();

// Local UI state
const viewMode = ref('list'); // 'list' | 'detail'
const showModal = ref(false);
const editingId = ref(null);

const form = ref({
    name: '',
    description: '',
    cover_image_url: '',
    is_public: false
});

const gameToAdd = ref('');

onMounted(() => {
    loadCollections();
    loadMyGames();
});

const openCreateModal = () => {
    editingId.value = null;
    form.value = { name: '', description: '', cover_image_url: '', is_public: false };
    fieldErrors.value = {};
    showModal.value = true;
};

const openEditModal = (col) => {
    editingId.value = col.id;
    form.value = { 
        name: col.name, 
        description: col.description || '', 
        cover_image_url: col.cover_image_url || '', 
        is_public: col.is_public 
    };
    fieldErrors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = async () => {
    try {
        if (editingId.value) {
            await updateCollection(editingId.value, form.value);
        } else {
            await createCollection(form.value);
        }
        closeModal();
    } catch {
        // Errors are handled by useCollections and mapped to fieldErrors / error
    }
};

const handleDelete = async (id) => {
    if (confirm('¿Estás seguro de eliminar esta colección?')) {
        await deleteCollection(id);
        if (currentCollection.value && currentCollection.value.id === id) {
            viewMode.value = 'list';
        }
    }
};

const viewCollection = async (id) => {
    await loadCollectionDetails(id);
    viewMode.value = 'detail';
};

const handleAddGame = async () => {
    if (!gameToAdd.value) return;
    try {
        await addGameToCollection(currentCollection.value.id, gameToAdd.value);
        await loadCollectionDetails(currentCollection.value.id);
        gameToAdd.value = ''; // clear selection
        alert('Juego añadido correctamente');
    } catch (e) {
        alert(error.value || 'Ocurrió un error.');
    }
};

const handleRemoveGame = async (gameId) => {
    if (!confirm('¿Quitar este juego de la colección?')) return;
    
    try {
        await removeGameFromCollection(currentCollection.value.id, gameId);
    } catch (e) {
        alert(error.value || 'Ocurrió un error.');
    }
};
</script>

<template>
    <div class="collections-panel">
        <div v-if="viewMode === 'list'">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-white mb-0"><i class="fas fa-compact-disc me-2 text-primary"></i>Tus Colecciones</h5>
                <button @click="openCreateModal" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> Nueva Colección
                </button>
            </div>

            <div v-if="loading" class="text-center py-5">
                <span class="spinner-border text-primary"></span>
            </div>

            <div v-else-if="collections.length === 0" class="text-center text-muted py-5">
                <i class="fas fa-compact-disc fa-3x mb-3"></i>
                <p>Aún no tienes colecciones de juegos.</p>
                <button @click="openCreateModal" class="btn btn-outline-primary mt-2">Crear mi primera colección</button>
            </div>

            <div v-else class="row g-4">
                <div v-for="col in collections" :key="col.id" class="col-md-6 col-lg-4">
                    <div class="card bg-dark border-secondary h-100 collection-card shadow-sm text-white" @click="viewCollection(col.id)">
                        <!-- Cover Image Fallback -->
                        <div class="card-img-top cover-placeholder d-flex align-items-center justify-content-center border-bottom border-secondary"
                             :style="col.cover_image_url ? `background-image: url(${col.cover_image_url}); background-size: cover; background-position: center;` : ''">
                             <h1 v-if="!col.cover_image_url" class="text-muted"><i class="fas fa-images"></i></h1>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title fw-bold mb-1">{{ col.name }}</h6>
                            <p class="text-muted small text-truncate">{{ col.description || 'Sin descripción' }}</p>
                            <span class="badge bg-secondary">{{ col.games_count }} juegos</span>
                            <span v-if="col.is_public" class="badge bg-info ms-2 text-dark"><i class="fas fa-globe me-1"></i>Público</span>
                        </div>
                        <div class="card-footer bg-transparent border-0 d-flex justify-content-between pb-3">
                            <button @click.stop="openEditModal(col)" class="btn btn-sm btn-outline-info rounded-circle"><i class="fas fa-edit"></i></button>
                            <button @click.stop="handleDelete(col.id)" class="btn btn-sm btn-outline-danger rounded-circle"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail View -->
        <div v-else-if="viewMode === 'detail' && currentCollection">
            <button @click="viewMode = 'list'" class="btn btn-sm btn-outline-secondary mb-4">
                <i class="fas fa-arrow-left me-2"></i>Volver a mis colecciones
            </button>

            <div class="card bg-dark border-secondary text-white mb-4 shadow">
                <div class="row g-0">
                    <div class="col-md-3 cover-wrapper">
                         <div class="detail-cover d-flex align-items-center justify-content-center"
                             :style="currentCollection.cover_image_url ? `background-image: url(${currentCollection.cover_image_url}); background-size: cover; background-position: center;` : ''">
                             <i v-if="!currentCollection.cover_image_url" class="fas fa-images fa-4x text-muted"></i>
                        </div>
                    </div>
                    <div class="col-md-9 d-flex flex-column justify-content-center p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <h3 class="fw-bold mb-2">{{ currentCollection.name }}</h3>
                            <span v-if="currentCollection.is_public" class="badge bg-info text-dark"><i class="fas fa-globe me-1"></i>Público</span>
                        </div>
                        <p class="text-muted mb-0">{{ currentCollection.description || 'Sin descripción' }}</p>
                    </div>
                </div>
            </div>

            <!-- Games Management -->
            <div class="card bg-black border-secondary text-white shadow-sm">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-gamepad me-2 text-success"></i>Juegos en esta colección</h6>
                    
                    <div class="d-flex gap-2">
                        <select v-model="gameToAdd" class="form-select form-select-sm bg-dark text-white border-secondary" style="max-width: 250px;">
                            <option value="">Seleccionar juego de mi catálogo...</option>
                            <option v-for="g in myGames" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                        <button @click="handleAddGame" class="btn btn-sm btn-success" :disabled="!gameToAdd">
                            <i class="fas fa-plus"></i> Añadir
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div v-if="currentCollection.games && currentCollection.games.length === 0" class="text-center text-muted py-4 small">
                        Aún no has añadido juegos a esta colección. ¡Selecciona uno arriba!
                    </div>
                    
                    <ul class="list-group list-group-flush" v-else>
                        <li v-for="g in currentCollection.games" :key="g.id" class="list-group-item bg-black text-white border-secondary d-flex justify-content-between align-items-center py-3">
                            <div class="d-flex align-items-center gap-3">
                                <img v-if="g.cover_image_url" :src="g.cover_image_url" alt="Cover" class="rounded shadow-sm" style="width: 48px; height: 64px; object-fit: cover;">
                                <div v-else class="rounded bg-secondary d-flex align-items-center justify-content-center text-dark" style="width: 48px; height: 64px;">
                                    <i class="fas fa-image"></i>
                                </div>
                                <span class="fw-bold">{{ g.name }}</span>
                            </div>
                            <button @click="handleRemoveGame(g.id)" class="btn btn-sm btn-outline-danger" title="Quitar de la colección">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Collection Modal -->
        <div v-if="showModal" class="modal-overlay d-flex align-items-center justify-content-center position-fixed w-100 h-100 top-0 start-0 z-3" style="background: rgba(0,0,0,0.7)">
            <div class="modal-container card bg-dark border-secondary shadow-lg" style="max-width: 500px; width: 95%;">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-compact-disc me-2 text-primary"></i>{{ editingId ? 'Editar Colección' : 'Nueva Colección' }}
                    </h5>
                    <button @click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="card-body">
                    <div v-if="error" class="alert alert-danger small mb-3">{{ error }}</div>

                    <form @submit.prevent="submitForm">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Nombre de la Colección *</label>
                            <input type="text" v-model="form.name" required maxlength="255" class="form-control bg-black border-secondary text-white" :class="{ 'is-invalid': fieldErrors.name }">
                            <div v-if="fieldErrors.name" class="invalid-feedback">{{ fieldErrors.name[0] }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Descripción</label>
                            <textarea v-model="form.description" rows="3" class="form-control bg-black border-secondary text-white" :class="{ 'is-invalid': fieldErrors.description }"></textarea>
                            <div v-if="fieldErrors.description" class="invalid-feedback">{{ fieldErrors.description[0] }}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">URL Imagen Portada (Opcional)</label>
                            <input type="url" v-model="form.cover_image_url" class="form-control bg-black border-secondary text-white" :class="{ 'is-invalid': fieldErrors.cover_image_url }">
                            <div v-if="fieldErrors.cover_image_url" class="invalid-feedback">{{ fieldErrors.cover_image_url[0] }}</div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" id="isPublicSwitch" v-model="form.is_public">
                            <label class="form-check-label text-white small" for="isPublicSwitch">Hacer pública <span class="text-muted">(Otros en la comunidad podrán verla)</span></label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cover-placeholder {
    height: 150px;
    background-color: #1a1a1a;
}
.collection-card {
    cursor: pointer;
    transition: transform 0.2s;
}
.collection-card:hover {
    transform: translateY(-5px);
    border-color: var(--bs-primary) !important;
}
.cover-wrapper {
    background-color: #111;
}
.detail-cover {
    height: 100%;
    min-height: 200px;
}
.z-3 {
    z-index: 1055 !important;
}
</style>
