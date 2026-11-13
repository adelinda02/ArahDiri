<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BerandaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('profile.umbProgram');
        $latest = $user->latestResult();
        $umb = $user->profile?->umbProgram;

        return Inertia::render('Beranda', [
            'latest' => $latest ? ['holland_code' => $latest->holland_code, 'completed_at' => $latest->created_at?->toIso8601String()] : null,
            'umbProgram' => $umb ? ['name' => $umb->original_name, 'level' => $umb->level] : null,
        ]);
    }
}
