@extends('layouts.app')

@section('title', 'Panel Administrador')

@section('content')
<div class="dashboard-shell">
    <aside class="dashboard-sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">IA</div>
            <div>
                <h1>Sistema de Alertas</h1>
                <p>Administración académica</p>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Navegación del panel">
            <button type="button" class="sidebar-link active" data-tab="summary">
                <span class="icon">📊</span>
                <span>Resumen</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="users">
                <span class="icon">👥</span>
                <span>Usuarios</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="courses">
                <span class="icon">📚</span>
                <span>Cursos y Materias</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="alerts">
                <span class="icon">🚨</span>
                <span>Alertas IA</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="reports">
                <span class="icon">📁</span>
                <span>Reportes</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="audit">
                <span class="icon">LOG</span>
                <span>Auditoria</span>
            </button>
            <button type="button" class="sidebar-link" data-tab="settings">
                <span class="icon">⚙️</span>
                <span>Configuración IA</span>
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
                <p class="eyebrow">Panel Administrativo</p>
                <h2>Control central del sistema inteligente</h2>
                <p class="hero-copy">Monitorea estudiantes, profesores, cursos y alertas de rendimiento en un solo lugar.</p>
            </div>
        </header>

        <main class="dashboard-panel">
            @php
                $activeAlerts = $alerts->where('resolved', false)->count();
                $resolvedAlerts = $alerts->where('resolved', true)->count();
                $criticalAlerts = $alerts->where('severity', 'critical')->count();
                $warningAlerts = $alerts->where('severity', 'warning')->count();
                $lowAlerts = $alerts->where('severity', 'low')->count();
            @endphp

            <section class="tab-panel active" data-tab="summary">
                <div class="kpi-grid">
                    <article class="kpi-card">
                        <span class="kpi-label">Estudiantes</span>
                        <strong>{{ $students->count() }}</strong>
                        <p>Registros de alumnos activos.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Profesores</span>
                        <strong>{{ $teachers->count() }}</strong>
                        <p>Docentes asignados al sistema.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Cursos</span>
                        <strong>{{ $courses->count() }}</strong>
                        <p>Programas y asignaturas disponibles.</p>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-label">Alertas activas</span>
                        <strong>{{ $activeAlerts }}</strong>
                        <p>Casos que requieren seguimiento inmediato.</p>
                    </article>
                </div>

                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div>
                                <p class="eyebrow">Distribución de alertas</p>
                                <h3>Alertas por nivel</h3>
                            </div>
                        </div>
                        <div class="summary-card-body">
                            <canvas id="summary-alert-chart" data-values='{{ json_encode(['critical' => $criticalAlerts, 'warning' => $warningAlerts, 'low' => $lowAlerts]) }}'></canvas>
                        </div>
                    </div>

                    <div class="summary-card stretch-card">
                        <div class="summary-card-header">
                            <div>
                                <p class="eyebrow">Actividad reciente</p>
                                <h3>Últimas alertas y cambios</h3>
                            </div>
                        </div>
                        <div class="activity-list">
                            @forelse($alerts->sortByDesc('updated_at')->take(6) as $alert)
                                <div class="activity-item">
                                    <div>
                                        <span class="activity-title">{{ $alert->student?->name ?? 'Estudiante desconocido' }}</span>
                                        <span class="activity-meta">{{ $alert->subject?->name ?? 'Materia' }} · {{ optional($alert->updated_at)->format('d M H:i') }}</span>
                                    </div>
                                    <span class="status-chip status-{{ $alert->resolved ? 'success' : 'warning' }}">{{ $alert->resolved ? 'Revisada' : 'Activa' }}</span>
                                </div>
                            @empty
                                <p class="muted">No se han registrado actividades recientes.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="tab-panel" data-tab="users">
                @if(session('importSummary'))
                    @php
                        $summary = session('importSummary');
                    @endphp
                    <div class="module-card" style="margin-bottom: 20px;">
                        <div class="module-header">
                            <h3>{{ $summary['title'] }}</h3>
                            <p>
                                Creados: {{ $summary['created'] }} · Actualizados: {{ $summary['updated'] }}
                                @if(($summary['courses_created'] ?? 0) > 0)
                                    · Cursos creados: {{ $summary['courses_created'] }}
                                @endif
                                · Errores: {{ count($summary['errors']) }}
                            </p>
                        </div>
                        @if(!empty($summary['errors']))
                            <div class="activity-list">
                                @foreach(array_slice($summary['errors'], 0, 8) as $error)
                                    <div class="activity-item">
                                        <span class="activity-title">{{ $error }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="section-split">
                    <div class="module-card">
                        <div class="module-header">
                            <h3>Crear usuario</h3>
                            <p>Agregar estudiantes y profesores con un flujo rápido.</p>
                        </div>
                        <form action="{{ route('admin.users.store') }}" method="post" class="stacked-form">
                            @csrf
                            <label>Nombre</label>
                            <input type="text" name="name" class="input" value="{{ old('name') }}" required />
                            <label>Correo</label>
                            <input type="email" name="email" class="input" value="{{ old('email') }}" required />
                            <label>Rol</label>
                            <select name="role" class="select" required>
                                <option value="teacher">Profesor</option>
                                <option value="student">Estudiante</option>
                            </select>
                            <label>Curso / paralelo</label>
                            <select name="course_id" class="select">
                                <option value="">Sin curso</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                            <label>Contraseña</label>
                            <input type="password" name="password" class="input" required />
                            <button type="submit" class="btn btn-primary btn-block">Crear usuario</button>
                        </form>
                    </div>

                    <div class="module-card">
                        <div class="module-header">
                            <h3>Buscador de usuarios</h3>
                            <p>Filtra nombres y roles para encontrar registros rápido.</p>
                        </div>
                        <form method="get" action="{{ route('admin') }}" class="stacked-form">
                            <label>Buscar usuario</label>
                            <input type="search" name="q" class="input" placeholder="Nombre, correo o rol" />
                            <button type="submit" class="btn btn-secondary btn-block">Buscar</button>
                        </form>
                    </div>
                </div>

                <div class="module-card" style="margin-bottom: 20px;">
                    <div class="module-header">
                        <h3>Importar estudiantes</h3>
                        <p>Columnas: nombre, email, password. Puedes agregar curso como grupo/paralelo; si no existe, se crea automaticamente.</p>
                    </div>
                    <form method="post" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="stacked-form">
                        @csrf
                        <label>Archivo CSV</label>
                        <input type="file" name="students_file" class="input" accept=".csv,text/csv,text/plain" required />
                        <button type="submit" class="btn btn-secondary btn-block">Importar estudiantes</button>
                    </form>
                </div>

                <div class="table-panel">
                    <div class="table-panel-header">
                        <h3>Estudiantes</h3>
                        <span>{{ $students->count() }} registros</span>
                    </div>
                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Curso / paralelo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->email }}</td>
                                        <td>{{ $student->course?->name ?? 'N/A' }}</td>
                                        <td class="table-actions">
                                            <button type="button" class="btn btn-secondary btn-small"
                                                data-edit-user
                                                data-id="{{ $student->id }}"
                                                data-name="{{ $student->name }}"
                                                data-email="{{ $student->email }}"
                                                data-role="student"
                                                data-course-id="{{ $student->course_id }}">Editar</button>
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
                </div>

                <div class="table-panel">
                    <div class="table-panel-header">
                        <h3>Profesores</h3>
                        <span>{{ $teachers->count() }} registros</span>
                    </div>
                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Curso / paralelo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teachers as $teacher)
                                    <tr>
                                        <td>{{ $teacher->name }}</td>
                                        <td>{{ $teacher->email }}</td>
                                        <td>{{ $teacher->course?->name ?? 'N/A' }}</td>
                                        <td class="table-actions">
                                            <button type="button" class="btn btn-secondary btn-small"
                                                data-edit-user
                                                data-id="{{ $teacher->id }}"
                                                data-name="{{ $teacher->name }}"
                                                data-email="{{ $teacher->email }}"
                                                data-role="teacher"
                                                data-course-id="{{ $teacher->course_id }}">Editar</button>
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
                </div>

                <div class="pagination-wrap">
                    @if(method_exists($students, 'links'))
                        {{ $students->links() }}
                    @endif
                </div>
            </section>

            <section class="tab-panel" data-tab="courses">
                <div class="section-split">
                    <div class="module-card">
                        <div class="module-header">
                            <h3>Crear curso / paralelo</h3>
                            <p>Define grupos como Primero A, Segundo B o Tercero Informatica. Los parciales se manejan en las notas.</p>
                        </div>
                        <form action="{{ route('admin.courses.store') }}" method="post" class="stacked-form">
                            @csrf
                            <label>Nombre del curso / paralelo</label>
                            <input type="text" name="name" class="input" value="{{ old('name') }}" required />
                            <label>Descripción</label>
                            <textarea name="description" class="textarea">{{ old('description') }}</textarea>
                            <button type="submit" class="btn btn-primary btn-block">Crear curso / paralelo</button>
                        </form>
                    </div>

                    <div class="module-card">
                        <div class="module-header">
                            <h3>Crear materia</h3>
                            <p>Asigna una materia a un curso/paralelo y a un profesor.</p>
                        </div>
                        <form action="{{ route('admin.subjects.store') }}" method="post" class="stacked-form">
                            @csrf
                            <label>Nombre de la materia</label>
                            <input type="text" name="name" class="input" required />
                            <label>Curso / paralelo</label>
                            <select name="course_id" class="select" required>
                                <option value="">Selecciona un curso / paralelo</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                            <label>Profesor</label>
                            <select name="teacher_id" class="select" required>
                                <option value="">Selecciona un profesor</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-block">Crear materia</button>
                        </form>
                    </div>
                </div>

                <div class="table-panel">
                    <div class="table-panel-header">
                        <h3>Listado de cursos / paralelos</h3>
                        <span>{{ $courses->count() }} registros</span>
                    </div>
                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($courses as $course)
                                    <tr>
                                        <td>{{ $course->name }}</td>
                                        <td>{{ $course->description }}</td>
                                        <td class="table-actions">
                                            <button type="button" class="btn btn-secondary btn-small"
                                                data-edit-course
                                                data-id="{{ $course->id }}"
                                                data-name="{{ $course->name }}"
                                                data-description="{{ $course->description }}">Editar</button>
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
                </div>

                <div class="table-panel">
                    <div class="table-panel-header">
                        <h3>Listado de materias</h3>
                        <span>{{ $subjects->count() }} materias</span>
                    </div>
                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>Curso / paralelo</th>
                                    <th>Profesor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjects as $subject)
                                    <tr>
                                        <td>{{ $subject->name }}</td>
                                        <td>{{ $subject->course?->name ?? 'N/A' }}</td>
                                        <td>{{ $subject->teacher?->name ?? 'N/A' }}</td>
                                        <td class="table-actions">
                                            <button type="button" class="btn btn-secondary btn-small"
                                                data-edit-subject
                                                data-id="{{ $subject->id }}"
                                                data-name="{{ $subject->name }}"
                                                data-course-id="{{ $subject->course_id }}"
                                                data-teacher-id="{{ $subject->teacher_id }}">Editar</button>
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
                </div>
                <div class="module-card">
                    <div class="module-header">
                        <h3>Inscribir estudiante en materia</h3>
                        <p>Esto es necesario para que el profesor pueda registrarle notas.</p>
                    </div>
                    <form action="{{ route('admin.enrollments.store') }}" method="post" class="stacked-form">
                        @csrf
                        <label>Materia</label>
                        <select name="subject_id" class="select" required>
                            <option value="">Selecciona una materia</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }} - {{ $subject->course?->name }}</option>
                            @endforeach
                        </select>
                        <label>Estudiante</label>
                        <select name="student_id" class="select" required>
                            <option value="">Selecciona un estudiante</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->course?->name ?? 'sin curso' }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-block">Inscribir</button>
                    </form>
                </div>

                <div class="table-panel">
                    <div class="table-panel-header">
                        <h3>Estudiantes inscritos por materia</h3>
                    </div>
                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>Estudiantes inscritos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subjects as $subject)
                                    <tr>
                                        <td>{{ $subject->name }}</td>
                                        <td>
                                            @forelse($subject->students as $enrolledStudent)
                                                <span class="badge badge-success" style="margin:2px; display:inline-flex; align-items:center; gap:6px;">
                                                    {{ $enrolledStudent->name }}
                                                    <form action="{{ route('admin.enrollments.delete') }}" method="post" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="subject_id" value="{{ $subject->id }}" />
                                                        <input type="hidden" name="student_id" value="{{ $enrolledStudent->id }}" />
                                                        <button type="submit" style="border:none; background:none; cursor:pointer; color:#7a1f1f;" title="Quitar">✕</button>
                                                    </form>
                                                </span>
                                            @empty
                                                <span style="color:#5f6c7b;">Sin estudiantes inscritos</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="tab-panel" data-tab="alerts">
                <form method="get" action="{{ route('admin') }}" class="filters-panel">
                    <div class="filter-group">
                        <label>Curso / paralelo</label>
                        <select name="filter_course" class="select">
                            <option value="">Todos</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('filter_course') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Materia</label>
                        <select name="filter_subject" class="select">
                            <option value="">Todas</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ request('filter_subject') == $subject->id ? 'selected' : '' }}>{{ $subject->name }} - {{ $subject->course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Estado</label>
                        <select name="filter_status" class="select">
                            <option value="all" {{ request('filter_status', 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                            <option value="open" {{ request('filter_status') === 'open' ? 'selected' : '' }}>Activa</option>
                            <option value="resolved" {{ request('filter_status') === 'resolved' ? 'selected' : '' }}>Revisada</option>
                            <option value="ignored" {{ request('filter_status') === 'ignored' ? 'selected' : '' }}>Ignorada</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Aplicar</button>
                        <a href="{{ route('admin') }}" class="btn btn-secondary">Restaurar</a>
                    </div>
                </form>

                <div class="table-scroll">
                    <table class="table table-bordered table-fixed">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Materia</th>
                                <th>Severidad</th>
                                <th>Estado</th>
                                <th>Promedio</th>
                                <th>Mensaje</th>
                                <th>Detalles</th>
                                <th>Historial</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->student?->name ?? 'N/A' }}</td>
                                    <td>{{ $alert->subject?->name ?? 'N/A' }}</td>
                                    <td><span class="status-chip status-{{ $alert->severity ?? 'low' }}">{{ ucfirst($alert->severity ?? 'baja') }}</span></td>
                                    <td><span class="status-chip status-{{ $alert->resolved ? 'success' : 'warning' }}">{{ $alert->resolved ? 'Revisada' : 'Activa' }}</span></td>
                                    <td>{{ $alert->meta['avg'] ?? '-' }}</td>
                                    <td>{{ $alert->message }}</td>
                                    <td><button type="button" class="btn btn-secondary btn-small">Ver detalles</button></td>
                                    <td><button type="button" class="btn btn-primary btn-small btn-history" data-values='{{ json_encode($alert->meta['values'] ?? []) }}' data-student='{{ $alert->student?->name ?? "" }}' data-subject='{{ $alert->subject?->name ?? "" }}'>Historial</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap">
                    @if(method_exists($alerts, 'links'))
                        {{ $alerts->links() }}
                    @endif
                </div>
            </section>

            <section class="tab-panel" data-tab="reports">
                <div class="report-grid">
                    <div class="report-card">
                        <h3>Reporte por curso / paralelo</h3>
                        <form action="{{ route('admin.reports.course') }}#report-section" method="get" class="stacked-form">
                            <label>Selecciona un curso / paralelo</label>
                            <select name="course_id" class="select" required>
                                <option value="">Selecciona un curso / paralelo</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-block">Ver reporte</button>
                        </form>
                    </div>
                    <div class="report-card">
                        <h3>Reporte por profesor</h3>
                        <form action="{{ route('admin.reports.teacher') }}#report-section" method="get" class="stacked-form">
                            <label>Selecciona un profesor</label>
                            <select name="teacher_id" class="select" required>
                                <option value="">Selecciona un profesor</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-block">Ver reporte</button>
                        </form>
                    </div>
                    <div class="report-card">
                        <h3>Reporte por estudiante</h3>
                        <form action="{{ route('admin.reports.student') }}#report-section" method="get" class="stacked-form">
                            <label>Selecciona un estudiante</label>
                            <select name="student_id" class="select" required>
                                <option value="">Selecciona un estudiante</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-block">Ver reporte</button>
                        </form>
                    </div>
                </div>

                @if(isset($report))
                    <div id="report-section" class="report-summary-card">
                        <div class="report-header-actions">
                            <h3>{{ $report['title'] }}</h3>
                            @if($report['type'] === 'course')
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.course.pdf', $report['course']->id) }}">Exportar PDF</a>
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.course.excel', $report['course']->id) }}">Exportar Excel</a>
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.course.xml', $report['course']->id) }}">Exportar XML</a>
                            @elseif($report['type'] === 'teacher')
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.teacher.pdf', $report['teacher']->id) }}">Exportar PDF</a>
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.teacher.excel', $report['teacher']->id) }}">Exportar Excel</a>
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.teacher.xml', $report['teacher']->id) }}">Exportar XML</a>
                            @elseif($report['type'] === 'student')
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.student.pdf', $report['student']->id) }}">Exportar PDF</a>
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.student.excel', $report['student']->id) }}">Exportar Excel</a>
                                <a class="btn btn-secondary btn-small" href="{{ route('admin.reports.student.xml', $report['student']->id) }}">Exportar XML</a>
                            @endif
                        </div>

                        @if(!empty($report['ai_summary']))
                            <div class="card" style="padding:14px; background:#eff7ff; box-shadow:none; margin-bottom:16px;">
                                <strong>Resumen (IA)</strong>
                                <p style="margin-top:6px;">{{ $report['ai_summary'] }}</p>
                            </div>
                        @endif

                        @if($report['type'] === 'course')
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['student']->name }}</td>
                                            <td>{{ $row['average'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($report['type'] === 'teacher')
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <th>Promedio</th>
                                        <th>Estudiantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['subject']->name }}</td>
                                            <td>{{ $row['average'] }}</td>
                                            <td>{{ $row['studentCount'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($report['type'] === 'student')
                            <div class="report-details-row">
                                <div>
                                    <strong>Curso / paralelo:</strong> {{ $report['student']->course?->name ?? 'N/A' }}
                                </div>
                                <div>
                                    <strong>Promedio general:</strong> {{ $report['average'] }}
                                </div>
                            </div>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <th>Curso / paralelo</th>
                                        <th>Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report['scores'] as $score)
                                        <tr>
                                            <td>{{ $score->subject->name }}</td>
                                            <td>{{ $score->subject->course->name }}</td>
                                            <td>{{ $score->value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($report['alerts']->isNotEmpty())
                                <div class="alert alert-warning">
                                    <strong>Alertas del estudiante:</strong>
                                    <ul>
                                        @foreach($report['alerts'] as $alert)
                                            <li>{{ $alert->message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endif
                    </div>
                @endif
            </section>

            <section class="tab-panel" data-tab="audit">
                <div class="table-panel">
                    <div class="table-panel-header" style="padding: 20px 20px 0;">
                        <h3>Auditoria de acciones</h3>
                        <span class="status-chip status-success">{{ $auditLogs->count() }} registros recientes</span>
                    </div>

                    @if($auditLogs->isEmpty())
                        <p class="muted" style="padding: 0 20px 20px;">Aun no hay acciones registradas.</p>
                    @else
                        <div class="table-scroll">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Rol</th>
                                        <th>Accion</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($auditLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                                            <td>{{ $log->role ?? 'N/A' }}</td>
                                            <td><span class="status-chip status-warning">{{ $log->action }}</span></td>
                                            <td>{{ $log->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            <section class="tab-panel" data-tab="settings">
                <div class="settings-grid">
                    <div class="module-card">
                        <div class="module-header">
                            <h3>Umbrales IA</h3>
                            <p>Ajusta los límites para generar alertas.</p>
                        </div>
                        <form action="{{ route('admin.settings.update') }}" method="post" class="stacked-form">
                            @csrf
                            <label>Umbral de alerta (warning)</label>
                            <input type="number" name="alert_warning" class="input" value="{{ $alertWarning }}" min="0" max="100" required />
                            <label>Umbral crítico (danger)</label>
                            <input type="number" name="alert_danger" class="input" value="{{ $alertDanger }}" min="0" max="100" required />
                            <button type="submit" class="btn btn-primary btn-block">Guardar umbrales</button>
                        </form>
                    </div>
                    <div class="module-card">
                        <div class="module-header">
                            <h3>Notificaciones</h3>
                            <p>Configura alertas por correo y avisos internos.</p>
                        </div>
                        <form action="{{ route('admin.settings.update') }}" method="post" class="stacked-form">
                            @csrf
                            <label class="toggle-label">
                                <span>Enviar notificaciones por correo</span>
                                <input type="checkbox" name="notify_email" checked />
                            </label>
                            <label class="toggle-label">
                                <span>Activar alertas en panel</span>
                                <input type="checkbox" name="notify_dashboard" checked />
                            </label>
                            <button type="submit" class="btn btn-secondary btn-block">Guardar notificaciones</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </section>
</div>

<div id="history-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="history-title">Histórico</h3>
            <button id="history-close" class="btn btn-secondary btn-small">Cerrar</button>
        </div>
        <canvas id="history-chart" width="700" height="300"></canvas>
    </div>
</div>

<div id="edit-user-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Editar usuario</h3>
            <button type="button" class="btn btn-secondary btn-small" data-modal-close="edit-user-modal">Cerrar</button>
        </div>
        <form id="edit-user-form" method="post" class="stacked-form">
            @csrf
            @method('PATCH')
            <label>Nombre</label>
            <input type="text" name="name" id="edit-user-name" class="input" required />
            <label>Correo</label>
            <input type="email" name="email" id="edit-user-email" class="input" required />
            <label>Rol</label>
            <select name="role" id="edit-user-role" class="select" required>
                <option value="student">Estudiante</option>
                <option value="teacher">Profesor</option>
            </select>
            <label>Curso / paralelo</label>
            <select name="course_id" id="edit-user-course" class="select">
                <option value="">Sin curso</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                @endforeach
            </select>
            <label>Nueva contraseña (opcional)</label>
            <input type="password" name="password" class="input" placeholder="Dejar vacío para no cambiarla" />
            <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
        </form>
    </div>
</div>

<div id="edit-course-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Editar curso / paralelo</h3>
            <button type="button" class="btn btn-secondary btn-small" data-modal-close="edit-course-modal">Cerrar</button>
        </div>
        <form id="edit-course-form" method="post" class="stacked-form">
            @csrf
            @method('PATCH')
            <label>Nombre del curso / paralelo</label>
            <input type="text" name="name" id="edit-course-name" class="input" required />
            <label>Descripción</label>
            <textarea name="description" id="edit-course-description" class="textarea"></textarea>
            <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
        </form>
    </div>
</div>

<div id="edit-subject-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Editar materia</h3>
            <button type="button" class="btn btn-secondary btn-small" data-modal-close="edit-subject-modal">Cerrar</button>
        </div>
        <form id="edit-subject-form" method="post" class="stacked-form">
            @csrf
            @method('PATCH')
            <label>Nombre de la materia</label>
            <input type="text" name="name" id="edit-subject-name" class="input" required />
            <label>Curso / paralelo</label>
            <select name="course_id" id="edit-subject-course" class="select" required>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}">{{ $course->name }}</option>
                @endforeach
            </select>
            <label>Profesor</label>
            <select name="teacher_id" id="edit-subject-teacher" class="select" required>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const navItems = document.querySelectorAll('.sidebar-link');
    const panels = document.querySelectorAll('.tab-panel');

    function showTab(tab) {
        navItems.forEach(item => item.classList.toggle('active', item.dataset.tab === tab));
        panels.forEach(panel => panel.classList.toggle('active', panel.dataset.tab === tab));
    }

    navItems.forEach(item => item.addEventListener('click', () => showTab(item.dataset.tab)));
    showTab('summary');

    const historyModal = document.getElementById('history-modal');
    const historyClose = document.getElementById('history-close');
    const historyTitle = document.getElementById('history-title');
    const historyCanvas = document.getElementById('history-chart');
    let historyChart = null;

    document.addEventListener('click', event => {
        const button = event.target.closest('.btn-history');
        if (!button) return;
        const values = JSON.parse(button.dataset.values || '[]');
        const student = button.dataset.student || '';
        const subject = button.dataset.subject || '';
        historyTitle.textContent = `${student} · ${subject}`;
        const labels = values.map((_, index) => `T-${values.length - index}`);

        const data = {
            labels,
            datasets: [{
                label: 'Calificaciones',
                data: values,
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(59,130,246,0.15)',
                fill: true,
                tension: 0.25,
            }]
        };

        if (historyChart) historyChart.destroy();
        historyChart = new Chart(historyCanvas.getContext('2d'), {
            type: 'line',
            data,
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { min: 0, max: 100 } }
            }
        });

        historyModal.classList.add('open');
    });

    historyClose.addEventListener('click', () => historyModal.classList.remove('open'));

    // --- Modales de edición (usuario, curso, materia) ---
    function openModal(id) {
        document.getElementById(id).classList.add('open');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.modalClose));
    });

    document.addEventListener('click', event => {
        const editUserBtn = event.target.closest('[data-edit-user]');
        if (editUserBtn) {
            const d = editUserBtn.dataset;
            document.getElementById('edit-user-form').action = `/admin/users/${d.id}`;
            document.getElementById('edit-user-name').value = d.name;
            document.getElementById('edit-user-email').value = d.email;
            document.getElementById('edit-user-role').value = d.role;
            document.getElementById('edit-user-course').value = d.courseId && d.courseId !== '' ? d.courseId : '';
            openModal('edit-user-modal');
            return;
        }

        const editCourseBtn = event.target.closest('[data-edit-course]');
        if (editCourseBtn) {
            const d = editCourseBtn.dataset;
            document.getElementById('edit-course-form').action = `/admin/courses/${d.id}`;
            document.getElementById('edit-course-name').value = d.name;
            document.getElementById('edit-course-description').value = d.description || '';
            openModal('edit-course-modal');
            return;
        }

        const editSubjectBtn = event.target.closest('[data-edit-subject]');
        if (editSubjectBtn) {
            const d = editSubjectBtn.dataset;
            document.getElementById('edit-subject-form').action = `/admin/subjects/${d.id}`;
            document.getElementById('edit-subject-name').value = d.name;
            document.getElementById('edit-subject-course').value = d.courseId;
            document.getElementById('edit-subject-teacher').value = d.teacherId;
            openModal('edit-subject-modal');
            return;
        }
    });

    const summaryChart = document.getElementById('summary-alert-chart');
    if (summaryChart) {
        const summaryData = JSON.parse(summaryChart.dataset.values || '{}');
        new Chart(summaryChart.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Críticas', 'Advertencia', 'Bajas'],
                datasets: [{
                    data: [summaryData.critical, summaryData.warning, summaryData.low],
                    backgroundColor: ['#dc2626', '#f59e0b', '#22c55e'],
                    borderColor: '#f8fafc',
                    borderWidth: 2,
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }
})();
</script>
@endpush
