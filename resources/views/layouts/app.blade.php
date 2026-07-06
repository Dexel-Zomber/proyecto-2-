<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>@yield('title', 'Vida Nueva') | Sistema de Alertas</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-body">
        @include('partials.navigation')

        <main class="app-main">
            <div class="app-container">
                @if (session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        <strong>Por favor, revisa los siguientes errores:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
        @stack('scripts')
    </body>
</html>
