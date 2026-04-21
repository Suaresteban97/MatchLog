import { ref } from 'vue';
import { useApi } from './useApi';

export function useCollections() {
    const { get, post, put, del, error, loading } = useApi();
    
    const collections = ref([]);
    const currentCollection = ref(null);
    const fieldErrors = ref({});

    /**
     * Parse 422 validation errors if any
     */
    const parseErrors = (err) => {
        if (err.status === 422 && err.errors) {
            fieldErrors.value = err.errors;
        } else {
            fieldErrors.value = {};
            error.value = err.message || err.error || 'Error de red';
        }
    };

    /**
     * Load user's collections
     */
    const loadCollections = async () => {
        try {
            const response = await get('/collections');
            collections.value = response.data || [];
            return response.data;
        } catch (err) {
            console.error('Error loading collections:', err);
        }
    };

    /**
     * Load a specific collection by ID
     */
    const loadCollectionDetails = async (id) => {
        currentCollection.value = null; // Clear old
        try {
            const response = await get(`/collections/${id}`);
            currentCollection.value = response.data;
            return response.data;
        } catch (err) {
            console.error('Error loading collection details:', err);
        }
    };

    /**
     * Create a new collection
     */
    const createCollection = async (formData) => {
        fieldErrors.value = {};
        error.value = null;
        try {
            const response = await post('/collections', formData);
            if (response.success) {
                // Prepend to list
                collections.value.unshift(response.data);
                return response.data;
            }
        } catch (err) {
            parseErrors(err);
            throw err;
        }
    };

    /**
     * Update an existing collection
     */
    const updateCollection = async (id, formData) => {
        fieldErrors.value = {};
        error.value = null;
        try {
            const response = await put(`/collections/${id}`, formData);
            if (response.success) {
                // Update in local list
                const idx = collections.value.findIndex(c => c.id === id);
                if (idx !== -1) {
                    collections.value[idx] = { ...collections.value[idx], ...response.data };
                }
                if (currentCollection.value && currentCollection.value.id === id) {
                    currentCollection.value = { ...currentCollection.value, ...response.data };
                }
                return response.data;
            }
        } catch (err) {
            parseErrors(err);
            throw err;
        }
    };

    /**
     * Delete a collection
     */
    const deleteCollection = async (id) => {
        try {
            const response = await del(`/collections/${id}`);
            if (response.success) {
                collections.value = collections.value.filter(c => c.id !== id);
                if (currentCollection.value && currentCollection.value.id === id) {
                    currentCollection.value = null;
                }
                return true;
            }
        } catch (err) {
            console.error('Error deleting collection', err);
            throw err;
        }
    };

    /**
     * Add a game to a collection
     */
    const addGameToCollection = async (collectionId, gameId) => {
        error.value = null;
        try {
            const response = await post(`/collections/${collectionId}/games/${gameId}`, {});
            if (response.success) {
                // If we are looking at this collection, ideally we might want to reload it
                // We'll just trust the caller to reload details.
                return true;
            }
        } catch (err) {
            error.value = err.message || 'No se pudo añadir el juego.';
            throw err;
        }
    };

    /**
     * Remove a game from a collection
     */
    const removeGameFromCollection = async (collectionId, gameId) => {
        error.value = null;
        try {
            const response = await del(`/collections/${collectionId}/games/${gameId}`);
            if (response.success) {
                // Optimiztically remove if we are viewing the details
                if (currentCollection.value && currentCollection.value.id === collectionId) {
                    currentCollection.value.games = currentCollection.value.games.filter(g => g.id !== gameId);
                }
                return true;
            }
        } catch (err) {
             error.value = err.message || 'No se pudo retirar el juego.';
             throw err;
        }
    };

    return {
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
    };
}
