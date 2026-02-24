import { ref } from 'vue';
import { useApi } from './useApi';
import { router } from '@inertiajs/vue3';

export function useLogin() {
    const { post, error, loading } = useApi();

    const email = ref('');
    const password = ref('');

    const login = async () => {
        try {
            await post('/login', {
                email: email.value,
                password: password.value
            }, {
                // Asumo que ADMIN_TOKEN se pasa globalmente o se captura después,
                // si es necesario lo podemos obtener del DOM/Window aquí
                'Admin-Authorization': window.ADMIN_TOKEN || ''
            });

            // Si el login de la API fue correcto (en backend crea la sesión Sanctum),
            // con Inertia redirigimos al dashboard sin recargar todo el browser.
            router.visit('/dashboard');
        } catch (err) {
            // El error real se delega a `error.value` de useApi
            console.error('Fallo en el login:', err);
        }
    };

    return {
        email,
        password,
        login,
        error,
        loading
    };
}
