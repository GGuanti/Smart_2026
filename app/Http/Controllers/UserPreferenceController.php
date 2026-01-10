<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserPreferenceController extends Controller
{
    public function get(Request $request)
    {
        $key = $request->query('key');
        $prefs = auth()->user()->table_preferences ?? [];



        return response()->json($prefs[$key] ?? []);
    }

    public function save(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'columns' => 'required|array|min:1', // 👈 evita array vuoto
        ]);

        $user = auth()->user();
        $prefs = $user->table_preferences ?? [];
        $prefs[$request->key] = $request->columns;

        $user->table_preferences = $prefs;
        $user->save();

        return response()->json(['status' => 'ok']);
    }

    public function reset(Request $request)
{
    $request->validate([
        'key' => 'required|string',
    ]);

    $user = auth()->user();
    $prefs = $user->table_preferences ?? [];

    unset($prefs[$request->key]); // Rimuove solo quella chiave

    $user->table_preferences = $prefs;
    $user->save();

    return response()->json(['status' => 'reset']);
}
}
