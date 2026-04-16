import { ref } from 'vue';
import { useApi } from './useApi';

/**
 * Composable for Community Contributions (Feature #1 – agents.md).
 *
 * All HTTP calls go through useApi exclusively (no direct axios / fetch).
 *
 * Usage:
 *   const { submitContribution, loadMyContributions, contributions, loading, error } =
 *     useContributions();
 */
export function useContributions() {
    const { get, post, error, loading } = useApi();

    /** Contributions returned by the last list call. */
    const contributions = ref([]);

    /**
     * Submit a community contribution (proposed field change).
     *
     * @param {'game'|'genre'|'platform'} type
     * @param {number} resourceId
     * @param {string} field        - e.g. 'description', 'name'
     * @param {string} proposedValue
     * @returns {Promise<Object>}   - API response
     */
    const submitContribution = async (type, resourceId, field, proposedValue) => {
        return await post('/contributions', {
            contributable_type: type,
            contributable_id:   resourceId,
            field,
            proposed_value: proposedValue,
        });
    };

    /**
     * Load contributions submitted by the authenticated user.
     *
     * @param {number} [perPage=15]
     */
    const loadMyContributions = async (perPage = 15) => {
        try {
            const response = await get(`/contributions?per_page=${perPage}`);
            contributions.value = response.data || [];
            return response;
        } catch (err) {
            console.error('Error loading contributions:', err);
        }
    };

    /**
     * Load contributions targeting a specific resource.
     *
     * @param {'game'|'genre'|'platform'} type
     * @param {number} resourceId
     * @param {'pending'|'approved'|'rejected'|'all'} [status='pending']
     * @param {number} [perPage=15]
     */
    const loadContributionsForResource = async (type, resourceId, status = 'pending', perPage = 15) => {
        try {
            const response = await get(
                `/contributions/resource/${type}/${resourceId}?status=${status}&per_page=${perPage}`
            );
            contributions.value = response.data || [];
            return response;
        } catch (err) {
            console.error('Error loading resource contributions:', err);
        }
    };

    return {
        contributions,
        loading,
        error,
        submitContribution,
        loadMyContributions,
        loadContributionsForResource,
    };
}
