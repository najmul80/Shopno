<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
     public function index()
    {
        return view('products.index');
    }

    public function create()
    {
        // We are using a modal for create, but this route can serve a dedicated page.
        return view('products.create');
    }

    public function edit(Product $product)
    {
        // Pass the product ID to the view. JS will fetch the full data.
        return view('products.edit', ['productId' => $product->id]);
    }
}
