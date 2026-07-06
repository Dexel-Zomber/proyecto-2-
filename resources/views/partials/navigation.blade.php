@php
    $navUser = null;
    if (session()->has('user_id')) {
        $navUser = App\Models\User::find(session('user_id'));
    }
@endphp

<header class="site-header">
    <div class="site-brand">
        <a href="/" class="brand-logo">
            @if (file_exists(public_path('images/vida-nueva-logo.png')))
                <img src="{{ asset('images/vida-nueva-logo.png') }}" alt="Logo Vida Nueva" class="brand-image" />
                <span>Vida Nueva</span>
            @else
                Vida Nueva
            @endif
        </a>
        <span class="brand-tag">Alerta académica inteligente</span>
    </div>

    <button class="nav-toggle" type="button" data-nav-toggle aria-label="Abrir menú">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav class="site-nav" data-nav-menu>
        <a href="/" class="nav-link">Inicio</a>

        @if ($navUser)
            <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>

            @if ($navUser->isAdmin())
                <a href="{{ route('admin') }}" class="nav-link">Administrador</a>
            @elseif ($navUser->isTeacher())
                <a href="{{ route('teacher') }}" class="nav-link">Profesor</a>
            @elseif ($navUser->isStudent())
                <a href="{{ route('student') }}" class="nav-link">Estudiante</a>
            @endif

            <span class="nav-user">
                {{ $navUser->name }}
                <small>({{ ucfirst($navUser->role) }})</small>
            </span>

            <form action="{{ route('logout') }}" method="post" class="nav-form">
                @csrf
                <button type="submit" class="btn btn-danger">Cerrar sesión</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary nav-link">Iniciar sesión</a>
        @endif
    </nav>
</header>
