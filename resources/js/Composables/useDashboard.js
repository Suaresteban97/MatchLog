import { ref } from 'vue';
import { useApi } from './useApi';

export function useDashboard() {
    const { get, post, error, loading } = useApi();
    
    const posts = ref([]);
    const postsMeta = ref({ current_page: 1, last_page: 1 });
    const loadingPosts = ref(false);
    
    const suggestions = ref([]);
    const suggestionsMeta = ref({ current_page: 1, last_page: 1 });
    const loadingSuggestions = ref(false);

    const recentGames = ref([]);
    const recentGamesMeta = ref({ current_page: 1, last_page: 1 });
    const loadingRecentGames = ref(false);

    const fetchPosts = async (page = 1) => {
        if (loadingPosts.value) return;
        loadingPosts.value = true;
        try {
            const data = await get(`/posts?page=${page}`);
            if (page === 1) {
                posts.value = data.data || [];
            } else {
                posts.value = [...posts.value, ...(data.data || [])];
            }
            postsMeta.value = {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1
            };
        } catch (e) {
            console.error('Error fetching posts:', e);
        } finally {
            loadingPosts.value = false;
        }
    };

    const fetchSuggestions = async (page = 1) => {
        if (loadingSuggestions.value) return;
        loadingSuggestions.value = true;
        try {
            const data = await get(`/dashboard/suggestions?page=${page}`);
            if (page === 1) {
                suggestions.value = data.data || [];
            } else {
                suggestions.value = [...suggestions.value, ...(data.data || [])];
            }
            suggestionsMeta.value = {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1
            };
        } catch (e) {
            console.error('Error fetching suggestions:', e);
        } finally {
            loadingSuggestions.value = false;
        }
    };

    const fetchRecentGames = async (page = 1) => {
        if (loadingRecentGames.value) return;
        loadingRecentGames.value = true;
        try {
            const data = await get(`/dashboard/recent-games?page=${page}`);
            if (page === 1) {
                recentGames.value = data.data || [];
            } else {
                recentGames.value = [...recentGames.value, ...(data.data || [])];
            }
            recentGamesMeta.value = {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1
            };
        } catch (e) {
            console.error('Error fetching recent games:', e);
        } finally {
            loadingRecentGames.value = false;
        }
    };

    const createPost = async (postData) => {
        try {
            const result = await post('/posts', postData);
            if (result && result.success) {
                // Prepend new post
                posts.value.unshift(result.data);
                return true;
            }
        } catch (e) {
            console.error('Error creating post:', e);
        }
        return false;
    };

    const joinSession = async (sessionId) => {
        try {
            const result = await post(`/sessions/${sessionId}/join`);
            return result;
        } catch (e) {
            console.error('Error joining session:', e);
            return null;
        }
    };

    return {
        posts,
        postsMeta,
        loadingPosts,
        suggestions,
        suggestionsMeta,
        loadingSuggestions,
        recentGames,
        recentGamesMeta,
        loadingRecentGames,
        fetchPosts,
        fetchSuggestions,
        fetchRecentGames,
        createPost,
        joinSession,
        error,
        loading
    };
}
