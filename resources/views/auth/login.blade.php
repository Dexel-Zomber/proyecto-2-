@extends('layouts.auth')

@section('title', 'Iniciar sesión')

@section('content')
<div class="login-container">
    <div class="login-wrapper">
        <!-- Left Panel - Branding -->
        <div class="login-brand-panel">
            <div class="login-brand-content">
                <div class="login-logo-container">
                    @if (file_exists(public_path('images/vida-nueva-logo.png')))
                        <img src="{{ asset('images/vida-nueva-logo.png') }}" alt="Logo Vida Nueva" class="login-logo" />
                    @else
                        <div class="login-logo-placeholder">VN</div>
                    @endif
                </div>
                <h1 class="login-brand-title">Vida Nueva</h1>
                <p class="login-brand-subtitle">Sistema Inteligente de Alertas Académicas</p>
                
                <div class="login-benefits">
                    <div class="benefit-item">
                        <div class="benefit-icon">📊</div>
                        <p>Análisis de desempeño académico</p>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">🚨</div>
                        <p>Alertas inteligentes en tiempo real</p>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">💡</div>
                        <p>Recomendaciones basadas en IA</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="login-form-panel">
            <div class="login-form-wrapper">
                <div class="login-form-header">
                    <h2>Bienvenido</h2>
                    <p>Inicia sesión con tus credenciales</p>
                </div>

                <form action="{{ route('login.submit') }}" method="post" class="login-form">
                    @csrf

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            class="input" 
                            placeholder="tu@correo.com" 
                            required 
                            autofocus 
                        />
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            class="input" 
                            placeholder="Ingresa tu contraseña" 
                            required 
                        />
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">Ingresar</button>

                    <div class="login-footer">
                        <p class="login-help-text">¿Necesitas ayuda? Contacta al administrador del colegio.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
