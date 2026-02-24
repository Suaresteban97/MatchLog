<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useProfile } from '../../Composables/useProfile';
import { onMounted } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    module: Number,
});

const { 
    profile, isLoading, isSaving, 
    error, successMessage, 
    loadProfile, updateProfile 
} = useProfile();

// Las peticiones ya se ejecutan en el onMounted del hook composable, 
// no hace falta duplicar acá, pero importamos lo necesario.
</script>

<template>
    <Head title="Editar Perfil" />

    <AppLayout v-cloak>
        <div class="row pt-4 justify-content-center">
            <div class="col-12 col-md-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center text-white">
                        <span><i class="fas fa-cog me-2"></i>Configuración del Perfil</span>
                    </div>
                    
                    <div class="card-body">
                        <div v-if="successMessage" class="alert alert-success border-success text-success bg-dark mb-4">
                            <i class="fas fa-check-circle me-2"></i>{{ successMessage }}
                        </div>
                        <div v-if="error" class="alert alert-danger mb-4" role="alert" style="background: rgba(220,53,69,0.2) !important; color: var(--neon-pink); border-color: var(--neon-pink);">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
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
                                    <label class="form-check-label text-white" for="shareEmail">
                                        Compartir Email Públicamente
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="lfgSwitch" v-model="profile.available_for_online">
                                    <label class="form-check-label text-white" for="lfgSwitch">
                                        Disponible para Jugar Online (LFG)
                                    </label>
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
        </div>
    </AppLayout>
</template>
