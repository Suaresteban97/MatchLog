<script setup>
import { ref, watch, nextTick, computed } from 'vue';
import axios from 'axios';
import { useChat } from '../../../../Composables/useChat';

const { state, closeChat, isUserOnline } = useChat();

const messages = ref([]);
const newMessage = ref('');
const conversationId = ref(null);
const messagesContainer = ref(null);

const replyingTo = ref(null); // stores message object being replied to

// Listen for private channel
const subscribeToChat = (convId) => {
    if (!window.Echo || !convId) return;
    
    window.Echo.private(`chat.conversation.${convId}`)
        .listen('DirectMessageSent', (e) => {
            // ONLY accept messages from the friend via socket
            if (String(e.sender_id) !== String(state.activeFriend.id)) return;

            const exists = messages.value.some(m => String(m.id) === String(e.id));
            if (!exists) {
                messages.value.push(e);
                scrollToBottom();
                markAllAsRead();
            }
        });
};

const unsubscribeFromChat = (convId) => {
    if (!window.Echo || !convId) return;
    window.Echo.leave(`chat.conversation.${convId}`);
};

const loadMessages = async () => {
    if (!state.activeFriend) return;
    try {
        const { data } = await axios.get(`/api/chats/user/${state.activeFriend.id}`);
        messages.value = data.messages.data.reverse(); 
        
        if (conversationId.value !== data.conversation_id) {
            if(conversationId.value) unsubscribeFromChat(conversationId.value);
            conversationId.value = data.conversation_id;
            subscribeToChat(conversationId.value);
        }

        scrollToBottom();
        markAllAsRead();
    } catch (e) {
        console.error("Error loading chat", e);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.put(`/api/chats/user/${state.activeFriend.id}/read`);
    } catch(e) {}
};

let isSending = false; // Add guard flag

const sendMessage = async () => {
    if (!newMessage.value.trim() || !state.activeFriend || isSending) return;
    
    isSending = true;
    const payload = { 
        message: newMessage.value,
        reply_to_id: replyingTo.value ? replyingTo.value.id : null
    };

    const optimsticMsg = {
        id: 'temp-' + Date.now(),
        message: payload.message,
        sender_id: null, 
        created_at: new Date().toISOString(),
        reply_context: replyingTo.value
    };

    const tmpReplyTo = replyingTo.value;
    
    newMessage.value = '';
    replyingTo.value = null;
    messages.value.push(optimsticMsg);
    scrollToBottom();

    try {
        const { data } = await axios.post(`/api/chats/user/${state.activeFriend.id}`, payload);
        // Safely replace the temp message natively
        const idx = messages.value.findIndex(m => m.id === optimsticMsg.id);
        if (idx !== -1) {
            messages.value[idx] = data.data;
        }
    } catch (e) {
        console.error("Error sending", e);
        messages.value = messages.value.filter(m => m.id !== optimsticMsg.id);
        newMessage.value = payload.message;
        replyingTo.value = tmpReplyTo;
    } finally {
        isSending = false;
    }
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const setReply = (msg) => {
    replyingTo.value = {
        id: msg.id,
        message: msg.message,
        sender_id: msg.sender_id
    };
};

const cancelReply = () => {
    replyingTo.value = null;
};

// Date helpers
const isSameDay = (date1, date2) => {
    if (!date1 || !date2) return false;
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    return d1.getFullYear() === d2.getFullYear() &&
           d1.getMonth() === d2.getMonth() &&
           d1.getDate() === d2.getDate();
};

const formatDateHeader = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (isSameDay(date, today)) return 'Hoy';
    if (isSameDay(date, yesterday)) return 'Ayer';
    
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'long' });
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
};

// Whenever the active friend changes, load messages
watch(() => state.activeFriend, (newVal, oldVal) => {
    if (newVal && newVal.id !== oldVal?.id) {
        cancelReply();
        loadMessages();
    }
}, { immediate: true });

</script>

