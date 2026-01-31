<?php

namespace App\Http\Controllers;

use App\Models\DoorConfig;
use App\Models\Texture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DoorConfigController extends Controller
{
    public function index()
    {
        $textures = Texture::where('user_id', Auth::id())->latest()->get();
        $configs  = DoorConfig::where('user_id', Auth::id())->latest()->get();

        return Inertia::render('Door/Configurator', [
            'textures' => $textures,
            'configs'  => $configs,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable','string','max:120'],
            'opening_type' => ['required','in:battente,libro,rototraslante,scorrevole_interno,scorrevole_esterno,scorrevole_mantovana'],
            'anta_texture_id' => ['nullable','integer','exists:textures,id'],
            'telaio_texture_id' => ['nullable','integer','exists:textures,id'],
            'params' => ['nullable','array'],
        ]);

        // sicurezza: le texture devono essere dell’utente
        foreach (['anta_texture_id','telaio_texture_id'] as $k) {
            if (!empty($data[$k])) {
                $ok = Texture::where('id', $data[$k])
                    ->where('user_id', Auth::id())
                    ->exists();
                abort_unless($ok, 403);
            }
        }

        DoorConfig::create([
            'user_id' => Auth::id(),
            ...$data,
        ]);

        return back()->with('success', 'Configurazione salvata');
    }

    public function destroy(DoorConfig $doorConfig)
    {
        abort_unless($doorConfig->user_id === Auth::id(), 403);
        $doorConfig->delete();

        return back()->with('success', 'Configurazione eliminata');
    }
}
