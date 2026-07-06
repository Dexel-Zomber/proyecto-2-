@extends('layouts.app')

@section('title', 'Colegio Inteligente')

@section('content')
<div class="card">
    <div class="hero">
        <div>
            <h1>Sistema de alertas académicas con IA</h1>
            <p>Bienvenido al prototipo del colegio. Inicia sesión como administrador, profesor o estudiante para acceder a tu panel.</p>
            <a href="/login" class="button button-primary">Iniciar sesión</a>
        </div>
        <div style="max-width:380px;">
            <p><strong>Roles disponibles:</strong></p>
            <ul>
                <li>Administrador: gestiona usuarios, cursos, materias y IA.</li>
                <li>Profesor: registra y modifica notas, ve alertas de sus estudiantes.</li>
                <li>Estudiante: revisa sus notas, promedio y recomendaciones.</li>
            </ul>
        </div>
    </div>
</div>
<div class="grid grid-3">
    <div class="card">
        <h2>Administrador</h2>
        <p>Control total del colegio, creación de profesores, estudiantes, materias, cursos y parámetros de IA.</p>
    </div>
    <div class="card">
        <h2>Profesor</h2>
        <p>Registra notas, administra sus materias y consulta alertas de sus estudiantes.</p>
    </div>
    <div class="card">
        <h2>Estudiante</h2>
        <p>Consulta notas, promedio, alertas académicas y recomendaciones IA.</p>
    </div>
</div>
@endsection
