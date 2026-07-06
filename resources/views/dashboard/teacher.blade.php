@extends('layouts.app')

@section('title', 'Panel Profesor')

@section('content')
@php
    $unresolvedCount = $alerts->where('resolved', false)->count();
    $totalScores = $subjects->sum(fn ($s) => $s->scores->count());
    $totalPending = $pendingBySubject->flatten()->count();
@endphp

<div class="dashboard-shell">
    <aside class="dashboard-sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">{{ strtoupper(substr($user->name ?? 'P', 0, 1)) }}</div>
            <div>
                <h1>Panel del profesor</h1>
                <p>{{ $user->name ?? '' }}</p>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Navegación del panel">
            <button type="button" class="sidebar-link active" data-tab="summary">
                <span class="icon">📊</span>
                <span>Resumen</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="grade">
                <span class="icon">📝</span>
                <span>Registrar nota</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="subjects">
                <span class="icon">📚</span>
                <span>Mis materias</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="alerts">
                <span class="icon">🚨</span>
                <span>Alertas</span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <div>
                <span class="muted">Última actualización</span>
                <strong>{{ now()->format('d M Y H:i') }}</strong>
            </div>
        </div>
    </aside>

    <section class="dashboard-content">
        <header class="dashboard-header">
            <div>
                <p class="eyebrow">Panel del profesor</p>
                <h2>Bienvenido, {{ $user->name ?? '' }}</h2>
                <p class="hero-copy">Revisa tus materias asignadas, registra notas y consulta alertas de tus estudiantes.</p>
            </div>
        </header>

        <main class="dashboard-panel">
            {{-- ===================== RESUMEN ===================== --}}
            <section class="tab-panel active" data-tab="summary">
                <div class="kpi-grid">
                    <article class="kpi-card">
                        <span class="kpi-label">Materias</span>
                        <strong>{{ $subjects->count() }}</strong>
                        <p>Materias asignadas a ti.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Estudiantes</span>
                        <strong>{{ $students->count() }}</strong>
                        <p>Inscritos en tus materias.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Notas registradas</span>
                        <strong>{{ $totalScores }}</strong>
                        <p>Calificaciones ingresadas en total.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Alertas activas</span>
                        <strong>{{ $unresolvedCount }}</strong>
                        <p>Casos que requieren seguimiento.</p>
                    </article>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div>
                                <p class="eyebrow">Pendientes</p>
                                <h3>Estudiantes sin calificar</h3>
                            </div>
                        </div>
                        <div class="activity-list">
                            @forelse($subjects as $subject)
                                @php $pending = $pendingBySubject[$subject->id] ?? collect(); @endphp
                                @if($pending->isNotEmpty())
                                    <div class="activity-item">
                                        <div>
                                            <span class="activity-title">{{ $subject->name }}</span>
                                            <span class="activity-meta">{{ $pending->pluck('name')->implode(', ') }}</span>
                                        </div>
                                        <span class="status-chip status-warning">{{ $pending->count() }}</span>
                                    </div>
                                @endif
                            @empty
                            @endforelse

                            @if($totalPending === 0)
                                <p class="muted">Todos tus estudiantes ya tienen al menos una nota registrada.</p>
                            @endif
                        </div>
                    </div>

                    <div class="summary-card stretch-card">
                        <div class="summary-card-header">
                            <div>
                                <p class="eyebrow">Actividad reciente</p>
                                <h3>Últimas alertas</h3>
                            </div>
                        </div>
                        <div class="activity-list">
                            @forelse($alerts->take(6) as $alert)
                                <div class="activity-item">
                                    <div>
                                        <span class="activity-title">{{ $alert->student?->name ?? 'Estudiante desconocido' }}</span>
                                        <span class="activity-meta">{{ $alert->subject?->name ?? 'Materia' }} · {{ optional($alert->updated_at)->format('d M H:i') }}</span>
                                    </div>
                                    <span class="status-chip status-{{ $alert->resolved ? 'success' : ($alert->severity === 'critical' ? 'critical' : 'warning') }}">
                                        {{ $alert->resolved ? 'Resuelta' : ucfirst($alert->severity) }}
                                    </span>
                                </div>
                            @empty
                                <p class="muted">No hay alertas registradas para tus estudiantes por ahora.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== REGISTRAR NOTA ===================== --}}
            <section class="tab-panel" data-tab="grade">
                <div class="module-card">
                    <div class="module-header">
                        <div>
                            <h3>Registrar nota</h3>
                            <p>Si ya existe una nota con el mismo parcial para ese estudiante, se actualizará en vez de duplicarse.</p>
                        </div>
                    </div>
                    <form action="{{ route('teacher.scores.store') }}" method="post" class="stacked-form">
                        @csrf
                        <label>Materia</label>
                        <select name="subject_id" id="subject-select" class="select" required onchange="filterStudents()">
                            <option value="">Selecciona una materia</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }} - {{ $subject->course->name }}</option>
                            @endforeach
                        </select>

                        <label>Estudiante</label>
                        <select name="student_id" id="student-select" class="select" required>
                            <option value="">Selecciona primero una materia</option>
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

                        <button type="submit" class="btn btn-primary btn-block">Guardar nota</button>
                    </form>
                </div>
            </section>

            {{-- ===================== MIS MATERIAS ===================== --}}
            <section class="tab-panel" data-tab="subjects">
                @forelse($subjects as $subject)
                    @php
                        $pending = $pendingBySubject[$subject->id] ?? collect();
                        $averagesByStudent = $subject->scores->groupBy('student_id')->map(fn ($scores) => $scores->avg('value'));
                    @endphp
                    <div class="table-panel" style="margin-bottom: 24px;">
                        <div class="table-panel-header" style="padding: 20px 20px 0;">
                            <div>
                                <h3>{{ $subject->name }} <small class="muted">({{ $subject->course->name }})</small></h3>
                                <p class="muted" style="margin: 4px 0 0;">
                                    {{ $subject->students->count() }} estudiante(s) inscrito(s)
                                    @if($pending->count() > 0)
                                        · {{ $pending->count() }} sin ninguna nota registrada
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($pending->isNotEmpty())
                            <div style="padding: 0 20px 16px;">
                                <span class="status-chip status-warning">Pendientes: {{ $pending->pluck('name')->implode(', ') }}</span>
                            </div>
                        @endif

                        @if($subject->scores->isEmpty())
                            <p class="muted" style="padding: 0 20px 20px;">No hay notas registradas aún para esta materia.</p>
                        @else
                            <div class="table-scroll">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Parcial</th>
                                            <th>Calificación</th>
                                            <th>Promedio</th>
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
                                                <td>{{ number_format($averagesByStudent[$score->student_id] ?? $score->value, 1) }}</td>
                                                <td>
                                                    @if($score->value < 60)
                                                        <span class="status-chip status-critical">Crítico</span>
                                                    @elseif($score->value < 70)
                                                        <span class="status-chip status-warning">Bajo</span>
                                                    @else
                                                        <span class="status-chip status-success">Aceptable</span>
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
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="module-card">
                        <p class="muted">Todavía no tienes materias asignadas.</p>
                    </div>
                @endforelse
            </section>

            {{-- ===================== ALERTAS ===================== --}}
            <section class="tab-panel" data-tab="alerts">
                <div class="table-panel">
                    <div class="table-panel-header" style="padding: 20px 20px 0;">
                        <h3>Alertas de mis estudiantes</h3>
                        <span class="status-chip {{ $unresolvedCount > 0 ? 'status-critical' : 'status-success' }}">
                            {{ $unresolvedCount > 0 ? $unresolvedCount.' sin resolver' : 'Todo al día' }}
                        </span>
                    </div>

                    @if($alerts->isEmpty())
                        <p class="muted" style="padding: 0 20px 20px;">No hay alertas registradas para tus estudiantes por ahora.</p>
                    @else
                        <div class="table-scroll">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Materia</th>
                                        <th>Mensaje</th>
                                        <th>Severidad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($alerts as $alert)
                                        <tr>
                                            <td>{{ $alert->student->name ?? 'N/D' }}</td>
                                            <td>{{ $alert->subject->name ?? 'N/D' }}</td>
                                            <td>{{ $alert->message }}</td>
                                            <td>
                                                <span class="status-chip status-{{ $alert->severity === 'critical' ? 'critical' : 'warning' }}">
                                                    {{ $alert->severity === 'critical' ? 'Crítico' : 'Advertencia' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-chip status-{{ $alert->resolved ? 'success' : 'warning' }}">
                                                    {{ $alert->resolved ? 'Resuelta' : 'Pendiente' }}
                                                </span>
                                            </td>
                                            <td class="table-actions">
                                                @if($alert->resolved)
                                                    <form action="{{ route('teacher.alerts.unresolve', $alert) }}" method="post" class="inline-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-secondary btn-small">Reabrir</button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('teacher.alerts.resolve', $alert) }}" method="post" class="inline-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-primary btn-small">Marcar resuelta</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </section>
