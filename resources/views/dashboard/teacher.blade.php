@extends('layouts.app')

@section('title', 'Panel Profesor')

@section('content')
<div class="card">
    <div class="hero">
        <div>
            <h1>Panel del profesor</h1>
            <p>Revisa tus materias asignadas, registra notas y consulta alertas de tus estudiantes.</p>
        </div>
    </div>
</div>

<div class="card">
    <h2>Registrar nota</h2>
    <form action="/teacher/scores" method="post">
        @csrf
        <label>Materia</label>
        <select name="subject_id" class="select" required>
            <option value="">Selecciona una materia</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }} - {{ $subject->course->name }}</option>
            @endforeach
        </select>

        <label>Estudiante</label>
        <select name="student_id" class="select" required>
            <option value="">Selecciona un estudiante</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->name }}</option>
            @endforeach
        </select>

        <label>Parcial</label>
        <select name="label" class="select">
            <option value="Parcial 1">Parcial 1</option>
            <option value="Parcial 2">Parcial 2</option>
            <option value="Quimestral">Quimestral</option>
            <option value="General">General</option>
        </select>

        <label>Calificación</label>
        <input type="number" name="value" class="input" min="0" max="100" step="1" required />

        <button type="submit" class="button button-primary">Guardar nota</button>
        <p style="color:#5f6c7b; font-size:.85rem; margin-top:6px;">Si ya existe una nota con el mismo parcial para ese estudiante, se actualizará en vez de duplicarse.</p>
    </form>
</div>

<div class="card">
    <h2>Mis materias</h2>
    @foreach($subjects as $subject)
        <div style="margin-bottom:24px;">
            <h3>{{ $subject->name }} <small style="color:#5f6c7b;">({{ $subject->course->name }})</small></h3>
            @if($subject->scores->isEmpty())
                <p>No hay notas registradas aún para esta materia.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Parcial</th>
                            <th>Calificación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subject->scores as $score)
                            <tr>
                                <td>{{ $score->student->name }}</td>
                                <td>{{ $score->label }}</td>
                                <td>{{ number_format($score->value, 1) }}</td>
                                <td>
                                    @if($score->value < 60)
                                        <span class="badge badge-critical">Crítico</span>
                                    @elseif($score->value < 70)
                                        <span class="badge badge-warning">Bajo</span>
                                    @else
                                        <span class="badge badge-success">Aceptable</span>
                                    @endif
                                </td>
                                <td class="table-actions">
                                    <form action="{{ route('teacher.scores.delete', $score) }}" method="post" class="inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
</div>
@endsection
