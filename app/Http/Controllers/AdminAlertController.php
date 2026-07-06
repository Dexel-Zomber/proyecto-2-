<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class AdminAlertController extends Controller
{
    public function resolve(Request $request, Alert $alert)
    {
        $alert->update(['resolved' => true]);

        return back()->with('status', 'Alerta marcada como resuelta.');
    }

    public function unresolve(Request $request, Alert $alert)
    {
        $alert->update(['resolved' => false]);

        return back()->with('status', 'Alerta marcada como no resuelta.');
    }
}
