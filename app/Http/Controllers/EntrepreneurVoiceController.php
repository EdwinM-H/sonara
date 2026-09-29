<?php

namespace App\Http\Controllers;

use App\Services\Assistant\EntrepreneurVoiceMenu;
use Illuminate\Http\Request;

class EntrepreneurVoiceController extends Controller
{
    public function command(Request $request, EntrepreneurVoiceMenu $menu)
    {
        $request->validate(['transcript' => 'required|string|max:500']);

        return response()->json($menu->interpret($request->input('transcript')));
    }
}
