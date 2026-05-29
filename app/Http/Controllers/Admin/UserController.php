<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    
    public function index()
    {
        return Inertia::render('users/users-index', [
            'users' => UserResource::collection(User::paginate(10)),
            //'search' => $request->only(['username', 'status', 'role', 'page']),
        ]); 
    }
}