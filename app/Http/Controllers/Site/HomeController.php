<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        return Inertia::render('site/home', [
            // feed real news/activities/volunteers here later
        ]);
    }
}
