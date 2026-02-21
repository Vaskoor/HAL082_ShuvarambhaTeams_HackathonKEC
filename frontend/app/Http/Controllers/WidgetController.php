<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chatbot;
use App\Models\Website;

class WidgetController extends Controller
{
    public function loadWidget(Chatbot $chatbot, Request $request)
    {
        $currentDomain = $request->header('origin') ?? $request->getHost();

        $website = Website::where('domain', $currentDomain)->first();

        if (!$website || !$chatbot->isPublishedOn($website->id)) {
            return response()->json(['error' => 'Widget not allowed'], 403);
        }

        // Return JS-friendly data (or HTML fragment)
        return response()->json([
            'chatbot_id' => $chatbot->id,
        ]);
    }
}