<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminAlertController extends Controller
{
    public function resolve(Request $request, Alert $alert, AuditLogService $auditLogService)
    {
        $alert->update(['resolved' => true]);

        $auditLogService->record($request->attributes->get('authUser'), 'alerts.resolved', "Administrador marco alerta resuelta para {$alert->student?->name}.", Alert::class, $alert->id, [
            'subject_id' => $alert->subject_id,
        ], $request);

        return back()->with('status', 'Alerta marcada como resuelta.');
    }

    public function unresolve(Request $request, Alert $alert, AuditLogService $auditLogService)
    {
        $alert->update(['resolved' => false]);

        $auditLogService->record($request->attributes->get('authUser'), 'alerts.unresolved', "Administrador marco alerta no resuelta para {$alert->student?->name}.", Alert::class, $alert->id, [
            'subject_id' => $alert->subject_id,
        ], $request);

        return back()->with('status', 'Alerta marcada como no resuelta.');
    }
}
