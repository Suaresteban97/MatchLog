<div id="profile-form" class="container mt-5" v-cloak>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-white border-secondary shadow-lg">
                <div class="card-header border-secondary">
                    <h3 class="mb-0"><i class="fas fa-user-edit me-2 text-primary"></i>Editar Perfil</h3>
                </div>
                <div class="card-body">

                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>

                    <div v-else>

                        <div v-if="message" class="alert alert-success alert-dismissible fade show" role="alert">
                            @{{ message }}
                            <button type="button" class="btn-close" @click="message = ''"></button>
                        </div>

                        <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
                            @{{ error }}
                            <button type="button" class="btn-close" @click="error = ''"></button>
                        </div>

                        <form @submit.prevent="updateProfile">

                            <!-- Avatar Preview -->
                            <div class="text-center mb-4">
                                <img :src="profile.photo || 'assets/images/default-avatar.png'"
                                    class="rounded-circle border border-primary p-1" width="120" height="120"
                                    style="object-fit: cover;">
                                <div class="mt-2">
                                    <label class="form-label small text-muted">URL de tu Avatar</label>
                                    <input type="text"
                                        class="form-control form-control-sm bg-dark text-white border-secondary"
                                        v-model="profile.photo" placeholder="https://...">
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- First Name -->
                                <div class="col-md-6">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary"
                                        v-model="profile.first_name">
                                </div>

                                <!-- Last Name -->
                                <div class="col-md-6">
                                    <label class="form-label">Apellido</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary"
                                        v-model="profile.last_name">
                                </div>

                                <!-- Nickname -->
                                <div class="col-md-6">
                                    <label class="form-label">Nickname <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary"
                                        v-model="profile.nickname" required>
                                </div>

                                <!-- Age -->
                                <div class="col-md-2">
                                    <label class="form-label">Edad</label>
                                    <input type="number" class="form-control bg-dark text-white border-secondary"
                                        v-model="profile.age">
                                </div>

                                <!-- Genre -->
                                <div class="col-md-4">
                                    <label class="form-label">Género</label>
                                    <select class="form-select bg-dark text-white border-secondary"
                                        v-model="profile.genre">
                                        <option value="">Seleccionar...</option>
                                        <option value="Male">Masculino</option>
                                        <option value="Female">Femenino</option>
                                        <option value="Non-binary">No binario</option>
                                        <option value="Other">Otro</option>
                                        <option value="Cyberware">Cyborg / Robot</option>
                                    </select>
                                </div>

                                <!-- Bio -->
                                <div class="col-12">
                                    <label class="form-label">Biografía</label>
                                    <textarea class="form-control bg-dark text-white border-secondary" rows="3" v-model="profile.bio"
                                        placeholder="Cuéntanos sobre ti..."></textarea>
                                </div>

                                <!-- Settings -->
                                <div class="col-12 mt-4">
                                    <h5 class="border-bottom border-secondary pb-2 mb-3">Preferencias</h5>

                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="shareEmail"
                                            v-model="profile.share_email">
                                        <label class="form-check-label" for="shareEmail">Compartir email
                                            públicamente</label>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="availableOnline"
                                            v-model="profile.available_for_online">
                                        <label class="form-check-label" for="availableOnline">Disponible para juego
                                            online</label>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary">
                                <a href="/my-space" class="btn btn-outline-light">
                                    <i class="fas fa-arrow-left me-1"></i> Volver
                                </a>
                                <button type="submit" class="btn btn-primary px-4" :disabled="saving">
                                    <span v-if="saving" class="spinner-border spinner-border-sm me-1" role="status"
                                        aria-hidden="true"></span>
                                    <span v-if="saving">Guardando...</span>
                                    <span v-else><i class="fas fa-save me-1"></i> Guardar Cambios</span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
