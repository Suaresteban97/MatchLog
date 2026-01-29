
const API_URL = '/api';

/**
 * General API Wrapper
 */
export default class General {

    /**
     * Get Authorization Headers
     */
    static getHeaders(customHeaders = {}) {
        const token = localStorage.getItem('token');
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...customHeaders
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        return headers;
    }

    /**
     * Handle Response
     */
    static async handleResponse(response) {
        const data = await response.json();

        if (!response.ok) {
            if (response.status === 401 || response.status === 403) {
                // Handle unauthorized (optional: redirect to login)
                // localStorage.removeItem('token');
            }
            throw { status: response.status, ...data };
        }

        return data;
    }

    /**
     * GET Request
     */
    static async get(endpoint, customHeaders = {}) {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'GET',
                headers: this.getHeaders(customHeaders)
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    /**
     * POST Request
     */
    static async post(endpoint, body, customHeaders = {}) {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'POST',
                headers: this.getHeaders(customHeaders),
                body: JSON.stringify(body)
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    /**
     * PUT Request
     */
    static async put(endpoint, body, customHeaders = {}) {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'PUT',
                headers: this.getHeaders(customHeaders),
                body: JSON.stringify(body)
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }

    /**
     * DELETE Request
     */
    static async delete(endpoint, customHeaders = {}) {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'DELETE',
                headers: this.getHeaders(customHeaders)
            });
            return this.handleResponse(response);
        } catch (error) {
            throw error;
        }
    }
}
