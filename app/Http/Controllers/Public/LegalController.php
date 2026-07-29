<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function terms(): Response
    {
        return Inertia::render('Public/Legal/Terms');
    }

    public function privacy(): Response
    {
        return Inertia::render('Public/Legal/Privacy');
    }
}
