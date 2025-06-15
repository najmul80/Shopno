<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StoreStaffController extends Controller
{
     public function index()
    {
        return view('store_staff.index');
    }
    
    public function create()
    {
        return view('store_staff.create');
    }
    
    public function edit(User $user)
    {
        return view('store_staff.edit', compact('user'));
    }
}