<template>
    <div v-if="state.isChatOpen && state.activeFriend" class="chat-widget shadow-lg border border-secondary">
        
        <!-- Header -->
        <div class="chat-header bg-dark d-flex align-items-center justify-content-between p-2 border-bottom border-secondary">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm position-relative">
                    {{ state.activeFriend.name[0].toUpperCase() }}
                     <div class="online-dot" :class="{'bg-success': isUserOnline(state.activeFriend.id).value, 'bg-secondary': !isUserOnline(state.activeFriend.id).value}"></div>
                </div>
                <div>
                    <div class="fw-bold text-white lh-1">{{ state.activeFriend.name }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">
                        {{ isUserOnline(state.activeFriend.id).value ? 'En línea' : 'Desconectado' }}
                    </small>
                </div>
            </div>
            <button class="btn btn-sm text-muted py-0 px-2" @click="closeChat">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages p-3" ref="messagesContainer">
            <div v-if="messages.length === 0" class="text-center text-muted small mt-5">
                Envía un saludo y comienza a charlar.
            </div>

            <template v-for="(msg, index) in messages" :key="msg.id">
                <!-- Date Separator -->
                <div v-if="index === 0 || !isSameDay(msg.created_at, messages[index - 1].created_at)" 
                     class="text-center w-100 my-2">
                    <span class="badge bg-black border border-secondary text-muted fw-normal px-2 py-1" style="font-size: 0.65rem; border-radius: 12px;">
                        {{ formatDateHeader(msg.created_at) }}
                    </span>
                </div>

                <div class="d-flex flex-column mb-3"
                     :class="(msg.sender_id === state.activeFriend.id) ? 'align-items-start' : 'align-items-end'">
                    
                    <!-- Reply Context -->
                    <div v-if="msg.reply_context || msg.replied_message" 
                         class="reply-bubble p-1 px-2 mb-1 rounded small border-start border-3"
                         :class="(msg.sender_id === state.activeFriend.id) ? 'border-secondary text-muted' : 'bg-primary bg-opacity-25 border-info text-light'"
                         :style="(msg.sender_id === state.activeFriend.id) ? 'background-color: #2b2b36; font-size:0.75rem; max-width:85%;' : 'font-size:0.75rem; max-width:85%;'">
                        <i class="fas fa-reply me-1" style="font-size:0.6rem"></i>
                        {{ (msg.reply_context || msg.replied_message).message }}
                    </div>

                    <!-- Main Bubble -->
                    <div class="position-relative chat-bubble p-2 px-3 rounded shadow-sm text-break"
                         :class="(msg.sender_id === state.activeFriend.id) ? 'text-light' : 'bg-primary text-white'"
                         :style="(msg.sender_id === state.activeFriend.id) ? 'background-color: #4a4a5a;' : ''">
                        
                        {{ msg.message }}
                        
                        <!-- Timestamp -->
                        <div class="text-end mt-1" style="font-size: 0.65rem; opacity: 0.7;">
                            {{ formatTime(msg.created_at) }}
                        </div>
                        
                        <!-- Hover Actions (Reply) -->
                        <div class="bubble-actions position-absolute top-50 translate-middle-y"
                             :class="(msg.sender_id === state.activeFriend.id) ? 'end-0' : 'start-0'"
                             :style="(msg.sender_id === state.activeFriend.id) ? 'margin-right: -2.5rem;' : 'margin-left: -2.5rem;'">
                            <button class="btn btn-sm text-secondary p-0" title="Responder" @click="setReply(msg)">
                                <i class="fas fa-reply"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input Area -->
        <div class="chat-input-area border-top border-secondary bg-dark position-relative p-2">
            
            <div v-if="replyingTo" class="replying-to-banner d-flex justify-content-between align-items-center p-2 mb-2 bg-black rounded">
                <div class="text-truncate small text-muted">
                    <i class="fas fa-reply me-1"></i> "{{ replyingTo.message }}"
                </div>
                <button class="btn btn-sm btn-link text-danger p-0 ms-2" @click="cancelReply"><i class="fas fa-times"></i></button>
            </div>

            <form @submit.prevent="sendMessage" class="d-flex">
                <input type="text" v-model="newMessage" class="form-control form-control-sm bg-black text-white border-secondary me-2 rounded-pill" placeholder="Escribe un mensaje...">
                <button type="submit" class="btn btn-sm btn-primary rounded-circle" :disabled="!newMessage.trim()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>

    </div>
</template>


