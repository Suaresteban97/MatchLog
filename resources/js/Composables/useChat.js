import { reactive, ref, computed } from 'vue';
import axios from 'axios';

// Global reactive state
const state = reactive({
    isChatOpen: false,
    activeFriend: null, // The friend currently being chatted with { id, name }
    onlineUsers: [], // IDs of users currently online via Reverb
    inbox: [], // Array of conversations
    unreadTotal: 0,
    pendingRequests: [] // Array of pending friend requests
});

export function useChat() {
    
    /**
     * Join the global presence channel to track online users
     */
    const initGlobalEcho = (userId) => {
        if (!window.Echo) return;

        window.Echo.join('global')
            .here((users) => {
                state.onlineUsers = users.map(u => u.id);
            })
            .joining((user) => {
                if (!state.onlineUsers.includes(user.id)) {
                    state.onlineUsers.push(user.id);
                }
            })
            .leaving((user) => {
                state.onlineUsers = state.onlineUsers.filter(id => id !== user.id);
            });

        if (userId) {
            window.Echo.private(`App.Models.User.${userId}`)
                .listen('DirectMessageSent', async (e) => {
                    // Refresh inbox to show notification badge
                    await fetchInbox();
                    
                    // Auto-open chat if it's not already open with this sender
                    if (!state.isChatOpen || state.activeFriend?.id !== e.sender_id) {
                        const conv = state.inbox.find(c => c.conversation_id === e.conversation_id);
                        if (conv && conv.friend) {
                            openChat(conv.friend);
                        }
                    }
                });
        }
    };

    /**
     * Fetch standard inbox and calculate unread
     */
    const fetchInbox = async () => {
        try {
            const { data } = await axios.get('/api/chats');
            state.inbox = data.conversations || [];
            recalculateUnread();
        } catch (error) {
            console.error("Error fetching inbox", error);
        }
    };

    /**
     * Fetch pending requests for notifications
     */
    const fetchPendingRequests = async () => {
        try {
            const { data } = await axios.get('/api/friends/pending');
            state.pendingRequests = data.pending_requests || [];
        } catch (error) {
            console.error("Error fetching pending requests", error);
        }
    };

    const recalculateUnread = () => {
        state.unreadTotal = state.inbox.reduce((acc, sum) => acc + (sum.unread_count || 0), 0);
    };

    /**
     * Open a chat with a specific user
     */
    const openChat = (friend) => {
        state.activeFriend = friend;
        state.isChatOpen = true;
    };

    const closeChat = () => {
        state.isChatOpen = false;
        state.activeFriend = null;
    };

    /**
     * Helpers
     */
    const isUserOnline = (userId) => computed(() => state.onlineUsers.includes(userId));
    
    // Bubble total = unread chats + pending requests
    const notificationTotal = computed(() => state.unreadTotal + state.pendingRequests.length);

    return {
        state,
        initGlobalEcho,
        fetchInbox,
        fetchPendingRequests,
        openChat,
        closeChat,
        isUserOnline,
        notificationTotal
    };
}
