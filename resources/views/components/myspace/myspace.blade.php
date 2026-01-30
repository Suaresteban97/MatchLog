<div id="myspace" class="container mt-5" v-cloak>
    <div v-if="loading" class="text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div v-else>
        <!-- Header -->
        <div class="mb-4">
            <h1 class="fw-bold">Bienvenido, <span class="text-primary">@{{ profile?.nickname || 'Gamer' }}</span></h1>
            <p class="text-muted">Resumen de tu actividad y hardware.</p>
        </div>

        <div class="row">
            <!-- Profile Summary -->
            <div class="col-md-4 mb-4">
                <div class="card bg-dark text-white border-secondary h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img :src="profile?.photo || '/assets/images/default-avatar.png'"
                                class="rounded-circle border border-primary" width="100" height="100"
                                style="object-fit: cover;">
                        </div>
                        <h4 class="card-title">@{{ profile?.nickname }}</h4>
                        <p class="card-text text-muted small">@{{ profile?.bio || 'Sin biografía' }}</p>
                        <div class="mt-3">
                            <span class="badge bg-secondary me-1">@{{ profile?.age ? profile.age + ' años' : '' }}</span>
                            <span class="badge bg-info text-dark">@{{ profile?.country || 'Global' }}</span>
                        </div>
                        <div class="mt-4 d-grid">
                            <a href="/profile" class="btn btn-outline-primary btn-sm">Editar Perfil</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Read-Only Devices List -->
            <div class="col-md-8">
                <div class="card bg-dark text-white border-secondary shadow-sm">
                    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-desktop me-2"></i>Mis Dispositivos</h5>
                        <a href="/devices" class="btn btn-sm btn-primary">Gestionar</a>
                    </div>
                    <div class="card-body">
                        <div v-if="devices.length === 0" class="text-center py-4 text-muted">
                            <i class="fas fa-gamepad fa-3x mb-3"></i>
                            <p>No tienes dispositivos registrados.</p>
                        </div>
                        <div v-else class="row">
                            <div class="col-md-6 mb-3" v-for="userDevice in devices" :key="userDevice.id">
                                <div class="card bg-secondary text-white border-0 h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-laptop fa-2x me-3 text-info"></i>
                                            <div>
                                                <h6 class="card-title mb-0">@{{ userDevice.custom_name || userDevice.device.name }}</h6>
                                                <small class="text-white-50">@{{ userDevice.device.name }}</small>
                                            </div>
                                        </div>
                                        <div v-if="userDevice.characteristics && userDevice.characteristics.length > 0"
                                            class="mt-2 pt-2 border-top border-dark">
                                            <small class="d-block text-white-50 mb-1">Specs:</small>
                                            <span v-for="char in userDevice.characteristics" :key="char.id"
                                                class="badge bg-dark me-1 mb-1">
                                                @{{ char.key }}: @{{ char.value || (char.pc_component ? char.pc_component.name : 'N/A') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
