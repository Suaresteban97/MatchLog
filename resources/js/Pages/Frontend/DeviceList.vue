<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useDevices } from '../../Composables/useDevices';
import { onMounted } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    module: Number,
});

const { devices, loading, loadDevices, deleteDevice } = useDevices();

onMounted(() => {
    loadDevices();
});
</script>

<template>
    <Head title="Mis Dispositivos" />

    <AppLayout v-cloak>
        <div class="row pt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-gamepad me-2"></i>Mis Dispositivos</span>
                        <Link href="/devices/create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Añadir Dispositivo
                        </Link>
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2 text-muted">Cargando dispositivos...</p>
                        </div>
                        <div v-else>
                            <div class="table-responsive" v-if="devices.length > 0">
                                <table class="table table-dark table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nombre Personalizado</th>
                                            <th>Tipo Base</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="dev in devices" :key="dev.id">
                                            <td>{{ dev.custom_name }}</td>
                                            <td><span class="badge bg-secondary">{{ dev.device?.name || 'Otro' }}</span></td>
                                            <td class="text-end">
                                                <Link :href="`/devices/${dev.id}/edit`" class="btn btn-sm btn-primary me-2">
                                                    <i class="fas fa-edit"></i> Editar
                                                </Link>
                                                <button @click="deleteDevice(dev.id)" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-muted text-center py-4">No tienes dispositivos registrados todavía.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
