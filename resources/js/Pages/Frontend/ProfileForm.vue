<script setup>
import { Head } from '@inertiajs/vue3';
import { useProfile } from '../../Composables/useProfile';
import { useDevices } from '../../Composables/useDevices';
import { useProfilePage } from '../../Composables/useProfilePage';
import { onMounted } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    module: Number,
});

// --- Perfil ---
const {
    profile, isLoading, isSaving,
    error: profileError, successMessage,
    loadProfile, updateProfile
} = useProfile();

// --- Dispositivos ---
const deviceFns = useDevices();
const {
    catalog, form, isCustomizable,
    devices, loading: devLoading,
    loadDevices,
    deleteDevice,
    addCharacteristic, removeCharacteristic,
    saveDevice, isSaving: isDevSaving, error: devError,
    isJsonStruct, getJsonStruct
} = deviceFns;

// --- Navegación de tabs y sub-vistas ---
const { activeTab, deviceView, editingDeviceId, goToCreate, goToEdit, goToList } = useProfilePage(deviceFns);

const handleSaveDevice = async () => {
    await saveDevice(editingDeviceId.value);
    if (!devError.value) await goToList();
};

onMounted(async () => {
    await loadProfile();
    await Promise.all([loadDevices(), deviceFns.loadCatalog()]);
});
</script>

