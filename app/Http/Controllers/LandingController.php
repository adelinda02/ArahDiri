<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Landing', ['copy' => ContentItem::bag('copy')]);
    }
}
