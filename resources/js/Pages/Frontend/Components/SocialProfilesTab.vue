<script setup>
import { ref, onMounted } from 'vue';
import { useSocial } from '../../../Composables/useSocial';

const socialFns = useSocial();
const {
    platforms, form, profiles, loading, isSaving, error,
    loadPlatforms, loadProfiles, loadProfile,
    saveProfile, deleteProfile
} = socialFns;

const socialView = ref('list');
const editingProfileId = ref(null);

const goToCreate = () => {
    form.social_platform_id = '';
    form.gamertag = '';
    form.external_user_id = '';
    form.profile_url = '';
    editingProfileId.value = null;
    socialView.value = 'create';
};

const goToEdit = async (id) => {
    await loadProfile(id);
    editingProfileId.value = id;
    socialView.value = 'edit';
};

const goToList = async () => {
    socialView.value = 'list';
    await loadProfiles();
};

const handleSaveProfile = async () => {
    await saveProfile(editingProfileId.value);
    if (!error.value) {
        await goToList();
    }
};

onMounted(async () => {
    await Promise.all([loadProfiles(), loadPlatforms()]);
});
</script>

<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">

            <!-- Header: Lista -->
            <template v-if="socialView === 'list'">
                <span><i class="fas fa-users me-2"></i>Mis Perfiles Sociales</span>
                <button @click="goToCreate" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Añadir Perfil
                </button>
            </template>

            <!-- Header: Crear / Editar -->
            <template v-else>
                <span>
                    <i class="fas fa-user-circle me-2"></i>
                    {{ socialView === 'edit' ? `Editar Perfil Social` : 'Añadir Nuevo Perfil' }}
                </span>
                <button @click="goToList" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </template>

        </div>
        <div class="card-body">

            <!-- ---- LISTADO ---- -->
            <div v-if="socialView === 'list'">
                <div v-if="loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2 text-muted">Cargando perfiles...</p>
                </div>
                <div v-else>
                    <div class="table-responsive" v-if="profiles.length > 0">
                        <table class="table table-dark table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Plataforma</th>
                                    <th>Gamertag</th>
                                    <th>URL del Perfil</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="prof in profiles" :key="prof.id">
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i v-if="prof.social_platform?.icon_class"
                                                :class="prof.social_platform.icon_class + ' me-1'"></i>
                                            {{ prof.social_platform?.name || 'Desconocida' }}
                                        </span>
                                    </td>
                                    <td>{{ prof.gamertag }}</td>
                                    <td>
                                        <a v-if="prof.profile_url" :href="prof.profile_url" target="_blank"
                                            class="text-info text-decoration-none">
                                            <i class="fas fa-external-link-alt"></i> Ver Perfil
                                        </a>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-end">
                                        <button @click="goToEdit(prof.id)" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button @click="deleteProfile(prof.id)" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-muted text-center py-4">No tienes perfiles sociales registrados todavía.</p>
                </div>
            </div>

            <!-- ---- FORMULARIO CREAR / EDITAR ---- -->
            <div v-else>
                <div v-if="error" class="alert alert-danger mb-4" role="alert"
                    style="background: rgba(220,53,69,0.2) !important; color: var(--neon-pink); border-color: var(--neon-pink);">
                    <span v-html="error"></span>
                </div>

                <div v-if="loading && socialView === 'edit'" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2 text-muted">Cargando datos...</p>
                </div>

                <form v-else @submit.prevent="handleSaveProfile">

                    <!-- Plataforma Social -->
                    <div class="mb-4">
                        <label class="form-label text-white fw-bold">Plataforma</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white border-secondary"><i
                                    class="fas fa-globe"></i></span>
                            <select v-model="form.social_platform_id"
                                class="form-select bg-dark text-white border-secondary" required>
                                <option value="" disabled>Selecciona una plataforma...</option>
                                <option v-for="plat in platforms" :key="plat.id" :value="plat.id">{{ plat.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Gamertag -->
                    <div class="mb-3">
                        <label class="form-label text-white fw-bold">Gamertag o Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white border-secondary"><i
                                    class="fas fa-gamepad"></i></span>
                            <input type="text" v-model="form.gamertag"
                                class="form-control bg-dark text-white border-secondary" placeholder="Ej. PlayerOne123"
                                required>
                        </div>
                    </div>

                    <!-- External User ID -->
                    <div class="mb-3">
                        <label class="form-label text-white fw-bold">ID Externo <small
                                class="text-muted fw-normal">(Opcional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white border-secondary"><i
                                    class="fas fa-id-badge"></i></span>
                            <input type="text" v-model="form.external_user_id"
                                class="form-control bg-dark text-white border-secondary" placeholder="Ej. 1234567890">
                        </div>
                    </div>

                    <!-- URL del Perfil -->
                    <div class="mb-4">
                        <label class="form-label text-white fw-bold">URL del Perfil <small
                                class="text-muted fw-normal">(Opcional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white border-secondary"><i
                                    class="fas fa-link"></i></span>
                            <input type="url" v-model="form.profile_url"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="https://steamcommunity.com/id/PlayerOne123">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold" :disabled="isSaving"
                            style="background-color: var(--neon-cyan); border-color: var(--neon-cyan); color: #000;">
                            <i v-if="isSaving" class="fas fa-spinner fa-spin me-2"></i>
                            <i v-else class="fas fa-save me-2"></i>
                            {{ isSaving ? 'Guardando...' : 'Guardar Perfil Social' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</template>
