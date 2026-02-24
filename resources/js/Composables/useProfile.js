import { ref, onMounted } from 'vue';
import { useApi } from './useApi';

export function useProfile() {
    const { get, put, error, loading } = useApi();

    const profile = ref({
        first_name: '',
        last_name: '',
        nickname: '',
        age: '',
        genre: '',
        bio: '',
        photo: '',
        share_email: false,
        available_for_online: true
    });

    const isLoading = ref(true);
    const isSaving = ref(false);
    const successMessage = ref('');

    const loadProfile = async () => {
        isLoading.value = true;
        try {
            const res = await get('/profile');
            if (res.profile) {
                profile.value = { ...profile.value, ...res.profile };
                profile.value.share_email = !!profile.value.share_email;
                profile.value.available_for_online = !!profile.value.available_for_online;
            }
        } catch (err) {
            console.error('Error cargando perfil:', err);
            error.value = 'No se pudo cargar el perfil.';
        } finally {
            isLoading.value = false;
        }
    };

    const updateProfile = async () => {
        isSaving.value = true;
        successMessage.value = '';
        error.value = '';

        try {
            const res = await put('/profile', profile.value);
            successMessage.value = res.message || 'Perfil actualizado correctamente';

            if (res.profile) {
                profile.value = { ...profile.value, ...res.profile };
                profile.value.share_email = !!profile.value.share_email;
                profile.value.available_for_online = !!profile.value.available_for_online;
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (err) {
            console.error('Error actualizando perfil:', err);
            error.value = err.message || 'Error al guardar el perfil.';
        } finally {
            isSaving.value = false;
        }
    };

    onMounted(() => {
        loadProfile();
    });

    return {
        profile,
        isLoading,
        isSaving,
        error,
        successMessage,
        loadProfile,
        updateProfile
    };
}
