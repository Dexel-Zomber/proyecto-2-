@extends('layouts.app')

@section('title', 'Panel Estudiante')

@section('content')
@php
    $unresolvedCount = $alerts->count();
    $scoresBySubject = $scores->groupBy('subject_id');
    $criticalCount = $scores->where('value', '<', 60)->count();
@endphp

<div class="dashboard-shell">
    <aside class="dashboard-sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">{{ strtoupper(substr($user->name ?? 'E', 0, 1)) }}</div>
            <div>
                <h1>Panel del estudiante</h1>
                <p>{{ $user->name ?? '' }}</p>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Navegación del panel">
            <button type="button" class="sidebar-link active" data-tab="summary">
                <span class="icon">📊</span>
                <span>Resumen</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="grades">
                <span class="icon">📝</span>
                <span>Mis calificaciones</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="alerts">
                <span class="icon">🚨</span>
                <span>Alertas</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="ai">
                <span class="icon">🤖</span>
                <span>Asistente IA</span>
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
                <p class="eyebrow">Panel del estudiante</p>
                <h2>Hola, {{ $user->name ?? '' }}</h2>
                <p class="hero-copy">Consulta tus notas, tu promedio y recomendaciones generadas por el sistema.</p>
            </div>
        </header>

        <main class="dashboard-panel">
            {{-- ===================== RESUMEN ===================== --}}
            <section class="tab-panel active" data-tab="summary">
                <div class="kpi-grid">
                    <article class="kpi-card">
                        <span class="kpi-label">Promedio general</span>
                        <strong>{{ number_format($average, 1) }}</strong>
                        <p>Sobre todas tus materias.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Materias</span>
                        <strong>{{ $scoresBySubject->count() }}</strong>
                        <p>Con al menos una nota registrada.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Notas críticas</span>
                        <strong>{{ $criticalCount }}</strong>
                        <p>Calificaciones por debajo de 60.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Alertas activas</span>
                        <strong>{{ $unresolvedCount }}</strong>
                        <p>Casos que requieren tu atención.</p>
                    </article>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div>
                                <p class="eyebrow">Por materia</p>
                                <h3>Tu promedio en cada una</h3>
                            </div>
                        </div>
                        <div class="activity-list">
                            @forelse($scoresBySubject as $subjectId => $subjectScores)
                                @php
                                    $subjectName = $subjectScores->first()->subject->name ?? 'Materia';
                                    $subjectAvg = $subjectScores->avg('value');
                                @endphp
                                <div class="activity-item">
                                    <div>
                                        <span class="activity-title">{{ $subjectName }}</span>
                                        <span class="activity-meta">{{ $subjectScores->count() }} nota(s) registrada(s)</span>
                                    </div>
                                    <span class="status-chip status-{{ $subjectAvg < 60 ? 'critical' : ($subjectAvg < 70 ? 'warning' : 'success') }}">
                                        {{ number_format($subjectAvg, 1) }}
                                    </span>
                                </div>
                            @empty
                                <p class="muted">Todavía no tienes calificaciones registradas.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="summary-card stretch-card">
                        <div class="summary-card-header">
                            <div>
                                <p class="eyebrow">Sistema de alertas</p>
                                <h3>Recomendaciones</h3>
                            </div>
                        </div>
                        <div class="activity-list">
                            @forelse($recommendations as $recommendation)
                                <div class="activity-item">
                                    <div>
                                        <span class="activity-title">{{ $recommendation }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="muted">Tu rendimiento es estable. Sigue adelante.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===================== MIS CALIFICACIONES ===================== --}}
            <section class="tab-panel" data-tab="grades">
                <div class="table-panel">
                    <div class="table-panel-header" style="padding: 20px 20px 0;">
                        <h3>Mis calificaciones</h3>
                        <span class="status-chip {{ $average >= 70 ? 'status-success' : ($average >= 60 ? 'status-warning' : 'status-critical') }}">
                            Promedio: {{ number_format($average, 1) }}
                        </span>
                    </div>

                    @if($scores->isEmpty())
                        <p class="muted" style="padding: 0 20px 20px;">No tienes calificaciones registradas todavía.</p>
                    @else
                        <div class="table-scroll">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <th>Curso</th>
                                        <th>Parcial</th>
                                        <th>Calificación</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($scores as $score)
                                        <tr>
                                            <td>{{ $score->subject->name }}</td>
                                            <td>{{ $score->subject->course->name }}</td>
                                            <td>{{ $score->label }}</td>
                                            <td>{{ number_format($score->value, 1) }}</td>
                                            <td>
                                                @if($score->value < 60)
                                                    <span class="status-chip status-critical">Crítico</span>
                                                @elseif($score->value < 70)
                                                    <span class="status-chip status-warning">Bajo</span>
                                                @else
                                                    <span class="status-chip status-success">Aceptable</span>
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

            {{-- ===================== ALERTAS ===================== --}}
            <section class="tab-panel" data-tab="alerts">
                <div class="table-panel">
                    <div class="table-panel-header" style="padding: 20px 20px 0;">
                        <h3>Alertas académicas</h3>
                        <span class="status-chip {{ $unresolvedCount > 0 ? 'status-critical' : 'status-success' }}">
                            {{ $unresolvedCount > 0 ? $unresolvedCount.' activa(s)' : 'Todo al día' }}
                        </span>
                    </div>

                    @if($alerts->isEmpty())
                        <p class="muted" style="padding: 0 20px 20px;">No hay alertas activas por el momento.</p>
                    @else
                        <div class="table-scroll">
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
                                                <span class="status-chip status-{{ $alert->severity === 'critical' ? 'critical' : 'warning' }}">
                                                    {{ $alert->severity === 'critical' ? 'Crítico' : 'Advertencia' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            {{-- ===================== ASISTENTE IA ===================== --}}
            <section class="tab-panel" data-tab="ai">
                <div class="module-card">
                    <div class="module-header">
                        <div>
                            <h3>Asistente académico (IA)</h3>
                            <p>Pregunta sobre tus notas o alertas. Ej: "¿En qué materia debo enfocarme más?"</p>
                        </div>
                    </div>

                    @if(session('aiAnswer'))
                        <div class="activity-list" style="margin-bottom: 20px;">
                            <div class="activity-item" style="align-items: flex-start; background: #eff6ff;">
                                <div>
                                    <span class="activity-title">Tú</span>
                                    <span class="activity-meta">{{ session('aiQuestion') }}</span>
                                </div>
                            </div>
                            <div class="activity-item" style="align-items: flex-start;">
                                <div>
                                    <span class="activity-title">Asistente</span>
                                    <span class="activity-meta">{{ session('aiAnswer') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('student.ai-chat') }}" method="post" class="stacked-form">
                        @csrf
                        <label>Tu pregunta</label>
                        <input type="text" name="question" class="input" maxlength="500" required value="{{ old('question') }}" placeholder="¿En qué materia debo enfocarme más?" />
                        <button type="submit" class="btn btn-primary btn-block">Preguntar</button>
                    </form>
                </div>
            </section>
        </main>
    </section>
</div>

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

    @if(session('aiAnswer'))
        showTab('ai');
    @endif
})();
</script>
@endpush
@endsection
