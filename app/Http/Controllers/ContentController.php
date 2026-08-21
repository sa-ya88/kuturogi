<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function onsen(): Response
    {
        return Inertia::render('Onsen');
    }

    public function food(): Response
    {
        return Inertia::render('Food');
    }

    public function sightseeing(): Response
    {
        return Inertia::render('Sightseeing');
    }

    public function access(): Response
    {
        return Inertia::render('Access');
    }

    public function company(): Response
    {
        return Inertia::render('Company');
    }

    public function faq(): Response
    {
        return Inertia::render('Faq');
    }

    public function news(): Response
    {
        return Inertia::render('News/Index', [
            'news' => News::latest('published_at')->get(),
        ]);
    }
}
