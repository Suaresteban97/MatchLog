<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/index.css') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ysabeau+Infant:wght@100;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://unpkg.com/vue@3.4.15/dist/vue.global.js"></script>

    <title>Backlog Project</title>
    <script>
        window.ADMIN_TOKEN = "{{ env('ADMIN_TOKEN') }}";
    </script>
</head>

<body>

    <nav id="main-navbar" class="navbar navbar-expand-lg navbar-dark bg-dark mb-4" style="display: none;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/dashboard">MY BACKLOG</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/my-space"><i class="fas fa-user-astronaut me-1"></i>Mi Espacio</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <button onclick="logout()" class="btn btn-outline-danger btn-sm">Cerrar Sesión</button>
                </div>
            </div>
        </div>
    </nav>
    <script>
        // Check for token to show navbar
        if (localStorage.getItem('token')) {
            document.getElementById('main-navbar').style.display = 'block';
        }

        async function logout() {
            try {
                // Call API logout (optional, but good practice)
                // We need to fetch with headers. 
                // Since General.js is a module, we can't easily use it here without module type script.
                // We'll just do simple cleanup and redirect.

                const token = localStorage.getItem('token');
                if (token) {
                    fetch('/api/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    }).catch(console.error);
                }

                localStorage.removeItem('token');
                window.location.href = '/login';
            } catch (e) {
                console.error(e);
                window.location.href = '/login';
            }
        }
    </script>

</body>

</html>
