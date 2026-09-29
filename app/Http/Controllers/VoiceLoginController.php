<?php

namespace App\Http\Controllers;

use App\Services\Assistant\VoiceLoginAuthenticator;
use App\Services\Assistant\VoiceLoginService;
use Illuminate\Http\Request;

class VoiceLoginController extends Controller
{
    public function __construct(
        protected VoiceLoginService $login,
        protected VoiceLoginAuthenticator $authenticator,
    ) {
    }

    public function index()
    {
        return view('voice.index', ['mode' => 'login', 'hasSession' => false]);
    }

    public function start()
    {
        return response()->json($this->login->start());
    }

    public function process(Request $request)
    {
        $request->validate(['transcript' => 'required|string|max:500']);

        $step = $this->login->process($request->input('transcript'));

        if ($step['type'] !== 'ready') {
            return response()->json($step);
        }

        $result = $this->authenticator->attempt($request, $step['usuario'], $step['pin']);

        if ($result['type'] === 'login_failed') {
            // Se vuelve a preguntar el usuario, en la misma conversación.
            return response()->json($this->login->start($result['speak']));
        }

        $this->login->resetSession();

        return response()->json($result);
    }
}
