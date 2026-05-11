<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useNotifications } from '../../../Composables/useNotifications';

const {
    state,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    togglePanel,
    closePanel,
    loadMore,
    hasMore,
} = useNotifications();

const panelRef = ref(null);

// Close panel when clicking outside
const handleClickOutside = (event) => {
    if (panelRef.value && !panelRef.value.contains(event.target)) {
        closePanel();
    }
};

onMounted(() => {
    fetchUnreadCount();
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

/**
 * Map notification type to an icon class
 */
const getIcon = (type) => {
    const icons = {
        post_like: 'fas fa-heart',
        post_comment: 'fas fa-comment',
        comment_like: 'fas fa-thumbs-up',
        comment_reply: 'fas fa-reply',
        follow: 'fas fa-user-plus',
        friend_request: 'fas fa-user-friends',
        friend_accepted: 'fas fa-handshake',
        session_join: 'fas fa-gamepad',
        session_request: 'fas fa-sign-in-alt',
        contribution_resolved: 'fas fa-check-circle',
    };
    return icons[type] || 'fas fa-bell';
};

/**
 * Format relative time
 */
const timeAgo = (dateStr) => {
    const now = new Date();
    const date = new Date(dateStr);
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'ahora';
    if (seconds < 3600) return `hace ${Math.floor(seconds / 60)}m`;
    if (seconds < 86400) return `hace ${Math.floor(seconds / 3600)}h`;
    if (seconds < 604800) return `hace ${Math.floor(seconds / 86400)}d`;
    return date.toLocaleDateString();
};

const handleNotificationClick = (notification) => {
    if (!notification.read_at) {
        markAsRead(notification.id);
    }
};
</script>

<template>
    <div class="position-relative notification-panel-wrapper" ref="panelRef">
        <!-- Bell Button -->
        <button @click.stop="togglePanel" class="btn btn-sm btn-outline-secondary border-0 position-relative"
            title="Notificaciones">
            <i class="fas fa-bell" style="font-size: 1.2rem;"></i>
            <span v-if="state.unreadCount > 0" class="notification-badge">
                {{ state.unreadCount > 99 ? '99+' : state.unreadCount }}
            </span>
        </button>

        <!-- Dropdown Panel -->
        <div v-if="state.isOpen" class="notification-dropdown">
            <div class="notification-header">
                <span class="notification-title">Notificaciones</span>
                <button v-if="state.unreadCount > 0" @click="markAllAsRead" class="btn-mark-all"
                    title="Marcar todas como leídas">
                    <i class="fas fa-check-double"></i>
                </button>
            </div>

            <div class="notification-list">
                <!-- Loading -->
                <div v-if="state.loading && state.notifications.length === 0" class="notification-empty">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>

                <!-- Empty -->
                <div v-else-if="state.notifications.length === 0" class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No tienes notificaciones</p>
                </div>

                <!-- Notification Items -->
                <div v-for="notif in state.notifications" :key="notif.id" class="notification-item"
                    :class="{ unread: !notif.read_at }" @click="handleNotificationClick(notif)">
                    <div class="notification-icon">
                        <i :class="getIcon(notif.type)"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-message">{{ notif.message }}</p>
                        <span class="notification-time">{{ timeAgo(notif.created_at) }}</span>
                    </div>
                    <div v-if="!notif.read_at" class="notification-dot"></div>
                </div>

                <!-- Load More -->
                <button v-if="hasMore" @click="loadMore" class="notification-load-more" :disabled="state.loading">
                    {{ state.loading ? 'Cargando...' : 'Ver más' }}
                </button>
            </div>
        </div>
    </div>
</template>
