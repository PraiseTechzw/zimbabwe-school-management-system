<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Frontend\Contracts\FrontendDataProvider;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class SchoolProfileController extends Controller
{
    public function __construct(private readonly FrontendDataProvider $data) {}

    public function index(): View
    {
        return view('frontend.school-profile', [
            'user' => $this->data->user(),
            'school' => $this->data->school(),
            'identifiers' => $this->data->identifiers(),
        ]);
    }
}
