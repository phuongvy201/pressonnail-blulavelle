<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;

class TestController extends Controller
{
    public function test(OpenAIService $ai)
    {
        $title = "Luxury Stained Glass Bald Eagle Press On Nails, Patriotic Americana Art, Red White & Blue Iridescent Nails, Custom 4th of July Long Stiletto Nails";

        $keywords = $ai->extractKeywords($title);

        return response()->json([
            'title' => $title,
            'keywords' => $keywords
        ]);
    }
}
