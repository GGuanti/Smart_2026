<?php

namespace App\Http\Controllers;

use App\Models\CalendarioIsomax;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CalendarioControllerIsomax extends Controller
{
    public function index()
    {
  $user = Auth::user();

    $adminEmails = [
        'info@isomaxporte.com',
        'm.mecca@isomaxporte.com',
        'gguanti@gmail.com',
        'giuseppe.mecca@isomaxporte.com',
    ];

    $appointments = in_array($user->email, $adminEmails)
        ? CalendarioIsomax::all()
        : CalendarioIsomax::where('user_id', $user->id)->get();

        return Inertia::render('Isomax/CalendarIsomax', [
            'appointments' => $appointments,
        ]);
    }

    public function calendar()
    {
        $events = CalendarioIsomax::where('user_id', Auth::id())
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start' => $e->DataInizio,
                'end' => $e->DataFine,
                'status' => $e->status,
            ]);

        return Inertia::render('Isomax/CalendarIsomax', [
            'appointments' => $events,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Isomax/Create', [
            'prefill' => [
                'DataInizio'   => $request->get('DataInizio'),
                'DataConsegna' => $request->get('DataConsegna'),
                'DataConferma' => $request->get('DataConsegna'),
            ],
        ]);
    }

    public function edit(CalendarioIsomax $evento)
    {
        $evento->load('items');

        return Inertia::render('Isomax/Edit', [
            'appointment' => $evento,
        ]);
    }


public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'DataInizio' => 'required|date',
            'DataFine' => 'nullable|date',
            'DataConsegna' => 'nullable|date',
            'status' => 'nullable|string',
            'StatoMagazzino' => 'nullable|string',
            'Nordine' => 'nullable|integer',
            'Riferimento' => 'nullable|string',
            'items' => 'array',
        ]);
            $validated['user_id'] = Auth::id();

        return DB::transaction(function () use ($validated, $request) {

            // ✅ crea evento
            $evento = CalendarioIsomax::create($validated);

            // ✅ gestisci prodotti
            $this->syncItems($evento, $request->items ?? []);

            return back();
        });
    }

    /**
     * UPDATE
     */
    public function update(Request $request, $id)
{
    $evento = CalendarioIsomax::findOrFail($id);

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'DataInizio' => 'required|date',
        'DataFine' => 'nullable|date',
        'DataConsegna' => 'nullable|date',
        'status' => 'nullable|string',
        'StatoMagazzino' => 'nullable|string',
        'Nordine' => 'nullable|integer',
        'Riferimento' => 'nullable|string',
        'Annotazioni' => 'nullable|string',
        'description' => 'nullable|string',
        'items' => 'array',
    ]);
 $validated['user_id'] = Auth::id();
    $items = $validated['items'] ?? [];
    unset($validated['items']); // 🔥 fondamentale

    $evento->update($validated);

    $this->syncItems($evento, $items);

    return back();
}

    /**
     * 🔥 MOVE (drag & drop calendario)
     */
    public function move(Request $request, $id)
    {
        $evento = CalendarioIsomax::findOrFail($id);

        $evento->update([
            'DataInizio' => $request->start,
            'DataFine'   => $request->end ?? $request->start,
        ]);

      //  return response()->json(['success' => true]);
    }

    /**
     * 🔥 SYNC ITEMS (CUORE DEL SISTEMA)
     */
    private function syncItems($evento, $items)
    {
        // ✅ cancella TUTTI i vecchi
        $evento->items()->delete();

        // ✅ filtra righe vuote (BUG RISOLTO QUI)
        $cleanItems = collect($items)
            ->filter(function ($it) {
                return !empty($it['Prodotto']); // 🔥 blocca {} e null
            })
            ->map(function ($it) use ($evento) {
                return [
                    'calendario_id' => $evento->id,
                    'Prodotto' => $it['Prodotto'],
                    'Pezzi' => (int) ($it['Pezzi'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

        // ✅ inserisci solo se ci sono dati veri
        if ($cleanItems->isNotEmpty()) {
            DB::table('calendario_prodotti')->insert($cleanItems->toArray());
        }

        // ✅ aggiorna totale pezzi
        $tot = $cleanItems->sum('Pezzi');
        $evento->update(['Pezzi' => $tot]);
    }


    public function destroy($id)
    {
        $evento = CalendarioIsomax::findOrFail($id);

        DB::transaction(function () use ($evento) {
            DB::table('calendario_prodotti')
                ->where('Nordine', $evento->Nordine)
                ->delete();

            $evento->delete();
        });

        return redirect()->route('Isomax.CalendarIsomax');
    }
public function move2(Request $request, $id)
{
    $evento = CalendarioIsomax::findOrFail($id);

    $evento->update([
        'DataInizio' => $request->start,
        'DataFine'   => $request->end ?? $request->start,
    ]);

    return response()->json([
        'success' => true
    ]);
}
    public function move1(Request $request, $id)
    {
        $evento = CalendarioIsomax::findOrFail($id);

        $start = Carbon::parse($request->start);
        $end   = $request->end ? Carbon::parse($request->end) : null;

        if ($request->allDay) {
            $evento->update([
                'DataInizio' => $start->startOfDay(),
                'DataFine'   => $start->copy()->endOfDay(),
            ]);
        } else {
            $evento->update([
                'DataInizio' => $start,
                'DataFine'   => $end,
            ]);
        }
    }
}
