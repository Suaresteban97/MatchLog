
import General from '../General.js';

const { createApp } = Vue;

const Login = createApp({
    data() {
        return {
            email: '',
            password: '',
            error: null,
            loading: false
        }
    },
    methods: {
        async login() {
            this.loading = true;
            this.error = null;

            try {
                const response = await General.post('/login', {
                    email: this.email,
                    password: this.password
                }, {
                    'Admin-Authorization': window.ADMIN_TOKEN
                });

                window.location.href = '/dashboard';

            } catch (err) {
                console.error(err);
                this.error = err.message || 'Error al iniciar sesión';
            } finally {
                this.loading = false;
            }
        }
    }
});

Login.mount("#login");