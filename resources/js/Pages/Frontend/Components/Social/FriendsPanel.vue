<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useChat } from '../../../../Composables/useChat';

const { state, fetchInbox, fetchPendingRequests, openChat, isUserOnline, notificationTotal } = useChat();

const isPanelOpen = ref(false);
const activeTab = ref('friends'); // 'friends', 'requests', 'search'

const friendsList = ref([]);
const searchResults = ref([]);
const searchQuery = ref('');

const togglePanel = () => {
    isPanelOpen.value = !isPanelOpen.value;
    if (isPanelOpen.value) {
        fetchData();
    }
};

const fetchData = async () => {
    try {
        const { data } = await axios.get('/api/friends');
        friendsList.value = data.friends || [];
        fetchInbox();
        fetchPendingRequests();
    } catch (e) {
        console.error("Error fetching dependencies", e);
    }
};

const sendFriendRequest = async (userId) => {
    try {
        await axios.post('/api/friends/request', { friend_id: userId });
        alert('Solicitud enviada!');
        searchQuery.value = '';
        searchResults.value = [];
    } catch (e) {
        if (e.response?.data?.message) {
            alert(e.response.data.message);
        } else {
            console.error(e);
        }
    }
};

const acceptRequest = async (userId) => {
    try {
        await axios.post('/api/friends/accept', { friend_id: userId });
        fetchData();
    } catch (e) {
        console.error(e);
    }
};

const rejectRequest = async (userId) => {
    try {
        await axios.post('/api/friends/reject', { friend_id: userId });
        fetchData();
    } catch (e) {
        console.error(e);
    }
};

let searchTimeout;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    if (!searchQuery.value) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(async () => {
        try {
            const { data } = await axios.get(`/api/users?search=${searchQuery.value}`);
            searchResults.value = data.data || data;
        } catch (e) {
            console.error(e);
        }
    }, 400);
};

// Close panel if clicked outside
const closePanelIfOutside = (e) => {
    if (isPanelOpen.value && !e.target.closest('.friends-panel-container')) {
        isPanelOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closePanelIfOutside);
});
onUnmounted(() => {
    document.removeEventListener('click', closePanelIfOutside);
});

</script>

<template>
    <div class="position-relative friends-panel-container">
        <!-- TRIGGER BUTTON -->
        <button class="btn btn-outline-secondary position-relative" @click="togglePanel">
            <i class="fas fa-user-friends"></i> Amigos
            <span v-if="notificationTotal > 0"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size: 0.65rem;">
                {{ notificationTotal }}
            </span>
        </button>

        <!-- PANEL -->
        <div v-if="isPanelOpen" class="friends-panel border border-secondary shadow-lg">

            <!-- Tabs -->
            <div class="d-flex border-bottom border-secondary mb-2">
                <button class="flex-grow-1 p-2 border-0 bg-transparent text-white"
                    :class="{ 'fw-bold border-bottom border-primary border-2': activeTab === 'friends' }"
                    @click="activeTab = 'friends'">
                    Amigos
                </button>
                <button class="flex-grow-1 p-2 border-0 bg-transparent text-white position-relative"
                    :class="{ 'fw-bold border-bottom border-primary border-2': activeTab === 'requests' }"
                    @click="activeTab = 'requests'">
                    Solicitudes
                    <span v-if="state.pendingRequests.length > 0" class="badge bg-danger rounded-circle p-1 ms-1"
                        style="font-size:0.5rem">
                        {{ state.pendingRequests.length }}
                    </span>
                </button>
                <button class="flex-grow-1 p-2 border-0 bg-transparent text-white"
                    :class="{ 'fw-bold border-bottom border-primary border-2': activeTab === 'search' }"
                    @click="activeTab = 'search'">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <!-- Tab: Friends -->
            <div v-if="activeTab === 'friends'" class="panel-content">
                <div v-if="friendsList.length === 0" class="text-muted small text-center mt-4">
                    Aún no tienes amigos agregados.
                </div>
                <div v-for="friend in friendsList" :key="friend.id" class="friend-item" @click="openChat(friend)">
                    <div class="d-flex align-items-center gap-2 w-100">
                        <div class="avatar-sm position-relative">
                            {{ friend.name[0].toUpperCase() }}
                            <!-- ONLINE DOT -->
                            <div class="online-dot"
                                :class="{ 'bg-success': isUserOnline(friend.id).value, 'bg-secondary': !isUserOnline(friend.id).value }">
                            </div>
                        </div>
                        <div class="flex-grow-1 text-truncate small text-white">{{ friend.name }}</div>
                        <i class="fas fa-comment-dots text-muted"></i>
                    </div>
                </div>
            </div>

            <!-- Tab: Requests -->
            <div v-if="activeTab === 'requests'" class="panel-content">
                <div v-if="state.pendingRequests.length === 0" class="text-muted small text-center mt-4">
                    No tienes solicitudes pendientes.
                </div>
                <div v-for="req in state.pendingRequests" :key="req.id" class="p-2 border-bottom border-secondary">
                    <div class="small text-white text-truncate mb-2">{{ req.user.name }}</div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary py-0" style="flex:1"
                            @click="acceptRequest(req.user.id)">Aceptar</button>
                        <button class="btn btn-sm btn-outline-danger py-0" style="flex:1"
                            @click="rejectRequest(req.user.id)">Ignorar</button>
                    </div>
                </div>
            </div>

            <!-- Tab: Search -->
            <div v-if="activeTab === 'search'" class="panel-content p-2">
                <input type="text" v-model="searchQuery" @input="handleSearch"
                    class="form-control form-control-sm bg-dark text-white border-secondary mb-2"
                    placeholder="Buscar por nombre...">

                <div v-for="user in searchResults" :key="user.id"
                    class="d-flex align-items-center justify-content-between p-2 hover-bg">
                    <div class="small text-white text-truncate">{{ user.name }}</div>
                    <button class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill"
                        @click="sendFriendRequest(user.id)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>


