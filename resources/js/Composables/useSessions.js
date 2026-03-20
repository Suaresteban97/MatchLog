import { ref, reactive } from 'vue';
import { useApi } from './useApi';

export function useSessions() {
    const { get, post, put, del, error, loading } = useApi();

    const fieldErrors = ref({});

    const browseSessions = ref([]);
    const mySessions = reactive({
        hosting: [],
        participating: []
    });

    // Create / Edit form state
    const sessionForm = ref({
        title: '',
        game_id: '',
        description: '',
        start_time: '',
        max_participants: 2,
        link: ''
    });

    // Load all public/browseable sessions (with optional filters)
    const loadBrowseSessions = async (filters = {}) => {
        try {
            // Build query string from non-empty filters
            const params = new URLSearchParams();
            for (const [key, value] of Object.entries(filters)) {
                if (value !== '' && value !== null && value !== undefined) {
                    params.append(key, value);
                }
            }
            const queryStr = params.toString() ? `?${params.toString()}` : '';

            const response = await get(`/sessions/browse${queryStr}`);
            browseSessions.value = response.data || [];
        } catch (err) {
            console.error('Error loading browse sessions:', err);
        }
    };

    // Load user's hosting + participating sessions
    const loadMySessions = async () => {
        try {
            const response = await get('/sessions');
            mySessions.hosting = response.hosting || [];
            mySessions.participating = response.participating || [];
        } catch (err) {
            console.error('Error loading my sessions:', err);
        }
    };

    const loadAll = () => Promise.all([loadBrowseSessions(), loadMySessions()]);

    const createSession = async (data) => {
        fieldErrors.value = {};
        try {
            const response = await post('/sessions', data);
            await loadAll();
            return response;
        } catch (err) {
            if (err.errors) {
                fieldErrors.value = err.errors;
                error.value = err.message || 'Revisa los campos marcados.';
            } else {
                error.value = err.message || 'Error al crear la sesión.';
            }
            throw err;
        }
    };

    const updateSession = async (id, data) => {
        fieldErrors.value = {};
        try {
            const response = await put(`/sessions/${id}`, data);
            await loadMySessions();
            return response;
        } catch (err) {
            if (err.errors) {
                fieldErrors.value = err.errors;
                error.value = err.message || 'Revisa los campos marcados.';
            } else {
                error.value = err.message || 'Error al actualizar la sesión.';
            }
            throw err;
        }
    };

    const deleteSession = async (id) => {
        try {
            await del(`/sessions/${id}`);
            await loadMySessions();
        } catch (err) {
            error.value = err.message || 'Error al eliminar la sesión.';
            throw err;
        }
    };

    const joinSession = async (id) => {
        try {
            const response = await post(`/sessions/${id}/join`);
            await loadAll();
            return response;
        } catch (err) {
            error.value = err.message || 'Error al unirse a la sesión.';
            throw err;
        }
    };

    const leaveSession = async (id) => {
        try {
            await post(`/sessions/${id}/leave`);
            await loadAll();
        } catch (err) {
            error.value = err.message || 'Error al abandonar la sesión.';
            throw err;
        }
    };

    const resetForm = () => {
        sessionForm.value = {
            title: '',
            game_id: '',
            description: '',
            start_time: '',
            max_participants: 2,
            link: ''
        };
    };

    return {
        browseSessions,
        mySessions,
        sessionForm,
        loading,
        error,
        fieldErrors,
        loadAll,
        loadBrowseSessions,
        loadMySessions,
        createSession,
        updateSession,
        deleteSession,
        joinSession,
        leaveSession,
        resetForm
    };
}
