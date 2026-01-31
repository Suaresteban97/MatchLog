<div id="device-list" class="container mt-5" v-cloak>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold"><i class="fas fa-microchip me-2"></i>Gestión de Dispositivos</h1>
        <a href="/devices/create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nuevo Dispositivo</a>
    </div>

    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div v-else>
        <div v-if="devices.length === 0" class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>No tienes dispositivos registrados. ¡Agrega el primero!
        </div>

        <div v-else class="row">
            <div class="col-md-6 col-lg-4 mb-4" v-for="userDevice in devices" :key="userDevice.id">
                <div class="card bg-dark text-white border-secondary h-100 shadow-sm">
                    <div class="card-header border-secondary d-flex justify-content-between align-items-center bg-secondary bg-opacity-25">
                         <h5 class="card-title mb-0 text-truncate">@{{ userDevice.custom_name || userDevice.device.name }}</h5>
                         <span class="badge bg-primary">@{{ userDevice.device.name }}</span>
                    </div>
                    <div class="card-body">
                         <div v-if="userDevice.characteristics && userDevice.characteristics.length > 0">
                            <small class="text-muted d-block mb-2">Especificaciones:</small>
                            <ul class="list-unstyled small mb-0">
                                <li v-for="char in userDevice.characteristics" :key="char.id" class="mb-1">
                                    <i class="fas fa-caret-right me-1 text-primary"></i>
                                    <strong>@{{ char.key }}:</strong> 
                                    @{{ char.value || (char.pc_component ? char.pc_component.name : 'N/A') }}
                                </li>
                            </ul>
                        </div>
                        <div v-else class="text-center py-3 text-muted">
                            <small>Sin especificaciones detalladas.</small>
                        </div>
                    </div>
                    <div class="card-footer border-secondary d-flex justify-content-end gap-2 bg-transparent">
                        <a :href="'/devices/' + userDevice.id + '/edit'" class="btn btn-sm btn-outline-warning" title="Editar"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-outline-danger" @click="deleteDevice(userDevice.id)" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
