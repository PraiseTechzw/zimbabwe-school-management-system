<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class LoginController extends Controller
{
    public function show(): View
    {
        return view('frontend.login');
    }
}
