<?php

namespace App\Http\Controllers;

use App\Models\User;

class DashboardController extends BaseController
{
    public function home()
    {
        return view('home');
    }

    public function index()
    {
        $user = $this->currentUser();

        if (! $user) {
            return redirect('/login');
        }

        if ($user->isAdmin()) {
            return redirect('/admin');
        }

        if ($user->isTeacher()) {
            return redirect('/teacher');
        }

        if ($user->isStudent()) {
            return redirect('/student');
        }

        return redirect('/login');
    }
}
