<?php

namespace App\Http\Controllers;

use App\Models\User;

abstract class BaseController extends Controller
{
    protected function currentUser(): ?User
    {
        return session()->has('user_id') ? User::find(session('user_id')) : null;
    }
}
