<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CreatorHomeController extends Controller
{
    public function __invoke(): View
    {
        return view('creator.home');
    }
}
