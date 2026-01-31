<div id="device-form" class="container mt-5" v-cloak>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card bg-dark text-white border-secondary shadow">
                <div class="card-header border-secondary">
                    <h3 class="fw-bold mb-0">@{{ form.device_id ? 'Editar Dispositivo' : 'Agregar Nuevo Dispositivo' }}</h3>
                </div>
                <div class="card-body">
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>

                    <form v-else @submit.prevent="save">
                        <!-- Device Type Selection -->
                        <div class="mb-4">
                            <label class="form-label">Tipo de Dispositivo</label>
                            <select class="form-select bg-secondary text-white border-0" v-model="form.device_id" required>
                                <option value="" disabled>Selecciona una consola o plataforma</option>
                                <option v-for="dev in catalog.devices" :key="dev.id" :value="dev.id">
                                    @{{ dev.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Custom Name -->
                        <div class="mb-4">
                            <label class="form-label">Nombre Personalizado (Opcional)</label>
                            <input type="text" class="form-control bg-secondary text-white border-0" v-model="form.custom_name" placeholder="Ej: Mi PC Main, PS5 de la Sala">
                        </div>

                        <!-- Characteristics -->
                        <div class="mb-4" v-if="isCustomizable">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Especificaciones / Componentes</label>
                                <button type="button" class="btn btn-sm btn-outline-info" @click="addCharacteristic">
                                    <i class="fas fa-plus"></i> Agregar Info
                                </button>
                            </div>
                            
                            <div v-if="form.characteristics.length === 0" class="text-muted small fst-italic mb-3">
                                No has agregado especificaciones (CPU, RAM, Color, etc.)
                            </div>

                            <div v-for="(char, index) in form.characteristics" :key="index" class="card bg-secondary bg-opacity-25 border-secondary mb-2">
                                <div class="card-body p-2 d-flex gap-2 align-items-center">
                                    <div class="flex-grow-1">
                                        <select class="form-select form-select-sm bg-dark text-white border-secondary mb-1" v-model="char.key" required>
                                            <option value="" disabled>Tipo (Key)</option>
                                            <option value="cpu">Procesador (CPU)</option>
                                            <option value="gpu">Gráfica (GPU)</option>
                                            <option value="ram">Memoria RAM</option>
                                            <option value="storage">Almacenamiento</option>
                                            <option value="color">Color</option>
                                            <option value="other">Otro</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Component Select (if CPU/GPU/RAM/Storage) -->
                                    <div class="flex-grow-1" v-if="['cpu','gpu','ram','storage'].includes(char.key)">
                                        <select class="form-select form-select-sm bg-dark text-white border-secondary" v-model="char.pc_component_id">
                                            <option value="">Selecciona Componente (Opcional)</option>
                                            <optgroup v-for="(comps, type) in catalog.components" :key="type" :label="type.toUpperCase()" v-if="char.key === type">
                                                <option v-for="c in comps" :key="c.id" :value="c.id">
                                                    @{{ c.name }} @{{ c.brand ? '('+c.brand+')' : '' }}
                                                </option>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <!-- Manual Value Input (always visible as alternative or for other types) -->
                                    <div class="flex-grow-1" v-if="!char.pc_component_id">
                                        <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" v-model="char.value" placeholder="Valor manual (Ej: Rojo, 120GB)">
                                    </div>

                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeCharacteristic(index)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div v-if="error" class="alert alert-danger" v-html="error"></div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/devices" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Guardar Dispositivo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
