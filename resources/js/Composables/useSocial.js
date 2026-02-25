import { ref, onMounted } from 'vue';
import { useApi } from './useApi';

export function useSocial() {
    const { get, error, loading } = useApi();

    const profile = ref(null);
    const isLoading = ref(true); // Estado de carga inicial

    const fetchProfile = async () => {
        isLoading.value = true;
        try {
            // Ejemplo de llamada a la API basándonos en tu estructura
            // const response = await get('/social-profiles');
            // profile.value = response.data;
        } catch (err) {
            console.error("Error cargando perfil social", err);
        } finally {
            isLoading.value = false;
        }
    };

    onMounted(() => {
        fetchProfile();
    });

    return {
        profile,
        isLoading,
        error,
        fetchProfile
    };
}
