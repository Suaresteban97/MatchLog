<div id="login">
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form method="POST" action="/api/login">
            @csrf 
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Usuario" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="login-btn">Ingresar</button>
        </form>
    </div>
</div>
