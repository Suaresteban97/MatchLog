import { ref } from 'vue';

const state = ref({
    isOpen: false,
    post: null, // Full post object
    postId: null, // Just the ID if we need to fetch
});

export function usePostModal() {
    const openPostModal = (postOrId) => {
        if (typeof postOrId === 'object' && postOrId !== null) {
            state.value.post = postOrId;
            state.value.postId = postOrId.id;
        } else {
            state.value.post = null;
            state.value.postId = postOrId;
        }
        state.value.isOpen = true;
    };

    const closePostModal = () => {
        state.value.isOpen = false;
        state.value.post = null;
        state.value.postId = null;
    };

    return {
        state,
        openPostModal,
        closePostModal
    };
}
