<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserManagementController extends Controller
{
   public function index()
{
    $query = \App\Models\User::query();

    // Search
    if (request('search')) {
        $query->where(function ($q) {
            $q->where('name', 'like', '%' . request('search') . '%')
              ->orWhere('email', 'like', '%' . request('search') . '%');
        });
    }

    // Filter by Role
    if (request('role')) {
        $query->where('role', request('role'));
    }

    // Filter by Department
    if (request('department')) {
        $query->where('department', request('department'));
    }

    // Filter by Status
    if (request('status')) {
        $query->where('status', request('status'));
    }

    $users = $query->latest()->get();

    return view('admin.user-management', compact('users'));
}
}   