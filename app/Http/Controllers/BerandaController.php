<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BerandaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('profile.currentProgram.campus');
        $latest = $user->latestResult();
        $cur = $user->profile?->currentProgram;

        return Inertia::render('Beranda', [
            'latest' => $latest ? ['holland_code' => $latest->holland_code, 'completed_at' => $latest->created_at?->toIso8601String()] : null,
            'currentProgram' => $cur ? ['name' => $cur->original_name, 'level' => $cur->level, 'campus' => $cur->campus?->name] : null,
        ]);
    }
}
