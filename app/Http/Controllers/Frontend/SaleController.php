<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
     public function index()
    {
        return view('sales.index'); // Sales history list
    }

    public function create()
    {
        return view('sales.create_pos'); // The POS interface
    }

    public function show(Sale $sale)
    {
        // This page will show the invoice and use JS to fetch details
        return view('sales.show_invoice_page', compact('sale'));
    }
}
