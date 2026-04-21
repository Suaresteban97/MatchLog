<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useSessions } from '../../Composables/useSessions';
import { useGames } from '../../Composables/useGames';
import CollectionsPanel from './Components/Collections/CollectionsPanel.vue';

const activeTab = ref('sesiones'); // 'sesiones' or 'colecciones'

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
            <div class="col-12">
                 <ul class="nav nav-pills mb-4 border-bottom border-secondary pb-3 gap-2">
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
            </div>

            <!-- ===================== SESIONES TAB ===================== -->
            <template v-if="activeTab === 'sesiones'">
                <div class="col-12 d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 text-white">Sesiones de Juego</h5>
                    <button @click="openCreateModal" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nueva Sesión
                    </button>
                </div>

            <!-- ===================== BROWSE SESSIONS ===================== -->
            <div class="col-12">
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-body p-3">
                        <h6 class="text-white mb-3 fw-bold small"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda
                        </h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-9">
                                <label class="text-muted small">Búsqueda rápida</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-black border-secondary text-muted"><i
                                            class="fas fa-search"></i></span>
                                    <input type="text" v-model="browseFilters.search" @keyup.enter="applyFilters"
                                        class="form-control form-control-sm bg-black border-secondary text-white"
                                        placeholder="Buscar por título de sesión, anfitrión, nickname o correo...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small">Juego</label>
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
                                    class="form-control form-control-sm bg-black border-secondary text-white"
                                    style="color-scheme: dark;">
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted small">Hasta</label>
                                <input type="date" v-model="browseFilters.end_date"
                                    class="form-control form-control-sm bg-black border-secondary text-white"
                                    style="color-scheme: dark;">
                            </div>
                            <div class="col-md-3 d-flex align-items-center mb-1">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="availableOnly"
                                        v-model="browseFilters.available_only">
                                    <label class="form-check-label text-white small" for="availableOnly">Solo cupos
                                        libres</label>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <button @click="clearFilters" class="btn btn-sm btn-outline-secondary me-2"
                                    title="Limpiar Filtros">
                                    <i class="fas fa-eraser"></i>
                                </button>
                                <button @click="applyFilters" class="btn btn-sm btn-primary">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header text-white d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-globe me-2 text-primary"></i>Sesiones Disponibles
                        </div>
                        <span v-if="loading" class="spinner-border spinner-border-sm text-primary"></span>
                    </div>
                    <div class="card-body">
                        <div v-if="loading && browseSessions.length === 0" class="text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2 text-muted">Buscando sesiones...</p>
                        </div>

                        <div v-else-if="Array.isArray(browseSessions) && browseSessions.length > 0" class="row g-3">
                            <div v-for="session in browseSessions" :key="session.id" class="col-md-6 col-xl-4">
                                <div class="session-card card bg-dark border-secondary h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <span v-if="session.is_recommended"
                                                    class="badge bg-warning text-dark mb-1 d-inline-block shadow-sm">
                                                    <i class="fas fa-star me-1"></i>Recomendado
                                                </span>
                                                <h6 class="card-title text-white fw-bold mb-0">
                                                    {{ session.title }}
                                                </h6>
                                            </div>
                                            <span class="badge ms-2"
                                                :class="statusConfig[session.status]?.class || 'bg-secondary'">
                                                {{ statusConfig[session.status]?.label || session.status }}
                                            </span>
                                        </div>

                                        <p v-if="session.description" class="text-muted small mb-2">{{
                                            session.description }}</p>

                                        <ul class="list-unstyled small text-muted mb-3">
                                            <li><i class="fas fa-crown me-2 text-warning"></i>{{ session.host?.name ||
                                                'Desconocido' }}</li>
                                            <li><i class="fas fa-calendar me-2 text-primary"></i>{{
                                                formatDate(session.start_time) }}</li>
                                            <li>
                                                <i class="fas fa-users me-2 text-primary"></i>
                                                {{ session.participants_count ?? session.participants?.length ?? 0 }} /
                                                {{ session.max_participants }}
                                                <span class="ms-1"
                                                    :class="spotsLeft(session) > 0 ? 'text-success' : 'text-danger'">
                                                    ({{ spotsLeft(session) > 0 ? `${spotsLeft(session)} cupos libres` :
                                                        'Llena' }})
                                                </span>
                                            </li>
                                            <li v-if="session.link">
                                                <i class="fas fa-link me-2 text-primary"></i>
                                                <a :href="session.link" target="_blank"
                                                    class="text-primary text-truncate d-inline-block"
                                                    style="max-width: 160px;">{{ session.link }}</a>
                                            </li>
                                        </ul>

                                        <div class="mt-auto">
                                            <button @click="handleJoin(session.id)" class="btn btn-primary w-100 btn-sm"
                                                :disabled="spotsLeft(session) <= 0">
                                                <i class="fas fa-sign-in-alt me-2"></i>Unirme
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-5">
                            <i class="fas fa-satellite fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay sesiones disponibles en este momento.</p>
                            <button @click="openCreateModal" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Crea la primera
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================== MIS SESIONES ===================== -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-white">
                        <i class="fas fa-user me-2 text-primary"></i>Mis Sesiones
                    </div>
                    <div class="card-body">

                        <!-- Hosting -->
                        <h6 class="text-secondary text-uppercase mb-3 small fw-bold">
                            <i class="fas fa-crown me-2 text-warning"></i>Soy Anfitrión
                        </h6>
                        <div v-if="mySessions.hosting.length > 0" class="table-responsive mb-4">
                            <table class="table table-dark table-hover align-middle">
                                <thead class="text-secondary border-secondary">
                                    <tr>
                                        <th>Sesión</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Participantes</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="border-secondary">
                                    <tr v-for="s in mySessions.hosting" :key="s.id">
                                        <td>
                                            <span class="fw-bold text-white">{{ s.title }}</span>
                                            <p v-if="s.description" class="text-muted small mb-0">{{ s.description }}
                                            </p>
                                        </td>
                                        <td>
                                            <span class="badge"
                                                :class="statusConfig[s.status]?.class || 'bg-secondary'">
                                                {{ statusConfig[s.status]?.label || s.status }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ formatDate(s.start_time) }}</td>
                                        <td>
                                            <span class="text-white">{{ s.participants?.length ?? 0 }} / {{
                                                s.max_participants }}</span>
                                        </td>
                                        <td class="text-end">
                                            <button @click="openChat(s)" class="btn btn-sm btn-outline-info me-2"
                                                title="Chat de la Sesión">
                                                <i class="fas fa-comment-dots"></i>
                                            </button>
                                            <button @click="openEditModal(s)"
                                                class="btn btn-sm btn-outline-primary me-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button @click="handleDelete(s.id)" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-muted small mb-4">No estás organizando ninguna sesión.</p>

                        <hr class="border-secondary">

                        <!-- Participating -->
                        <h6 class="text-secondary text-uppercase mb-3 small fw-bold">
                            <i class="fas fa-gamepad me-2 text-primary"></i>Estoy Participando
                        </h6>
                        <div v-if="onlyParticipating.length > 0" class="table-responsive">
                            <table class="table table-dark table-hover align-middle">
                                <thead class="text-secondary border-secondary">
                                    <tr>
                                        <th>Sesión</th>
                                        <th>Anfitrión</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="border-secondary">
                                    <tr v-for="s in onlyParticipating" :key="s.id">
                                        <td>
                                            <span class="fw-bold text-white">{{ s.title }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ s.host?.name || '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge"
                                                :class="statusConfig[s.status]?.class || 'bg-secondary'">
                                                {{ statusConfig[s.status]?.label || s.status }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ formatDate(s.start_time) }}</td>
                                        <td class="text-end">
                                            <button @click="openChat(s)" class="btn btn-sm btn-outline-info me-2"
                                                title="Chat de la Sesión">
                                                <i class="fas fa-comment-dots"></i>
                                            </button>
                                            <button @click="handleLeave(s.id, s)" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-sign-out-alt me-1"></i>Abandonar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-muted small">No estás participando en ninguna sesión ajena.</p>
                    </div>
                </div>
            </div>
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
