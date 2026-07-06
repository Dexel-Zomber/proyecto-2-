@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<div class="welcome-container">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-header">
                <span class="hero-badge">Tecnológico Universitario Vida Nueva</span>
                <h1 class="hero-title">Control académico inteligente con IA</h1>
                <p class="hero-description">Detecta estudiantes en riesgo, genera alertas automáticas y proporciona recomendaciones personalizadas para mejorar el desempeño académico.</p>
            </div>
            <div class="hero-actions">
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                    <span>🔐 Ingresar al sistema</span>
                </a>
                <a href="#features" class="btn btn-secondary btn-lg">
                    <span>↓ Conocer más</span>
                </a>
            </div>
        </div>

        <!-- Hero Visual -->
        <div class="hero-visual">
            <div class="hero-cards-stack">
                <div class="hero-card-item hero-card-1">
                    <div class="icon">📊</div>
                    <p>Análisis</p>
                </div>
                <div class="hero-card-item hero-card-2">
                    <div class="icon">🚨</div>
                    <p>Alertas</p>
                </div>
                <div class="hero-card-item hero-card-3">
                    <div class="icon">💡</div>
                    <p>Recomendaciones</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="features-header">
            <h2>Acceso según tu rol</h2>
            <p>Cada perfil tiene funcionalidades diseñadas para sus necesidades específicas</p>
        </div>

        <div class="features-grid">
            <!-- Admin Card -->
            <div class="feature-card">
                <div class="feature-icon admin-icon">👨‍💼</div>
                <h3>Administrador</h3>
                <p class="feature-description">Control total del sistema educativo</p>
                <ul class="feature-list">
                    <li>Gestión de usuarios (profesores y estudiantes)</li>
                    <li>Creación de cursos y materias</li>
                    <li>Configuración de parámetros de alerta</li>
                    <li>Reportes y estadísticas generales</li>
                </ul>
                <a href="{{ route('login') }}" class="feature-link">Acceder como Admin →</a>
            </div>

            <!-- Teacher Card -->
            <div class="feature-card">
                <div class="feature-icon teacher-icon">👨‍🏫</div>
                <h3>Profesor</h3>
                <p class="feature-description">Monitoreo del desempeño estudiantil</p>
                <ul class="feature-list">
                    <li>Registro y actualización de calificaciones</li>
                    <li>Visualización de alertas de estudiantes</li>
                    <li>Seguimiento de progreso académico</li>
                    <li>Recomendaciones automáticas por IA</li>
                </ul>
                <a href="{{ route('login') }}" class="feature-link">Acceder como Profesor →</a>
            </div>

            <!-- Student Card -->
            <div class="feature-card">
                <div class="feature-icon student-icon">👨‍🎓</div>
                <h3>Estudiante</h3>
                <p class="feature-description">Seguimiento de tu desempeño</p>
                <ul class="feature-list">
                    <li>Visualización de tus calificaciones</li>
                    <li>Cálculo automático de promedio</li>
                    <li>Recepción de alertas personalizadas</li>
                    <li>Consejos para mejorar tu desempeño</li>
                </ul>
                <a href="{{ route('login') }}" class="feature-link">Acceder como Estudiante →</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>¿Listo para comenzar?</h2>
            <p>Accede al sistema con tus credenciales para ver toda la información de tu institución.</p>
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Iniciar sesión</a>
        </div>
    </section>
</div>
@endsection
