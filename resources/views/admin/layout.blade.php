<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EsSalud MiConsulta - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .essalud-blue { color: #1A3C8F; }
        .bg-essalud-blue { background-color: #1A3C8F; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-essalud-blue text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="font-bold text-xl">EsSalud Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-200">Dashboard</a>
                    <a href="{{ route('admin.pacientes') }}" class="hover:text-blue-200">Pacientes</a>
                    <a href="{{ route('admin.citas') }}" class="hover:text-blue-200">Citas</a>
                    <a href="{{ route('admin.recetas') }}" class="hover:text-blue-200">Recetas</a>
                    <a href="{{ route('admin.banners') }}" class="hover:text-blue-200">Banners</a>
                    <a href="{{ route('admin.configuraciones') }}" class="hover:text-blue-200">Configuración</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
