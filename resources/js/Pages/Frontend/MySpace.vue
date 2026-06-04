<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useSessions } from '../../Composables/useSessions';
import { useGames } from '../../Composables/useGames';
import CollectionsPanel from './Components/Collections/CollectionsPanel.vue';

const activeTab = ref('sesiones'); // 'sesiones' or 'colecciones'
const sessionTab = ref('explorar'); // 'explorar' or 'mis-sesiones'

const {
    browseSessions, mySessions, sessionForm,
    loading, error, fieldErrors,
    loadAll, loadBrowseSessions, loadMySessions,
    createSession, updateSession, deleteSession,
    joinSession, leaveSession, resetForm,
    getMessages, sendMessage
} = useSessions();

// Load user's own games for the game_id selector
const { myGames, loadMyGames } = useGames();

// Current authenticated user
const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const copied = ref(false);
const copyPublicProfileLink = () => {
    if (!authUser.value?.name) return;
    const url = `${window.location.origin}/user/${authUser.value.name}`;
    navigator.clipboard.writeText(url).then(() => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    });
};

// Setup browse filters
const browseFilters = ref({
    search: '',
    game_id: '',
    start_date: '',
    end_date: '',
    available_only: false
});

const applyFilters = () => {
    loadBrowseSessions(browseFilters.value);
};

const clearFilters = () => {
    browseFilters.value = {
        search: '',
        game_id: '',
        start_date: '',
        end_date: '',
        available_only: false
    };
    applyFilters();
};

// Modal state
const showModal = ref(false);
const editingSession = ref(null);

// Split date/time pickers
const pickerDate = ref('');
const pickerHour = ref('18');
const pickerMinute = ref('00');

// Build hour and minute options
const hourOptions = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const minuteOptions = ['00', '15', '30', '45'];

// Sync split fields → sessionForm.start_time with local timezone offset
const syncStartTime = () => {
    if (pickerDate.value) {
        // Build a Date from local parts, then get ISO string with offset
        const local = new Date(`${pickerDate.value}T${pickerHour.value}:${pickerMinute.value}:00`);
        const offsetMin = local.getTimezoneOffset(); // in minutes, negative for UTC+
        const sign = offsetMin <= 0 ? '+' : '-';
        const absH = String(Math.floor(Math.abs(offsetMin) / 60)).padStart(2, '0');
        const absM = String(Math.abs(offsetMin) % 60).padStart(2, '0');
        sessionForm.value.start_time = `${pickerDate.value}T${pickerHour.value}:${pickerMinute.value}:00${sign}${absH}:${absM}`;
    }
};

const watchPicker = () => syncStartTime();

// Status badges config
const statusConfig = {
    scheduled: { label: 'Programada', class: 'bg-primary' },
    active: { label: 'En curso', class: 'bg-success' },
    finished: { label: 'Finalizada', class: 'bg-secondary' },
    cancelled: { label: 'Cancelada', class: 'bg-danger' },
};

const statusOptions = [
    { value: 'scheduled', label: 'Programada' },
    { value: 'active', label: 'En curso' },
    { value: 'finished', label: 'Finalizada' },
    { value: 'cancelled', label: 'Cancelada' },
];

const modalTitle = computed(() => editingSession.value ? 'Editar Sesión' : 'Nueva Sesión');

const openCreateModal = () => {
    editingSession.value = null;
    resetForm();
    // Set pickers to today + 18:00
    const today = new Date();
    pickerDate.value = today.toISOString().split('T')[0];
    pickerHour.value = '18';
    pickerMinute.value = '00';
    syncStartTime();
    showModal.value = true;
};

const openEditModal = (session) => {
    editingSession.value = session;
    sessionForm.value = {
        title: session.title,
        game_id: session.game_id || '',
        description: session.description || '',
        start_time: '',
        max_participants: session.max_participants,
        link: session.link || '',
        status: session.status || 'scheduled',
    };
    // Populate split pickers
    if (session.start_time) {
        const dt = new Date(session.start_time);
        pickerDate.value = dt.toISOString().split('T')[0];
        pickerHour.value = String(dt.getHours()).padStart(2, '0');
        pickerMinute.value = String(Math.floor(dt.getMinutes() / 15) * 15).padStart(2, '0');
    }
    syncStartTime();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingSession.value = null;
    fieldErrors.value = {};
    resetForm();
};

