import { ref, reactive } from 'vue';
import { useApi } from './useApi';

export function useSocial() {
    const api = useApi();
    const loading = api.loading;
    const isSaving = ref(false);
    const error = api.error;

    const platforms = ref([]);
    const profiles = ref([]);

    const form = reactive({
        social_platform_id: '',
        gamertag: '',
        external_user_id: '',
        profile_url: ''
    });

    const loadPlatforms = async () => {
        try {
            const response = await api.get('/social-platforms');
            platforms.value = response.platforms || [];
        } catch (err) {
            console.error('Error loading social platforms:', err);
        }
    };

    const loadProfiles = async () => {
        try {
            const response = await api.get('/social-profiles');
            profiles.value = response.profiles || [];
        } catch (err) {
            console.error('Error loading social profiles:', err);
            error.value = error.value || 'Error al cargar los perfiles sociales.';
        }
    };

    const loadProfile = async (id) => {
        try {
            const response = await api.get(`/social-profiles/${id}`);
            const data = response.profile;
            form.social_platform_id = data.social_platform_id;
            form.gamertag = data.gamertag;
            form.external_user_id = data.external_user_id || '';
            form.profile_url = data.profile_url || '';
        } catch (err) {
            console.error('Error loading social profile:', err);
            error.value = error.value || 'Error al cargar el perfil social.';
        }
    };

    const saveProfile = async (id = null) => {
        isSaving.value = true;
        api.error.value = null;
        try {
            const payload = { ...form };
            if (!payload.external_user_id) delete payload.external_user_id;
            if (!payload.profile_url) delete payload.profile_url;

            if (id) {
                await api.put(`/social-profiles/${id}`, payload);
            } else {
                await api.post('/social-profiles', payload);
            }
        } catch (err) {
            console.error('Error saving social profile:', err);
            if (err.status === 400 && err.errors) {
                api.error.value = Object.values(err.errors).flat().join('<br>');
            } else {
                api.error.value = err.message || 'Error al guardar el perfil social.';
            }
        } finally {
            isSaving.value = false;
        }
    };

    const deleteProfile = async (id) => {
        if (!confirm('¿Estás seguro de eliminar este perfil social?')) return;
        try {
            await api.del(`/social-profiles/${id}`);
            await loadProfiles();
        } catch (err) {
            console.error('Error deleting social profile:', err);
            alert(err.message || 'Error al eliminar el perfil social.');
        }
    };

    return {
        platforms,
        profiles,
        form,
        loading,
        isSaving,
        error,
        loadPlatforms,
        loadProfiles,
        loadProfile,
        saveProfile,
        deleteProfile
    };
}
