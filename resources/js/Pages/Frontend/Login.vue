<script setup>
import { Head } from '@inertiajs/vue3';
import { useLogin } from '../../Composables/useLogin';

// Extraemos los métodos y el estado del composable
const { email, password, login, error, loading } = useLogin();

const submit = async () => {
    await login();
};
</script>

<template>
    <Head title="Login" />

    <div id="login">
        <div class="login-container">
            <h2>MY BACKLOG</h2>
            
            <div v-if="error" class="alert alert-danger mb-4" role="alert" style="background: rgba(220,53,69,0.2) !important; color: var(--neon-pink); border-color: var(--neon-pink);">
                {{ error }}
            </div>

            <form @submit.prevent="submit">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input
                        id="email"
                        type="email"
                        v-model="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Correo electrónico"
                    />
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input
                        id="password"
                        type="password"
                        v-model="password"
                        required
                        autocomplete="current-password"
                        placeholder="Contraseña"
                    />
                </div>

                <div class="mt-4">
                    <button class="login-btn" :class="{ 'disabled': loading }" :disabled="loading">
                        <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
                        {{ loading ? 'Ingresando...' : 'INICIAR SESIÓN' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
