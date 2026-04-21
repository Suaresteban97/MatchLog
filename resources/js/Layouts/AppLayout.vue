<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { useApi } from '../Composables/useApi';
import { ref, onMounted } from 'vue';
import { useChat } from '../Composables/useChat';
import FriendsPanel from '../Pages/Frontend/Components/Social/FriendsPanel.vue';
import GlobalChatWidget from '../Pages/Frontend/Components/Social/GlobalChatWidget.vue';

const { post } = useApi();
const { initGlobalEcho } = useChat();
const page = usePage();

const isVintage = ref(false);

const toggleTheme = () => {
    isVintage.value = !isVintage.value;
    if (isVintage.value) {
        document.documentElement.classList.add('theme-vintage');
        document.cookie = "theme=vintage; max-age=31536000; path=/";
    } else {
        document.documentElement.classList.remove('theme-vintage');
        document.cookie = "theme=cyberpunk; max-age=31536000; path=/";
    }
};

onMounted(() => {
    // Check if the script in app.blade.php already applied the class
    isVintage.value = document.documentElement.classList.contains('theme-vintage');
    
    // Join global echo presence channel
    const authUser = page.props.auth?.user;
    if (authUser) {
        initGlobalEcho(authUser.id);
    }
});

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
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/my-space') || $page.url === '/social' }"
                                href="/my-space">
                                <i class="fas fa-satellite-dish me-1"></i> Social
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/games') || $page.url === '/games' }"
                                href="/games">
                                <i class="fas fa-gamepad me-1"></i> Juegos
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/profile') || $page.url.startsWith('/devices') || $page.url.startsWith('/social-profiles') }"
                                href="/profile">
                                <i class="fas fa-user me-1"></i> Mi Espacio
                            </Link>
                        </li>
                        <li class="nav-item" v-if="$page.props.auth.user?.role?.slug === 'admin'">
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/admin/moderation') }"
                                href="/admin/moderation">
                                <i class="fas fa-shield-alt me-1"></i> Moderación
                            </Link>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <FriendsPanel />

                        <!-- Theme Toggle Button -->
                        <button @click="toggleTheme" class="btn btn-sm btn-outline-secondary border-0" :title="isVintage ? 'Volver a Cyberpunk' : 'Modo Vintage (Clásico)'">
                            <i :class="isVintage ? 'fas fa-desktop' : 'fas fa-book-open'" style="font-size: 1.2rem;"></i>
                        </button>
                        
                        <button @click="logout" class="btn btn-outline-danger btn-sm">Cerrar Sesión</button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container">
            <slot />
        </main>
        <!-- Global Chat Widget (Floating) -->
        <GlobalChatWidget />
    </div>
</template>
