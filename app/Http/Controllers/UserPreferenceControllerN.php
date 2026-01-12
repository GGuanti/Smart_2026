<?php
// app/Http/Controllers/UserPreferenceControllerN.php
namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;

class UserPreferenceControllerN extends Controller
{
    public function show(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:190',
            'type' => 'nullable|string|max:50', // "columns" ecc.
        ]);

        $pref = UserPreference::where('user_id', auth()->id())
            ->where('key', $request->key)
            ->first();

        return response()->json([
            'data' => $pref?->value ?? null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:190',
            'type' => 'nullable|string|max:50',
            'data' => 'nullable', // array/tabulator data
        ]);

        $pref = UserPreference::updateOrCreate(
            ['user_id' => auth()->id(), 'key' => $request->key],
            ['value' => $request->data]
        );

        return response()->json(['ok' => true]);
    }
    public function destroy(Request $request)
{
    $request->validate([
        'key' => 'required|string|max:190',
    ]);

    \App\Models\UserPreference::where('user_id', auth()->id())
        ->where('key', $request->key)
        ->delete();

    return response()->json(['ok' => true]);
}
}
