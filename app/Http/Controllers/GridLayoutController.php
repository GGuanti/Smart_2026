<?php

namespace App\Http\Controllers;

use App\Models\GridLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GridLayoutController extends Controller
{
    /**
     * Salva o aggiorna il layout di una query per l'utente corrente.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query_name'        => 'required|string|max:100',
            'layout'            => 'required|array|min:1',
            'layout.*.key'      => 'required|string',
            'layout.*.label'    => 'required|string',
            'layout.*.width'    => 'required|integer|min:40|max:1200',
            'layout.*.visible'  => 'required|boolean',
            'layout.*.order'    => 'required|integer|min:0',
        ]);

        GridLayout::updateOrCreate(
            [
                 'user_id'    => Auth::id(),
                'query_name' => $validated['query_name'],
            ],
            [
                'layout' => $validated['layout'],
            ]
        );

        return response()->json([]);
    }

    /**
     * Elimina il layout salvato per una query.
     */
    public function destroy(string $queryName): JsonResponse
    {
        GridLayout::where('user_id',Auth::id())
            ->where('query_name', $queryName)
            ->delete();

        return response()->json([]);
    }
}