const handleSubmit = async () => {
    try {
        if (editingSession.value) {
            await updateSession(editingSession.value.id, sessionForm.value);
        } else {
            await createSession(sessionForm.value);
        }
        closeModal();
    } catch {
        // error shown via `error` ref
    }
};

const handleDelete = async (id) => {
    if (!confirm('¿Eliminar esta sesión? Esta acción no se puede deshacer.')) return;
    await deleteSession(id);
};

const handleJoin = async (id) => {
    await joinSession(id);
};

const handleLeave = async (id, session) => {
    const isHost = session?.host?.id === authUser.value?.id;
    const msg = isHost
        ? '⚠️ Eres el anfitrión. Abandonar eliminará la sesión completa para todos. ¿Continuar?'
        : '¿Abandonar esta sesión?';
    if (!confirm(msg)) return;
    await leaveSession(id);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('es-ES', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

// Participants count is now accurate in DB (host is included), no manual +1 needed
const spotsLeft = (session) => {
    const taken = session.participants_count ?? session.participants?.length ?? 0;
    return session.max_participants - taken;
};

// Filter out sessions where the user is the host from the participating list
// (they show in the Hosting section already)
const onlyParticipating = computed(() =>
    mySessions.participating.filter(s => s.host_id !== authUser.value?.id)
);

// ===================== CHAT OFFCANVAS STATE =====================
const showChat = ref(false);
const currentChatSession = ref(null);
const chatMessages = ref([]);
const newMessage = ref('');
const chatContainer = ref(null);

const chatCurrentPage = ref(1);
const chatLastPage = ref(1);
const isChatLoadingMore = ref(false);

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const handleChatScroll = (e) => {
    if (e.target.scrollTop === 0) {
        loadMoreMessages();
    }
};

const loadMoreMessages = async () => {
    if (isChatLoadingMore.value || chatCurrentPage.value >= chatLastPage.value || !currentChatSession.value) return;

    isChatLoadingMore.value = true;
    const nextPage = chatCurrentPage.value + 1;

    try {
        const response = await getMessages(currentChatSession.value.id, nextPage);
        if (response && response.data) {
            const olderMessages = response.data.reverse();

            // Save current scroll height to restore position
            const container = chatContainer.value;
            const previousScrollHeight = container ? container.scrollHeight : 0;

            chatMessages.value = [...olderMessages, ...chatMessages.value];
            chatCurrentPage.value = response.current_page;

            // Restore scroll position so it doesn't jump
            await nextTick();
            if (container) {
                container.scrollTop = container.scrollHeight - previousScrollHeight;
            }
        }
    } catch (err) {
        console.error("Error loading more messages", err);
    } finally {
        isChatLoadingMore.value = false;
    }
};

const groupedChatMessages = computed(() => {
    const groups = [];
    let currentDate = null;
    let currentGroup = null;

    chatMessages.value.forEach(msg => {
        const dateObj = new Date(msg.created_at);
        const dateStr = dateObj.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        if (dateStr !== currentDate) {
            currentDate = dateStr;
            currentGroup = {
                date: dateStr,
                messages: []
            };
            groups.push(currentGroup);
        }
        currentGroup.messages.push(msg);
    });

    return groups;
});

const openChat = async (session) => {
    currentChatSession.value = session;
    showChat.value = true;
    chatCurrentPage.value = 1;
    chatLastPage.value = 1;

    const response = await getMessages(session.id, 1);

    chatMessages.value = response.data ? response.data.reverse() : [];
    chatCurrentPage.value = response.current_page || 1;
    chatLastPage.value = response.last_page || 1;

    scrollToBottom();

    // Listen to Reverb WebSocket
    if (window.Echo) {
        window.Echo.join(`session.${session.id}`)
            .listen('MessageSent', (e) => {
                const exists = chatMessages.value.some(m => m.id === e.id);
                if (!exists) {
                    chatMessages.value.push(e);
                    scrollToBottom();
                }
            });
    }
};

const closeChat = () => {
    if (currentChatSession.value && window.Echo) {
        window.Echo.leave(`session.${currentChatSession.value.id}`);
    }
    showChat.value = false;
    currentChatSession.value = null;
    chatMessages.value = [];
    newMessage.value = '';
    chatCurrentPage.value = 1;
    chatLastPage.value = 1;
};

const submitMessage = async () => {
    if (!newMessage.value.trim() || !currentChatSession.value) return;
    const text = newMessage.value;
    newMessage.value = ''; // clear input immediately 
    try {
        const savedMsg = await sendMessage(currentChatSession.value.id, text);
        const exists = chatMessages.value.some(m => m.id === savedMsg.id);
        if (!exists) {
            chatMessages.value.push(savedMsg);
            scrollToBottom();
        }
    } catch (err) {
        console.error("Failed to send message", err);
    }
};

onMounted(() => {
    loadMyGames();
    loadMySessions();
    loadBrowseSessions(browseFilters.value);
});
</script>

<template>

    <Head title="Mi Espacio" />

    <AppLayout v-cloak>
        <div class="row pt-4 g-4">

            <!-- ===================== TABS NAVIGATION ===================== -->
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom border-secondary pb-3 mb-4">
                 <ul class="nav nav-pills gap-2 border-0 mb-0 pb-0">
                     <li class="nav-item">
                         <button class="nav-link border" 
                            :class="activeTab === 'sesiones' ? 'active bg-primary border-primary text-white' : 'bg-dark text-muted border-secondary'" 
                            @click="activeTab = 'sesiones'">
                            <i class="fas fa-satellite-dish me-2"></i>Sesiones Multi-jugador
                         </button>
                     </li>
                     <li class="nav-item">
                         <button class="nav-link border" 
                            :class="activeTab === 'colecciones' ? 'active bg-primary border-primary text-white' : 'bg-dark text-muted border-secondary'" 
                            @click="activeTab = 'colecciones'">
                            <i class="fas fa-compact-disc me-2"></i>Mis Colecciones
                         </button>
                     </li>
                 </ul>

                 <!-- Mi Perfil button -->
                 <div v-if="authUser" class="d-flex align-items-center gap-2">
                     <a :href="`/user/${authUser.name}`" class="btn btn-outline-info rounded-pill px-3 fw-bold btn-sm shadow-sm" target="_blank">
                         <i class="fas fa-user me-1"></i>Mi Perfil Público
                     </a>
                     <button @click="copyPublicProfileLink" class="btn btn-outline-secondary rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" :title="copied ? '¡Copiado!' : 'Copiar Enlace Público'">
                         <i class="fas" :class="copied ? 'fa-check text-success' : 'fa-copy'"></i>
                     </button>
                 </div>
            </div>

            <!-- ===================== SESIONES TAB ===================== -->
            <template v-if="activeTab === 'sesiones'">
                <div class="col-12 d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-white"><i class="fas fa-gamepad me-2 text-primary"></i>Sesiones de Juego</h5>
                    <button @click="openCreateModal" class="btn btn-primary shadow-sm rounded-pill px-4 fw-bold" style="transition: all 0.2s ease;">
                        <i class="fas fa-plus me-2"></i>Crear Sesión
                    </button>
                </div>

                <!-- Sub Tabs para Sesiones -->
                <div class="col-12 mb-4">
                    <ul class="nav nav-tabs border-secondary">
                        <li class="nav-item">
                            <button class="nav-link border-0 text-white fw-bold" 
                                :class="sessionTab === 'explorar' ? 'active bg-dark border-bottom border-primary border-3 text-primary' : 'bg-transparent text-muted'" 
                                @click="sessionTab = 'explorar'" style="transition: color 0.3s ease;">
                                <i class="fas fa-globe me-2"></i>Explorar Sesiones
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link border-0 text-white fw-bold" 
                                :class="sessionTab === 'mis-sesiones' ? 'active bg-dark border-bottom border-primary border-3 text-primary' : 'bg-transparent text-muted'" 
                                @click="sessionTab = 'mis-sesiones'" style="transition: color 0.3s ease;">
                                <i class="fas fa-user-circle me-2"></i>Mis Sesiones
                            </button>
                        </li>
                    </ul>
                </div>

                <template v-if="sessionTab === 'explorar'">
                    <!-- ===================== BROWSE SESSIONS ===================== -->
                    <div class="col-12">
                        <!-- Filtros (Collapsible o más compactos) -->
                        <div class="card bg-dark border-secondary mb-4 shadow-sm" style="border-radius: 12px;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                                    <h6 class="text-white mb-0 fw-bold small"><i class="fas fa-filter me-2 text-info"></i>Filtros de Búsqueda</h6>
                                    <i class="fas fa-chevron-down text-muted small"></i>
                                </div>
                                <div class="collapse show" id="filtersCollapse">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-9">
                                            <div class="position-relative">
                                                <i class="fas fa-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 0.8rem;"></i>
                                                <input type="text" v-model="browseFilters.search" @keyup.enter="applyFilters"
                                                    class="form-control form-control-sm bg-black border-secondary text-white"
                                                    style="padding-left: 32px;"
                                                    placeholder="Buscar por título de sesión, anfitrión, nickname o correo...">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <select v-model="browseFilters.game_id"
                                                class="form-select form-select-sm bg-black border-secondary text-white">
                                                <option value="">Cualquier juego</option>
                                                <option v-for="g in myGames" :key="g.id" :value="g.id">{{ g.name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-3">
                                            <label class="text-muted small">Desde</label>
                                            <input type="date" v-model="browseFilters.start_date"
                                                class="form-control form-control-sm border-secondary text-white"
                                                style="color-scheme: dark; background-color: #1a1d20;">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="text-muted small">Hasta</label>
                                            <input type="date" v-model="browseFilters.end_date"
                                                class="form-control form-control-sm border-secondary text-white"
                                                style="color-scheme: dark; background-color: #1a1d20;">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-center mb-1">
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="availableOnly" v-model="browseFilters.available_only">
                                                <label class="form-check-label text-white small" for="availableOnly">Solo cupos libres</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-end d-flex justify-content-end gap-2">
                                            <button @click="clearFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Limpiar Filtros">
                                                <i class="fas fa-eraser me-1"></i>Limpiar
                                            </button>
                                            <button @click="applyFilters" class="btn btn-sm btn-info fw-bold rounded-pill px-3">
                                                <i class="fas fa-search me-1"></i>Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sesiones List -->
                        <div v-if="loading && browseSessions.length === 0" class="text-center py-5">
                            <div class="spinner-border text-info mb-2" role="status"></div>
                            <p class="text-muted">Buscando sesiones para unirte...</p>
                        </div>
                        <div v-else-if="Array.isArray(browseSessions) && browseSessions.length > 0" class="row g-3">
                            <div v-for="session in browseSessions" :key="session.id" class="col-md-6 col-xl-4">
                                <div class="card bg-dark border-secondary h-100 shadow-sm" style="border-radius: 12px; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                                    <div class="card-body d-flex flex-column p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <span v-if="session.is_recommended" class="badge bg-warning text-dark mb-1 shadow-sm rounded-pill"><i class="fas fa-star me-1"></i>Recomendado</span>
                                                <h6 class="card-title text-white fw-bold mb-0 text-truncate" style="max-width: 200px;" :title="session.title">{{ session.title }}</h6>
                                            </div>
                                            <span class="badge rounded-pill ms-2" :class="statusConfig[session.status]?.class || 'bg-secondary'">
                                                {{ statusConfig[session.status]?.label || session.status }}
                                            </span>
                                        </div>
                                        <p v-if="session.description" class="text-muted small mb-2 text-truncate" style="max-height: 40px; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ session.description }}</p>
                                        <ul class="list-unstyled small text-muted mb-3 mt-auto bg-black p-2 rounded">
                                            <li class="mb-1"><i class="fas fa-crown me-2 text-warning"></i><span class="text-white">{{ session.host?.name || 'Desconocido' }}</span></li>
                                            <li class="mb-1"><i class="fas fa-calendar-alt me-2 text-info"></i>{{ formatDate(session.start_time) }}</li>
                                            <li>
                                                <i class="fas fa-users me-2 text-primary"></i>
                                                {{ session.participants_count ?? session.participants?.length ?? 0 }} / {{ session.max_participants }}
                                                <span class="ms-1" :class="spotsLeft(session) > 0 ? 'text-success' : 'text-danger'">
                                                    ({{ spotsLeft(session) > 0 ? `${spotsLeft(session)} libres` : 'Llena' }})
                                                </span>
                                            </li>
                                        </ul>
                                        <button @click="handleJoin(session.id)" class="btn btn-outline-info w-100 btn-sm rounded-pill fw-bold mt-2" :disabled="spotsLeft(session) <= 0">
                                            <i class="fas fa-sign-in-alt me-2"></i>Unirme
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-5 bg-dark border border-secondary" style="border-radius: 12px;">
                            <i class="fas fa-satellite fa-4x text-muted mb-3"></i>
                            <h5 class="text-white">Ninguna sesión encontrada</h5>
                            <p class="text-muted">No hay sesiones disponibles en este momento o que coincidan con tus filtros.</p>
                            <button @click="openCreateModal" class="btn btn-primary rounded-pill px-4 mt-2">
                                <i class="fas fa-plus me-2"></i>¡Crea la tuya!
                            </button>
                        </div>
                    </div>
                </template>

                <template v-if="sessionTab === 'mis-sesiones'">
                    <!-- ===================== MIS SESIONES ===================== -->
                    <div class="col-12">
                        <div class="row g-4">
                            <!-- Hosting -->
                            <div class="col-12">
                                <h6 class="text-warning text-uppercase mb-3 fw-bold border-bottom border-secondary pb-2">
                                    <i class="fas fa-crown me-2"></i>Soy Anfitrión
                                </h6>
                                <div v-if="mySessions.hosting.length > 0" class="row g-3">
                                    <div v-for="s in mySessions.hosting" :key="s.id" class="col-md-6 col-xl-4">
                                        <div class="card bg-dark border-secondary h-100 shadow-sm" style="border-radius: 12px; border-left: 4px solid var(--bs-warning);">
                                            <div class="card-body p-3 d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title text-white fw-bold mb-0 text-truncate" :title="s.title">{{ s.title }}</h6>
                                                    <span class="badge rounded-pill" :class="statusConfig[s.status]?.class || 'bg-secondary'">{{ statusConfig[s.status]?.label || s.status }}</span>
                                                </div>
                                                <div class="bg-black p-2 rounded small text-muted mb-3">
                                                    <div class="mb-1"><i class="fas fa-calendar-alt me-2 text-info"></i>{{ formatDate(s.start_time) }}</div>
                                                    <div><i class="fas fa-users me-2 text-primary"></i>{{ s.participants?.length ?? 0 }} / {{ s.max_participants }} Participantes</div>
                                                </div>
                                                <div class="d-flex gap-2 mt-auto">
                                                    <button @click="openChat(s)" class="btn btn-sm btn-info flex-grow-1 rounded-pill"><i class="fas fa-comment-dots me-1"></i>Chat</button>
                                                    <button @click="openEditModal(s)" class="btn btn-sm btn-outline-light rounded-pill"><i class="fas fa-edit"></i></button>
                                                    <button @click="handleDelete(s.id)" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center p-4 bg-dark border border-secondary rounded text-muted">
                                    <p class="mb-0">No estás organizando ninguna sesión.</p>
                                </div>
                            </div>

                            <!-- Participating -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary text-uppercase mb-3 fw-bold border-bottom border-secondary pb-2">
                                    <i class="fas fa-gamepad me-2"></i>Estoy Participando
                                </h6>
                                <div v-if="onlyParticipating.length > 0" class="row g-3">
                                    <div v-for="s in onlyParticipating" :key="s.id" class="col-md-6 col-xl-4">
                                        <div class="card bg-dark border-secondary h-100 shadow-sm" style="border-radius: 12px; border-left: 4px solid var(--bs-primary);">
                                            <div class="card-body p-3 d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title text-white fw-bold mb-0 text-truncate" :title="s.title">{{ s.title }}</h6>
                                                    <span class="badge rounded-pill" :class="statusConfig[s.status]?.class || 'bg-secondary'">{{ statusConfig[s.status]?.label || s.status }}</span>
                                                </div>
                                                <div class="bg-black p-2 rounded small text-muted mb-3">
                                                    <div class="mb-1"><i class="fas fa-crown me-2 text-warning"></i>{{ s.host?.name || '-' }}</div>
                                                    <div><i class="fas fa-calendar-alt me-2 text-info"></i>{{ formatDate(s.start_time) }}</div>
                                                </div>
                                                <div class="d-flex gap-2 mt-auto">
                                                    <button @click="openChat(s)" class="btn btn-sm btn-info flex-grow-1 rounded-pill"><i class="fas fa-comment-dots me-1"></i>Chat</button>
                                                    <button @click="handleLeave(s.id, s)" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-sign-out-alt me-1"></i>Salir</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center p-4 bg-dark border border-secondary rounded text-muted">
                                    <p class="mb-0">No estás participando en ninguna sesión ajena.</p>
                                    <button class="btn btn-sm btn-outline-primary mt-2 rounded-pill" @click="sessionTab = 'explorar'">
                                        Ir a Explorar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

            <!-- ===================== COLECCIONES TAB ===================== -->
            <template v-else-if="activeTab === 'colecciones'">
                <div class="col-12">
                     <CollectionsPanel />
                </div>
            </template>
        </div>

        <!-- ===================== MODAL CREATE/EDIT ===================== -->
        <div v-if="showModal" class="modal-overlay d-flex align-items-center justify-content-center">
            <div class="modal-container card bg-dark border-secondary shadow-lg" style="max-width: 560px; width: 95%;">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-satellite-dish me-2 text-primary"></i>{{ modalTitle }}
                    </h5>
                    <button @click="closeModal" class="btn-close btn-close-white"></button>
                </div>
                <div class="card-body">
                    <div v-if="error" class="alert alert-danger small mb-3">{{ error }}</div>

                    <form @submit.prevent="handleSubmit">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-white">Título *</label>
                                <input type="text" v-model="sessionForm.title" required maxlength="255"
                                    class="form-control bg-black border-secondary text-white"
                                    :class="{ 'is-invalid': fieldErrors.title }"
                                    placeholder="Ej: Sesión de Halo Infinite con amigos">
                                <div v-if="fieldErrors.title" class="invalid-feedback">{{ fieldErrors.title[0] }}</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-white">Fecha de Inicio *</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" v-model="pickerDate" required @change="watchPicker"
                                            class="form-control bg-black border-secondary text-white"
                                            :class="{ 'is-invalid': fieldErrors.start_time }"
                                            style="color-scheme: dark;" :min="new Date().toISOString().split('T')[0]">
                                    </div>
                                    <div class="col-3">
                                        <select v-model="pickerHour" @change="watchPicker"
                                            class="form-select bg-black border-secondary text-white">
                                            <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}h</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <select v-model="pickerMinute" @change="watchPicker"
                                            class="form-select bg-black border-secondary text-white">
                                            <option v-for="m in minuteOptions" :key="m" :value="m">:{{ m }}</option>
                                        </select>
                                    </div>
                                    <div v-if="fieldErrors.start_time" class="col-12">
                                        <small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>{{
                                            fieldErrors.start_time[0] }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white">Juego <span
                                        class="text-muted fw-normal">(opcional)</span></label>
                                <select v-model="sessionForm.game_id"
                                    class="form-select bg-black border-secondary text-white">
                                    <option value="">Sin juego específico</option>
                                    <option v-for="g in myGames" :key="g.id" :value="g.id">{{ g.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white">Máx. Participantes *</label>
                                <div class="input-group-plain">
                                    <input type="number" v-model="sessionForm.max_participants" required min="2"
                                        max="100" class="form-control bg-black border-secondary text-white"
                                        :class="{ 'is-invalid': fieldErrors.max_participants }">
                                    <div v-if="fieldErrors.max_participants" class="invalid-feedback">{{
                                        fieldErrors.max_participants[0] }}</div>
                                    <p v-else class="text-muted small mt-1 mb-0">
                                        <i class="fas fa-info-circle me-1"></i>Incluye tu lugar como anfitrión
                                    </p>
                                </div>
                            </div>

                            <div v-if="editingSession" class="col-md-6">
                                <label class="form-label small fw-bold text-white">Estado</label>
                                <select v-model="sessionForm.status"
                                    class="form-select bg-black border-secondary text-white">
                                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{
                                        opt.label }}</option>
                                </select>
                            </div>

                            <div :class="editingSession ? 'col-md-6' : 'col-12'">
                                <label class="form-label small fw-bold text-white">Link de Sala</label>
                                <input type="url" v-model="sessionForm.link"
                                    class="form-control bg-black border-secondary text-white"
                                    placeholder="https://discord.gg/...">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-white">Descripción</label>
                                <textarea v-model="sessionForm.description" rows="3" maxlength="1000"
                                    class="form-control bg-black border-secondary text-white"
                                    placeholder="¿Qué van a jugar? ¿Hay requisitos?"></textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                <i v-if="loading" class="fas fa-spinner fa-spin me-2"></i>
                                {{ editingSession ? 'Guardar Cambios' : 'Crear Sesión' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===================== OFFCANVAS CHAT ===================== -->
        <div class="offcanvas offcanvas-end bg-dark border-start border-secondary text-white shadow-lg" tabindex="-1"
            :class="{ 'show': showChat }" :style="{ visibility: showChat ? 'visible' : 'hidden', zIndex: 1060 }"
            style="width: 400px; max-width: 100vw;">

            <div class="offcanvas-header border-bottom border-secondary bg-black">
                <h5 class="offcanvas-title fw-bold text-white mb-0">
                    <i class="fas fa-comment-dots me-2 text-info"></i>
                    Chat: {{ currentChatSession?.title }}
                </h5>
                <button type="button" class="btn-close btn-close-white" @click="closeChat"></button>
            </div>

            <div class="offcanvas-body d-flex flex-column p-0" style="background: var(--bg-card); overflow: hidden;">
                <!-- Messages Area -->
                <div class="flex-grow-1 p-3" style="overflow-y: auto;" ref="chatContainer" @scroll="handleChatScroll">
                    <div v-if="isChatLoadingMore" class="text-center py-2">
                        <span class="spinner-border spinner-border-sm text-info"></span>
                    </div>

                    <div v-if="chatMessages.length === 0 && !isChatLoadingMore"
                        class="text-center text-muted my-5 small">
                        <i class="fas fa-comment-slash fa-2x mb-2"></i>
                        <p>No hay mensajes en esta sesión. ¡Sé el primero en escribir!</p>
                    </div>

                    <template v-for="(group, gIndex) in groupedChatMessages" :key="'group-' + gIndex">
                        <div class="position-relative text-center my-4">
                            <hr class="border-secondary mb-0" style="opacity: 0.3;">
                            <span class="badge bg-dark border border-secondary text-muted position-absolute px-3 py-2"
                                style="top: 50%; left: 50%; transform: translate(-50%, -50%); text-transform: capitalize;">
                                {{ group.date }}
                            </span>
                        </div>

                        <div v-for="msg in group.messages" :key="msg.id" class="mb-3">
                            <div class="d-flex flex-column"
                                :class="msg.user?.id === authUser?.id ? 'align-items-end' : 'align-items-start'">
                                <span class="small text-muted mb-1" style="font-size: 0.75rem;">
                                    {{ msg.user?.name }} • {{ new
                                        Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                                    }}
                                </span>
                                <div class="p-2 rounded"
                                    :class="msg.user?.id === authUser?.id ? 'bg-primary text-black' : 'bg-black border border-secondary text-white'"
                                    style="max-width: 85%; word-break: break-word; font-size: 0.9rem;">
                                    {{ msg.message }}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Input Area -->
                <div class="p-3 border-top border-secondary bg-black">
                    <form @submit.prevent="submitMessage" class="input-group">
                        <input type="text" v-model="newMessage" class="form-control bg-dark text-white border-secondary"
                            placeholder="Escribe un mensaje..." required>
                        <button class="btn btn-info" type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Chat Backdrop -->
        <div v-if="showChat" class="modal-backdrop fade show" @click="closeChat" style="z-index: 1055;"></div>

    </AppLayout>
</template>
