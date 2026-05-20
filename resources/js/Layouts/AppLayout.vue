<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { useApi } from '../Composables/useApi';
import { ref, onMounted, computed } from 'vue';
import { useChat } from '../Composables/useChat';
import { useNotifications } from '../Composables/useNotifications';
import FriendsPanel from '../Pages/Frontend/Components/Social/FriendsPanel.vue';
import GlobalChatWidget from '../Pages/Frontend/Components/Social/GlobalChatWidget.vue';
import NotificationPanel from '../Pages/Frontend/Components/NotificationPanel.vue';
import PostDetailModal from '../Pages/Frontend/Components/PostDetailModal.vue';

const { post } = useApi();
const { initGlobalEcho } = useChat();
const { initNotificationEcho } = useNotifications();
const page = usePage();

const isAuthenticated = computed(() => !!page.props.auth?.user);

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
    isVintage.value = document.documentElement.classList.contains('theme-vintage');

    const authUser = page.props.auth?.user;
    if (authUser) {
        initGlobalEcho(authUser.id);
        initNotificationEcho(authUser.id);
    }
});

const logout = async () => {
    try {
        await post('/logout');
        router.visit('/');
    } catch (e) {
        router.visit('/');
    }
};
</script>

<template>
    <div>
        <nav id="main-navbar" class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <!-- Brand always links to / (landing if guest, dashboard if auth) -->
                <Link class="navbar-brand fw-bold" href="/">MatchLog</Link>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- Only show nav links when logged in -->
                    <ul class="navbar-nav me-auto" v-if="isAuthenticated">
                        <li class="nav-item">
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/my-space') || $page.url === '/social' }"
                                href="/my-space">
                                <i class="fas fa-satellite-dish me-1"></i> Social
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/games') }"
                                href="/games">
                                <i class="fas fa-gamepad me-1"></i> Juegos
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/profile') || $page.url.startsWith('/devices') }"
                                href="/profile">
                                <i class="fas fa-user me-1"></i> Mi Espacio
                            </Link>
                        </li>
                        <li class="nav-item" v-if="$page.props.auth?.user?.role?.slug === 'admin'">
                            <Link class="nav-link"
                                :class="{ active: $page.url.startsWith('/admin/moderation') }"
                                href="/admin/moderation">
                                <i class="fas fa-shield-alt me-1"></i> Moderación
                            </Link>
                        </li>
                    </ul>
                    <ul class="navbar-nav me-auto" v-else></ul>

                    <div class="d-flex align-items-center gap-3">
                        <template v-if="isAuthenticated">
                            <NotificationPanel />
                            <FriendsPanel />
                        </template>

                        <!-- Theme Toggle -->
                        <button @click="toggleTheme" class="btn btn-sm btn-outline-secondary border-0"
                            :title="isVintage ? 'Volver a Cyberpunk' : 'Modo Vintage'">
                            <i :class="isVintage ? 'fas fa-desktop' : 'fas fa-book-open'" style="font-size: 1.2rem;"></i>
                        </button>

                        <button v-if="isAuthenticated" @click="logout" class="btn btn-outline-danger btn-sm">
                            Cerrar Sesión
                        </button>
                        <template v-else>
                            <Link href="/login" class="btn btn-outline-primary btn-sm">Iniciar Sesión</Link>
                            <Link href="/login" class="btn btn-primary btn-sm">Registrarse</Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <main class="container">
            <slot />
        </main>

        <GlobalChatWidget />
        <PostDetailModal />
    </div>
</template>
