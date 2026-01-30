<div id="social-dashboard" class="container mt-5">
    <div class="row">
        <!-- Main Feed -->
        <div class="col-md-8">
            <h2 class="mb-4 fw-bold text-uppercase" style="letter-spacing: 2px;">
                <i class="fas fa-globe-americas me-2 text-primary"></i>Actividad Global
            </h2>

            <!-- Post Composer Placeholder -->
            <div class="card bg-dark text-white border-secondary mb-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex">
                        <img src="{{ asset('assets/images/default-avatar.png') }}" class="rounded-circle border border-primary me-3"
                            width="50" height="50">
                        <textarea class="form-control bg-dark text-white border-secondary" rows="2"
                            placeholder="¿Qué estás jugando hoy?"></textarea>
                    </div>
                </div>
            </div>

            <!-- Placeholders for Posts -->
            <div class="card bg-dark text-white border-secondary mb-3">
                <div class="card-body">
                    <h5 class="card-title text-info">Usuario123 <small class="text-muted ms-2"
                            style="font-size: 0.8rem;">hace 2 min</small></h5>
                    <p class="card-text">¡Acabo de terminar Cyberpunk 2077! Qué final tan increíble.</p>
                </div>
            </div>

            <div class="card bg-dark text-white border-secondary mb-3">
                <div class="card-body">
                    <h5 class="card-title text-info">GamerPro <small class="text-muted ms-2"
                            style="font-size: 0.8rem;">hace 1 hora</small></h5>
                    <p class="card-text">Buscando equipo para jugar Valorant esta noche. ¿Alguien se apunta?</p>
                </div>
            </div>
        </div>

        <!-- Sidebar (Trends & Groups) -->
        <div class="col-md-4">

            <!-- Trends -->
            <div class="card bg-dark text-white border-secondary mb-4">
                <div class="card-header border-secondary bg-black">
                    <h5 class="mb-0 text-uppercase text-warning"><i class="fas fa-fire me-2"></i>Tendencias</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li
                        class="list-group-item bg-dark text-white border-secondary d-flex justify-content-between align-items-center">
                        #Cyberpunk2077
                        <span class="badge bg-primary rounded-pill">12k</span>
                    </li>
                    <li
                        class="list-group-item bg-dark text-white border-secondary d-flex justify-content-between align-items-center">
                        #EldenRingDLC
                        <span class="badge bg-primary rounded-pill">8.5k</span>
                    </li>
                    <li
                        class="list-group-item bg-dark text-white border-secondary d-flex justify-content-between align-items-center">
                        #IndieDev
                        <span class="badge bg-primary rounded-pill">5k</span>
                    </li>
                </ul>
            </div>

            <!-- Groups/Communities -->
            <div class="card bg-dark text-white border-secondary">
                <div class="card-header border-secondary bg-black">
                    <h5 class="mb-0 text-uppercase text-success"><i class="fas fa-users me-2"></i>Comunidades
                        Recomendadas</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#"
                        class="list-group-item list-group-item-action bg-dark text-white border-secondary">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-info">RPG Lovers</h6>
                        </div>
                        <small>Para los amantes de historias profundas.</small>
                    </a>
                    <a href="#"
                        class="list-group-item list-group-item-action bg-dark text-white border-secondary">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-info">PC Master Race</h6>
                        </div>
                        <small>Hardware, builds y más.</small>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