<template>
    <Head title="Perfil" />

    <AppLayout v-cloak>
        <div class="row pt-4 justify-content-center">
            <div class="col-12 col-md-10">

                <!-- Tabs nav -->
                <ul class="nav nav-tabs mb-3" style="border-bottom: 2px solid var(--neon-cyan);">
                    <li class="nav-item">
                        <button
                            class="nav-link profile-tab"
                            :class="{ 'tab-active': activeTab === 'profile' }"
                            @click="activeTab = 'profile'"
                        >
                            <i class="fas fa-cog me-2"></i>Configuración
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            class="nav-link profile-tab"
                            :class="{ 'tab-active': activeTab === 'devices' }"
                            @click="activeTab = 'devices'; deviceView = 'list';"
                        >
                            <i class="fas fa-gamepad me-2"></i>Mis Dispositivos
                        </button>
                    </li>
                </ul>

                <!-- ==================== TAB: PERFIL ==================== -->
                <div v-show="activeTab === 'profile'">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center text-white">
                            <span><i class="fas fa-cog me-2"></i>Configuración del Perfil</span>
                        </div>
                        <div class="card-body">
                            <div v-if="successMessage" class="alert alert-success border-success text-success bg-dark mb-4">
                                <i class="fas fa-check-circle me-2"></i>{{ successMessage }}
                            </div>
                            <div v-if="profileError" class="alert alert-danger mb-4" role="alert" style="background: rgba(220,53,69,0.2) !important; color: var(--neon-pink); border-color: var(--neon-pink);">
                                <i class="fas fa-exclamation-triangle me-2"></i>{{ profileError }}
                            </div>

                            <div v-if="isLoading" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p class="mt-2 text-muted">Cargando perfil...</p>
                            </div>

                            <form v-else @submit.prevent="updateProfile">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold text-white">Nombre</label>
                                        <input type="text" v-model="profile.first_name" class="form-control bg-dark text-white border-secondary" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold text-white">Apellidos</label>
                                        <input type="text" v-model="profile.last_name" class="form-control bg-dark text-white border-secondary" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold text-white">Nickname o Alias</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-dark text-white border-secondary">@</span>
                                            <input type="text" v-model="profile.nickname" class="form-control bg-dark text-white border-secondary" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold text-white">Edad</label>
                                        <input type="number" v-model="profile.age" class="form-control bg-dark text-white border-secondary">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-white">Biografía</label>
                                    <textarea v-model="profile.bio" class="form-control bg-dark text-white border-secondary" rows="4" placeholder="Cuéntanos sobre ti..."></textarea>
                                </div>

                                <div class="mb-4 p-3 border border-secondary rounded bg-secondary bg-opacity-10">
                                    <h6 class="fw-bold text-white mb-3">Preferencias de Privacidad</h6>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="shareEmail" v-model="profile.share_email">
                                        <label class="form-check-label text-white" for="shareEmail">Compartir Email Públicamente</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="lfgSwitch" v-model="profile.available_for_online">
                                        <label class="form-check-label text-white" for="lfgSwitch">Disponible para Jugar Online (LFG)</label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-5 fw-bold" :disabled="isSaving">
                                        <i v-if="isSaving" class="fas fa-spinner fa-spin me-2"></i>
                                        <i v-else class="fas fa-save me-2"></i>
                                        {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: DISPOSITIVOS ==================== -->
                <div v-show="activeTab === 'devices'">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">

                            <!-- Header: Lista -->
                            <template v-if="deviceView === 'list'">
                                <span><i class="fas fa-gamepad me-2"></i>Mis Dispositivos</span>
                                <button @click="goToCreate" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Añadir Dispositivo
                                </button>
                            </template>

                            <!-- Header: Crear / Editar -->
                            <template v-else>
                                <span>
                                    <i class="fas fa-gamepad me-2"></i>
                                    {{ deviceView === 'edit' ? `Editar Dispositivo #${editingDeviceId}` : 'Añadir Nuevo Dispositivo' }}
                                </span>
                                <button @click="goToList" class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </button>
                            </template>

                        </div>
                        <div class="card-body">

                            <!-- ---- LISTADO ---- -->
                            <div v-if="deviceView === 'list'">
                                <div v-if="devLoading" class="text-center py-4">
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
                                                        <button @click="goToEdit(dev.id)" class="btn btn-sm btn-primary me-2">
                                                            <i class="fas fa-edit"></i> Editar
                                                        </button>
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

                            <!-- ---- FORMULARIO CREAR / EDITAR ---- -->
                            <div v-else>
                                <div v-if="devError" class="alert alert-danger mb-4" role="alert" style="background: rgba(220,53,69,0.2) !important; color: var(--neon-pink); border-color: var(--neon-pink);">
                                    <span v-html="devError"></span>
                                </div>

                                <div v-if="devLoading" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2 text-muted">Cargando datos...</p>
                                </div>

                                <form v-else @submit.prevent="handleSaveDevice">
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
                                                <option v-for="dev in catalog.devices" :key="dev.id" :value="dev.id">{{ dev.name }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Características Dinámicas -->
                                    <div v-if="isCustomizable" class="mb-4 p-3 border border-secondary rounded bg-secondary bg-opacity-10">
                                        <h5 class="fw-bold text-white mb-3"><i class="fas fa-memory me-2 text-primary"></i>Características (Hardware)</h5>

                                        <div v-for="(char, index) in form.characteristics" :key="index" class="d-flex flex-column mb-3 p-2 border border-dark rounded bg-dark">
                                            <div class="d-flex gap-2 align-items-center mb-2">
                                                <!-- Primer Select: Tipo -->
                                                <select v-model="char.key" @change="char.pc_component_id = ''; char.value = ''" class="form-select bg-dark text-white border-secondary w-auto">
                                                    <option value="" disabled>Selecciona Tipo</option>
                                                    <option v-for="type in catalog.types" :key="type" :value="type">{{ type.toUpperCase() }}</option>
                                                </select>

                                                <!-- Segundo Select: Componente del catálogo -->
                                                <template v-if="char.key && catalog.components[char.key]">
                                                    <select
                                                        v-model="char.pc_component_id"
                                                        @change="(e) => {
                                                            if(e.target.value !== 'manual') {
                                                                const comp = catalog.components[char.key].find(c => c.id == e.target.value);
                                                                char.value = comp ? (comp.brand ? comp.brand + ' ' : '') + comp.name : '';
                                                            } else {
                                                                char.value = '';
                                                            }
                                                        }"
                                                        class="form-select bg-dark text-white border-secondary flex-grow-1"
                                                    >
                                                        <option value="" disabled>Selecciona {{ char.key.toUpperCase() }}</option>
                                                        <option v-for="comp in catalog.components[char.key]" :key="comp.id" :value="comp.id">
                                                            {{ comp.brand ? comp.brand + ' ' : '' }}{{ comp.name }}
                                                        </option>
                                                        <option value="manual">¿No ves tu componente? (Entrada Manual)</option>
                                                    </select>
                                                </template>

                                                <button type="button" @click="removeCharacteristic(index)" class="btn btn-outline-danger btn-sm ms-auto">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <!-- JSON Struct visual -->
                                            <div v-if="isJsonStruct(char.value)" class="mt-2 p-2 bg-black bg-opacity-50 rounded border border-secondary">
                                                <div class="row g-2">
                                                    <template v-for="(items, category) in getJsonStruct(char.value).components" :key="category">
                                                        <div class="col-12 col-md-6" v-for="item in items" :key="item.id">
                                                            <div class="d-flex align-items-center p-2 rounded bg-dark border" style="border-color: var(--neon-cyan) !important;">
                                                                <div class="me-3 fs-4" style="color: var(--neon-cyan);">
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

                                            <!-- Manual text input -->
                                            <div v-else-if="char.pc_component_id === 'manual' || !catalog.components[char.key] || (char.key && !catalog.components[char.key].length)">
                                                <input type="text" v-model="char.value" placeholder="Valor (Ej. RTX 4090, 32GB RAM)" class="form-control bg-dark text-white border-secondary w-100">
                                            </div>

                                            <!-- Info label catálogo -->
                                            <div v-else-if="char.pc_component_id" class="mt-2 p-2 rounded bg-dark border border-secondary border-opacity-50 small text-secondary d-flex align-items-center">
                                                <i class="fas fa-info-circle me-2"></i> {{ char.value }} (Desde catálogo)
                                            </div>
                                        </div>

                                        <button type="button" @click="addCharacteristic" class="btn btn-sm btn-outline-primary mt-2" style="border-color: var(--neon-cyan); color: var(--neon-cyan);">
                                            <i class="fas fa-plus"></i> Agregar Característica
                                        </button>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg fw-bold" :disabled="isDevSaving" style="background-color: var(--neon-cyan); border-color: var(--neon-cyan); color: #000;">
                                            <i v-if="isDevSaving" class="fas fa-spinner fa-spin me-2"></i>
                                            <i v-else class="fas fa-save me-2"></i>
                                            {{ isDevSaving ? 'Guardando...' : 'Guardar Dispositivo' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
