<script setup>
import { Link, router } from '@inertiajs/vue3';
import { useApi } from '../Composables/useApi';

const { post } = useApi();

const logout = async () => {
    try {
        await post('/logout');
        router.visit('/login');
    } catch (e) {
        console.error(e);
        router.visit('/login');
    }
};
</script>

<template>
    <div>
        <nav id="main-navbar" class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <Link class="navbar-brand fw-bold" href="/dashboard">Matchlog</Link>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <Link class="nav-link" :class="{ active: $page.url.startsWith('/my-space') }" href="/my-space">
                                <i class="fas fa-user-astronaut me-1"></i> Mi Espacio
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link class="nav-link" :class="{ active: $page.url.startsWith('/devices') }" href="/devices">
                                <i class="fas fa-gamepad me-1"></i> Dispositivos
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link class="nav-link" :class="{ active: $page.url.startsWith('/profile') }" href="/profile">
                                <i class="fas fa-cog me-1"></i> Perfil
                            </Link>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <button @click="logout" class="btn btn-outline-danger btn-sm">Cerrar Sesión</button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container">
            <slot />
        </main>
    </div>
</template>
