<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Top', [
            'latestNews' => News::latest('published_at')->take(3)->get(),
        ]);
    }
}
