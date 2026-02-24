<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useDevices } from '../../Composables/useDevices';
import { onMounted } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    module: Number,
    deviceId: {
        type: [Number, String],
        default: null
    }
});

const { 
    catalog, form, isCustomizable, 
    loadCatalog, loadDevice, addCharacteristic, removeCharacteristic, 
    saveDevice, loading, isSaving, error 
} = useDevices();

// Helpers para evaluar si un valor es un JSON estructurado de componentes
const isJsonStruct = (value) => {
    if (!value || typeof value !== 'string') return false;
    try {
        const parsed = JSON.parse(value);
        return parsed && typeof parsed === 'object' && parsed.components !== undefined;
    } catch (e) {
        return false;
    }
};

const getJsonStruct = (value) => {
    try {
        return JSON.parse(value);
    } catch (e) {
        return { components: {} };
    }
};

onMounted(async () => {
    await loadCatalog();
    if (props.deviceId) {
        await loadDevice(props.deviceId);
    }
});
</script>

<template>
    <Head :title="deviceId ? 'Editar Dispositivo' : 'Nuevo Dispositivo'" />

    <AppLayout v-cloak>
        <div class="row pt-4 justify-content-center">
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-gamepad me-2"></i>
                            {{ deviceId ? `Editar Dispositivo #${deviceId}` : 'Añadir Nuevo Dispositivo' }}
                        </span>
                        <Link href="/devices" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-arrow-left"></i> Volver
                        </Link>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveDevice(deviceId)">
                            
                            <div v-if="error" class="alert alert-danger mb-4" role="alert" style="background: rgba(220,53,69,0.2) !important; color: var(--neon-pink); border-color: var(--neon-pink);">
                                <span v-html="error"></span>
                            </div>

                            <div v-if="loading" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2 text-muted">Cargando datos...</p>
                            </div>
                            
                            <div v-else>
                                <!-- Nombre Personalizado -->
                                <div class="mb-3">
                                    <label class="form-label text-white fw-bold">Nombre Personalizado</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark text-white border-secondary"><i class="fas fa-font"></i></span>
                                        <input type="text" v-model="form.custom_name" class="form-control bg-dark text-white border-secondary" placeholder="Ej. PC Gamer, Rog Ally X..." required>
                                    </div>
                                </div>

                                <!-- Dispositivo Base -->
                                <div class="mb-4">
                                    <label class="form-label text-white fw-bold">Tipo de Dispositivo Base</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-dark text-white border-secondary"><i class="fas fa-laptop-code"></i></span>
                                        <select v-model="form.device_id" class="form-select bg-dark text-white border-secondary" required>
                                            <option value="" disabled>Selecciona un dispositivo base...</option>
                                            <option v-for="dev in catalog.devices" :key="dev.id" :value="dev.id">
                                                {{ dev.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Características Dinámicas -->
                                <div v-if="isCustomizable" class="mb-4 p-3 border border-secondary rounded bg-secondary bg-opacity-10">
                                    <h5 class="fw-bold text-white mb-3"><i class="fas fa-memory me-2 text-primary"></i>Características (Hardware)</h5>
                                    
                                    <div v-for="(char, index) in form.characteristics" :key="index" class="d-flex flex-column mb-3 p-2 border border-dark rounded bg-dark">
                                        <div class="d-flex gap-2 align-items-center mb-2">
                                            <select v-model="char.key" class="form-select bg-dark text-white border-secondary w-auto">
                                                <option value="" disabled>Componente</option>
                                                <option v-for="(name, key) in catalog.components" :key="key" :value="key">
                                                    {{ name }}
                                                </option>
                                            </select>
                                            
                                            <button type="button" @click="removeCharacteristic(index)" class="btn btn-outline-danger btn-sm ms-auto">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Renderizado visual si el valor es un JSON estructurado de componentes -->
                                        <div v-if="isJsonStruct(char.value)" class="mt-2 p-2 bg-black bg-opacity-50 rounded border border-secondary">
                                            <div class="row g-2">
                                                <template v-for="(items, category) in getJsonStruct(char.value).components" :key="category">
                                                    <div class="col-12 col-md-6" v-for="item in items" :key="item.id">
                                                        <div class="d-flex align-items-center p-2 rounded bg-dark border" style="border-color: var(--neon-cyan) !important;">
                                                            <div class="me-3 fs-4 text-cyan" style="color: var(--neon-cyan);">
                                                                <i v-if="category === 'cpu'" class="fas fa-microchip"></i>
                                                                <i v-else-if="category === 'gpu'" class="fas fa-video"></i>
                                                                <i v-else-if="category === 'ram'" class="fas fa-memory"></i>
                                                                <i v-else-if="category === 'storage'" class="fas fa-hdd"></i>
                                                                <i v-else class="fas fa-plug"></i>
                                                            </div>
                                                            <div>
                                                                <div class="text-white fw-bold small">{{ item.brand ? item.brand + ' ' : '' }}{{ item.name }}</div>
                                                                <div class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase;">{{ category }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        
                                        <!-- Fallback: Input de texto normal si no es JSON (o para editar a mano si se desea) -->
                                        <div v-else>
                                             <input type="text" v-model="char.value" placeholder="Valor (Ej. RTX 4090, 32GB RAM)" class="form-control bg-dark text-white border-secondary w-100">
                                        </div>
                                    </div>
                                    
                                    <button type="button" @click="addCharacteristic" class="btn btn-sm btn-outline-primary mt-2" style="border-color: var(--neon-cyan); color: var(--neon-cyan);">
                                        <i class="fas fa-plus"></i> Agregar Característica Manual
                                    </button>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg fw-bold tracking-widest text-uppercase" :disabled="isSaving" style="background-color: var(--neon-cyan); border-color: var(--neon-cyan); color: #000;">
                                        <i v-if="isSaving" class="fas fa-spinner fa-spin me-2"></i>
                                        <i v-else class="fas fa-save me-2"></i>
                                        {{ isSaving ? 'Guardando...' : 'Guardar Dispositivo' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
