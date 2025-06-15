<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
     public function index()
    {
        return view('admin.stores.index');
    }

    public function create()
    {
        return view('admin.stores.create');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.edit', compact('store'));
    }
}
