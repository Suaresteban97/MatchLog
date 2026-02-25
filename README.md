<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777bb4?style=for-the-badge&logo=php" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Laravel-11.x-ff2d20?style=for-the-badge&logo=laravel" alt="Laravel 11">
  <img src="https://img.shields.io/badge/Inertia.js-000000?style=for-the-badge&logo=inertiajs" alt="Inertia.js">
  <img src="https://img.shields.io/badge/Vue.js-42b883?style=for-the-badge&logo=vue.js" alt="Vue 3">
  <img src="https://img.shields.io/badge/Docker-2496ed?style=for-the-badge&logo=docker" alt="Docker">
  <img src="https://img.shields.io/badge/Redis-dc382d?style=for-the-badge&logo=redis" alt="Redis">
</p>

# 🎮 MatchLog: Gaming Backlog & Social Hub

**MatchLog** es una plataforma de alto rendimiento diseñada para la gestión de bibliotecas de videojuegos y el matchmaking social. El proyecto destaca por una arquitectura desacoplada y una infraestructura robusta, orientada a la escalabilidad y la baja latencia.

## Características

- **Gestión de Juegos**: Agrega, edita y elimina juegos de tu colección.
- **Estado de Juego**: Asigna un estado a cada juego (por ejemplo, "Jugando", "Completado", "Pendiente", etc.).
- **Fecha de Añadido**: Mantén un registro de la fecha en que agregaste un juego al backlog.
- **Filtros y Búsquedas**: Encuentra fácilmente cualquier juego en tu colección usando filtros y búsquedas avanzadas.
- **Historial de Juegos Completados**: Realiza un seguimiento de los juegos que ya has completado y celebra tus logros.
- **Interfaz amigable**: Diseño moderno y fácil de usar para una experiencia de usuario agradable.

---

## 🛠 Stack Tecnológico

### Core Frameworks
- **Backend:** Laravel 11 (PHP 8.3) utilizando tipado estricto y controladores optimizados.
- **Frontend (SPA):** Arquitectura de **Single Page Application** mediante **Inertia.js** y **Vue 3 (Composition API)**, permitiendo una experiencia de usuario fluida sin recargas de página.

### Infraestructura y Performance
- **Orquestación de Contenedores:** Sistema multi-contenedor gestionado con **Docker Compose**.
- **Manejo de Caché y Sesiones:** **Redis Alpine** configurado como driver principal para reducir el I/O en disco y acelerar la respuesta del servidor.
- **Optimización de Runtime:** Implementación de **Opcache** para la pre-compilación de bytecode PHP, minimizando el overhead de ejecución.

---

## 🏗️ Arquitectura de Microservicios (Docker)

La infraestructura se divide en servicios especializados para maximizar la eficiencia de recursos:

- **`app`**: Servicio PHP-FPM 8.3 encargado de la lógica de negocio.
- **`nginx`**: Servidor de alto desempeño para el balanceo y entrega de peticiones.
- **`node`**: Entorno de compilación reactiva con **Vite**.
- **`redis`**: Capa de persistencia en memoria para estados rápidos y colas.
- **`worker`**: Proceso asíncrono dedicado al manejo de **Queues**, garantizando que las tareas pesadas no afecten la latencia de la UI.
- **`db`**: Motor MySQL 8.0 con persistencia mediante volúmenes.



---

## 🏛️ Patrones de Diseño y Calidad

Para asegurar un código mantenible y de nivel profesional, se han aplicado:

- **Simplified DDD & Services:** Organización de la lógica en capas de servicios para evitar controladores saturados.
- **Repository Pattern:** Abstracción de la capa de persistencia para desacoplar el dominio de la base de datos.
- **Test-Driven Development (TDD):** Cobertura de funcionalidades críticas mediante **Pest/PHPUnit**.
- **Static Analysis:** Uso de **PHPStan** para garantizar la integridad de los tipos y prevenir errores lógicos.

---

## 🛡️ Seguridad y Resiliencia

- **Rate Limiting:** Control de flujo de peticiones para mitigar ataques de fuerza bruta.
- **Session Hardening:** Almacenamiento seguro de sesiones en Redis con flags de seguridad avanzados.
- **Branch Protection:** Implementación de reglas en la rama principal (`main`) para prevenir force-pushes y asegurar un flujo de integración mediante Pull Requests.

