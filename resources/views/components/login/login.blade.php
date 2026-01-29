<div id="login" v-cloak>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form @submit.prevent="login">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="email" v-model="email" placeholder="Correo Electrónico" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" v-model="password" placeholder="Contraseña" required>
            </div>
            
            <div v-if="error" class="alert alert-danger mt-3">
                @{{ error }}
            </div>

            <button type="submit" class="login-btn" :disabled="loading">
                @{{ loading ? 'Cargando...' : 'Ingresar' }}
            </button>
        </form>
    </div>
</div>
