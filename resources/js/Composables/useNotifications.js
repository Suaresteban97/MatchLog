import { reactive, computed } from 'vue';
import { useApi } from './useApi';

// Global reactive state (shared across all components)
const state = reactive({
    notifications: [],
    unreadCount: 0,
    isOpen: false,
    pagination: null,
    loading: false,
});

export function useNotifications() {
    const { get, put } = useApi();

    /**
     * Fetch notifications from API
     */
    const fetchNotifications = async (page = 1) => {
        state.loading = true;
        try {
            const data = await get(`/notifications?page=${page}&per_page=20`, {}, true);
            state.notifications = page === 1 ? data.data : [...state.notifications, ...data.data];
            state.unreadCount = data.unread_count;
            state.pagination = data.pagination;
        } catch (error) {
            console.error('Error fetching notifications', error);
        } finally {
            state.loading = false;
        }
    };

    /**
     * Fetch only unread count (lightweight, for badge)
     */
    const fetchUnreadCount = async () => {
        try {
            const data = await get('/notifications/unread-count', {}, true);
            state.unreadCount = data.unread_count;
        } catch (error) {
            console.error('Error fetching unread count', error);
        }
    };

    /**
     * Mark a single notification as read
     */
    const markAsRead = async (notificationId) => {
        try {
            await put(`/notifications/${notificationId}/read`, {}, {}, true);
            const notif = state.notifications.find(n => n.id === notificationId);
            if (notif) {
                notif.read_at = new Date().toISOString();
            }
            state.unreadCount = Math.max(0, state.unreadCount - 1);
        } catch (error) {
            console.error('Error marking notification as read', error);
        }
    };

    /**
     * Mark all notifications as read
     */
    const markAllAsRead = async () => {
        try {
            await put('/notifications/read-all', {}, {}, true);
            state.notifications.forEach(n => {
                if (!n.read_at) n.read_at = new Date().toISOString();
            });
            state.unreadCount = 0;
        } catch (error) {
            console.error('Error marking all as read', error);
        }
    };

    /**
     * Listen for real-time notifications via Echo (WebSocket)
     */
    const initNotificationEcho = (userId) => {
        if (!window.Echo) return;

        window.Echo.private(`App.Models.User.${userId}`)
            .listen('NotificationCreated', (e) => {
                // Add to the beginning of the list
                state.notifications.unshift(e);
                state.unreadCount++;
            });
    };

    /**
     * Toggle the notifications dropdown
     */
    const togglePanel = () => {
        state.isOpen = !state.isOpen;
        if (state.isOpen && state.notifications.length === 0) {
            fetchNotifications();
        }
    };

    const closePanel = () => {
        state.isOpen = false;
    };

    /**
     * Load more (infinite scroll / pagination)
     */
    const loadMore = async () => {
        if (state.pagination && state.pagination.current_page < state.pagination.last_page) {
            await fetchNotifications(state.pagination.current_page + 1);
        }
    };

    const hasMore = computed(() => {
        return state.pagination && state.pagination.current_page < state.pagination.last_page;
    });

    return {
        state,
        fetchNotifications,
        fetchUnreadCount,
        markAsRead,
        markAllAsRead,
        initNotificationEcho,
        togglePanel,
        closePanel,
        loadMore,
        hasMore,
    };
}