</div>

<script>
    // Mapa materia -> lista de estudiantes inscritos, generado desde el backend,
    // para filtrar el segundo select sin recargar la página.
    const subjectStudents = {
        @foreach($subjects as $subject)
            {{ $subject->id }}: [
                @foreach($subject->students as $student)
                    { id: {{ $student->id }}, name: @json($student->name) },
                @endforeach
            ],
        @endforeach
    };

    function filterStudents() {
        const subjectId = document.getElementById('subject-select').value;
        const studentSelect = document.getElementById('student-select');
        studentSelect.innerHTML = '';

        if (!subjectId) {
            studentSelect.innerHTML = '<option value="">Selecciona primero una materia</option>';
            return;
        }

        const list = subjectStudents[subjectId] || [];

        if (list.length === 0) {
            studentSelect.innerHTML = '<option value="">No hay estudiantes inscritos en esta materia</option>';
            return;
        }

        studentSelect.innerHTML = '<option value="">Selecciona un estudiante</option>';
        list.forEach(function (student) {
            const opt = document.createElement('option');
            opt.value = student.id;
            opt.textContent = student.name;
            studentSelect.appendChild(opt);
        });
    }
</script>

@push('scripts')
<script>
(() => {
    const navItems = document.querySelectorAll('.dashboard-sidebar .sidebar-link');
    const panels = document.querySelectorAll('.dashboard-panel .tab-panel');

    function showTab(tab) {
        navItems.forEach(item => item.classList.toggle('active', item.dataset.tab === tab));
        panels.forEach(panel => panel.classList.toggle('active', panel.dataset.tab === tab));
    }

    navItems.forEach(item => item.addEventListener('click', () => showTab(item.dataset.tab)));
})();
</script>
@endpush
@endsection
