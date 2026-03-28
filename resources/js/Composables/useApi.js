import { ref } from 'vue';

const API_URL = '/api';

export function useApi() {
    const error = ref(null);
    const loading = ref(false);

    const getHeaders = (customHeaders = {}) => {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Admin-Authorization': window.ADMIN_TOKEN || '',
            ...customHeaders
        };
    };

    const handleResponse = async (response) => {
        const data = await response.json();

        if (!response.ok) {
            if (response.status === 401 || response.status === 403) {
                if (data.message === 'Token inválido o expirado' || data.message === 'Unauthenticated.') {
                    window.location.href = '/login';
                }
            }
            throw { status: response.status, ...data };
        }

        return data;
    };

    const request = async (method, endpoint, body = null, customHeaders = {}, silent = false) => {
        if (!silent) {
            loading.value = true;
            error.value = null;
        }

        try {
            const options = {
                method,
                headers: getHeaders(customHeaders),
                credentials: 'include'
            };

            if (body) {
                options.body = JSON.stringify(body);
            }

            const response = await fetch(`${API_URL}${endpoint}`, options);
            return await handleResponse(response);
        } catch (err) {
            if (!silent) {
                error.value = err.message || err.error || 'Error en la petición';
            }
            throw err;
        } finally {
            if (!silent) {
                loading.value = false;
            }
        }
    };

    const get = (endpoint, headers = {}, silent = false) => request('GET', endpoint, null, headers, silent);
    const post = (endpoint, body, headers = {}, silent = false) => request('POST', endpoint, body, headers, silent);
    const put = (endpoint, body, headers = {}, silent = false) => request('PUT', endpoint, body, headers, silent);
    const del = (endpoint, headers = {}, silent = false) => request('DELETE', endpoint, null, headers, silent);

    return {
        get,
        post,
        put,
        del,
        error,
        loading
    };
}
