<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>@yield('title', 'Vida Nueva') | Sistema de Alertas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-body" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 0;">
        @yield('content')
        @stack('scripts')
    </body>
</html>
