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
                'Admin-Authorization': window.ADMIN_TOKEN || ''
            });

            router.visit('/dashboard');
        } catch (err) {
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
