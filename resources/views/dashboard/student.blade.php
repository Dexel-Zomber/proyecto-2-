@extends('layouts.app')

@section('title', 'Panel Estudiante')

@section('content')
<div class="card">
    <div class="hero">
        <div>
            <h1>Panel del estudiante</h1>
            <p>Consulta tus notas, promedio y recomendaciones académicas generadas por el sistema.</p>
        </div>
        <div>
            <div class="card" style="padding:16px; background:#eff7ff; box-shadow:none;">
                <strong>Promedio general</strong>
                <div style="font-size:2rem; margin-top:10px;">{{ number_format($average, 1) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <h2>Mis calificaciones</h2>
    @if($scores->isEmpty())
        <p>No tienes calificaciones registradas todavía.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Curso</th>
                    <th>Parcial</th>
                    <th>Calificación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scores as $score)
                    <tr>
                        <td>{{ $score->subject->name }}</td>
                        <td>{{ $score->subject->course->name }}</td>
                        <td>{{ $score->label }}</td>
                        <td>{{ number_format($score->value, 1) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h2>Alertas académicas</h2>

    @if($alerts->isEmpty())
        <p>No hay alertas activas por el momento.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Mensaje</th>
                    <th>Severidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alerts as $alert)
                    <tr>
                        <td>{{ $alert->subject?->name ?? 'General' }}</td>
                        <td>{{ $alert->message }}</td>
                        <td>
                            <span class="badge badge-{{ $alert->severity == 'critical' ? 'critical' : 'warning' }}">
                                {{ ucfirst($alert->severity) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h2>Recomendaciones</h2>
    @if(empty($recommendations))
        <p>Tu rendimiento es estable. Sigue adelante.</p>
    @else
        <ul>
            @foreach($recommendations as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
    @endif
</div>

<div class="card">
    <h2>Asistente académico (IA)</h2>
    <p style="color:#5f6c7b;">Pregunta sobre tus notas o alertas. Ej: "¿En qué materia debo enfocarme más?"</p>

    @if(session('aiAnswer'))
        <div class="card" style="padding:16px; background:#eff7ff; box-shadow:none; margin-bottom:16px;">
            <strong>Tú:</strong> {{ session('aiQuestion') }}
            <p style="margin-top:8px;"><strong>Asistente:</strong> {{ session('aiAnswer') }}</p>
        </div>
    @endif

    <form action="{{ route('student.ai-chat') }}" method="post">
        @csrf
        <label>Tu pregunta</label>
        <input type="text" name="question" class="input" maxlength="500" required value="{{ old('question') }}" />
        <button type="submit" class="button button-primary" style="margin-top:10px;">Preguntar</button>
    </form>
</div>
@endsection
