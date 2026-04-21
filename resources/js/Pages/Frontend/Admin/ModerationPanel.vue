<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useApi } from '../../../Composables/useApi';

const { get, post } = useApi();

const contributions = ref({ data: [], current_page: 1, last_page: 1 });
const loading = ref(true);
const processingId = ref(null);

const fetchContributions = async (page = 1) => {
    loading.value = true;
    try {
        const response = await get(`/admin/contributions?page=${page}`);
        if (response.success) {
             contributions.value = response.data;
        }
    } catch (e) {
        console.error('Error fetching contributions:', e);
    } finally {
        loading.value = false;
    }
};

const resolveContribution = async (id, status) => {
    if (!confirm(`¿Estás seguro de ${status === 'approved' ? 'aprobar' : 'rechazar'} esta propuesta?`)) {
        return;
    }

    processingId.value = id;
    try {
        const response = await post(`/admin/contributions/${id}/resolve`, {
            status: status,
            reviewer_notes: ''
        });

        if (response.success) {
            // Remove from list or refresh
            await fetchContributions(contributions.value.current_page);
        }
    } catch (e) {
        console.error('Resolution failed:', e);
        alert(e.response?.data?.message || 'Error resolviendo la contribución');
    } finally {
        processingId.value = null;
    }
};

onMounted(() => {
    fetchContributions();
});
</script>

<template>
    <AppLayout>
        <div class="moderation-panel">
            <header class="d-flex justify-content-between align-items-center mb-4">
                <h2>Panel de Moderación</h2>
                <span class="badge bg-secondary">{{ contributions.total || 0 }} Pendientes</span>
            </header>

            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>

            <div v-else-if="contributions.data.length === 0" class="alert alert-info text-center">
                No hay contribuciones pendientes para revisar en este momento.
            </div>

            <div v-else class="table-responsive bg-dark rounded shadow-sm border border-secondary">
                <table class="table table-dark table-hover mb-0 moderation-table">
                    <thead>
                        <tr>
                            <th>Recurso</th>
                            <th>Campo</th>
                            <th style="width: 25%">Valor Actual</th>
                            <th style="width: 25%">Valor Propuesto</th>
                            <th>Usuario</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in contributions.data" :key="item.id">
                            <td>
                                <div>
                                    <span class="badge bg-info text-dark mb-1 d-inline-block">
                                        {{ item.contributable_type.split('\\').pop() }}
                                    </span>
                                </div>
                                <strong class="text-light">{{ item.contributable?.name || 'Recurso Desconocido' }}</strong>
                            </td>
                            <td>
                                <code>{{ item.field }}</code>
                            </td>
                            <td>
                                <div class="val-box val-current">
                                    {{ item.current_value || '(Vacío)' }}
                                </div>
                            </td>
                            <td>
                                <div class="val-box val-proposed">
                                    {{ item.proposed_value }}
                                </div>
                            </td>
                            <td>
                                <div>{{ item.user?.name }}</div>
                                <small class="text-muted">{{ new Date(item.created_at).toLocaleDateString() }}</small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button 
                                        @click="resolveContribution(item.id, 'approved')"
                                        :disabled="processingId === item.id"
                                        class="btn btn-sm btn-success"
                                        title="Aprobar y aplicar cambio">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button 
                                        @click="resolveContribution(item.id, 'rejected')"
                                        :disabled="processingId === item.id"
                                        class="btn btn-sm btn-danger"
                                        title="Rechazar cambio">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 d-flex justify-content-center" v-if="contributions.last_page > 1">
                <button 
                    class="btn btn-outline-secondary me-2" 
                    :disabled="contributions.current_page === 1"
                    @click="fetchContributions(contributions.current_page - 1)">
                    Anterior
                </button>
                <span class="align-self-center mx-2">Página {{ contributions.current_page }} de {{ contributions.last_page }}</span>
                <button 
                    class="btn btn-outline-secondary ms-2" 
                    :disabled="contributions.current_page === contributions.last_page"
                    @click="fetchContributions(contributions.current_page + 1)">
                    Siguiente
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.moderation-table th, .moderation-table td {
    vertical-align: middle;
}
.val-box {
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    max-height: 80px;
    overflow-y: auto;
    word-break: break-word;
}
.val-current {
    background-color: rgba(255, 255, 255, 0.05);
    border: 1px dashed #6c757d;
    color: #adb5bd;
}
.val-proposed {
    background-color: rgba(40, 167, 69, 0.1);
    border: 1px solid #28a745;
    color: #e9ecef;
}
</style>
