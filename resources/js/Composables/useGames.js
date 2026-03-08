import { ref, reactive } from 'vue';
import { useApi } from './useApi';

export function useGames() {
    const { get, post, put, error, loading } = useApi();

    const myGames = ref([]);
    const catalog = ref([]);
    const metadata = reactive({
        statuses: [],
        platforms: []
    });

    const loadMyGames = async () => {
        try {
            const response = await get('/my-games');
            myGames.value = response.data || [];
        } catch (err) {
            console.error('Error loading my games:', err);
        }
    };

    const loadMetadata = async () => {
        try {
            const response = await get('/games-metadata');
            metadata.statuses = response.statuses;
            metadata.platforms = response.platforms;
        } catch (err) {
            console.error('Error loading games metadata:', err);
        }
    };

    const searchGames = async (name) => {
        if (!name || name.length < 3) {
            catalog.value = [];
            return;
        }
        try {
            const response = await get(`/games?name=${encodeURIComponent(name)}`);
            catalog.value = response.data || [];
        } catch (err) {
            console.error('Error searching games:', err);
        }
    };

    const toggleGame = async (gameId, platformId = null) => {
        try {
            const payload = platformId ? { game_platform_id: platformId } : {};
            const response = await post(`/my-games/${gameId}/toggle`, payload);
            await loadMyGames();
            return response;
        } catch (err) {
            console.error('Error toggling game:', err);
            throw err;
        }
    };

    const updateUserGame = async (gameId, data) => {
        try {
            const response = await put(`/my-games/${gameId}`, data);
            await loadMyGames();
            return response;
        } catch (err) {
            console.error('Error updating user game:', err);
            if (err.errors) {
                error.value = Object.values(err.errors).flat().join(' | ');
            } else {
                error.value = err.message || 'Error al actualizar el juego.';
            }
            throw err;
        }
    };

    return {
        myGames,
        catalog,
        metadata,
        loading,
        error,
        loadMyGames,
        loadMetadata,
        searchGames,
        toggleGame,
        updateUserGame
    };
}
