<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->get();
        $products = Product::with(['category', 'brand'])->orderByDesc('id')->take(8)->get();
        $bestSellers = Product::bestSellers()->with(['category', 'brand'])->limit(8)->get();

        return view('home', compact('categories', 'products', 'bestSellers'));
    }
}
