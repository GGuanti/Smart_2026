<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    // Mostra il calendario solo per l'utente loggato
    public function index()
    {
        $appointments = Appointment::with('user')
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('Appointments/Calendar', [
            'appointments' => $appointments,
        ]);
    }

    // Mostra il calendario completo (admin)
    public function calendar()
    {
        $appointments = Appointment::where('user_id', auth()->id())
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'start' => $appointment->DataInizio, // <-- qui
                    'end' => $appointment->DataFine,     // <-- qui
                    'status' => $appointment->status,
                ];
            });

        return Inertia::render('Appointments/Calendar', [
            'appointments' => $appointments,
        ]);
    }

    // Form per creare un appuntamento
    public function create(Request $request)
    {
        return Inertia::render('Appointments/Create', [
            'prefill' => [
                'DataInizio' => $request->get('DataInizio'),
                'DataConsegna' => $request->get('DataConsegna'),
                'DataConferma' => $request->get('DataConsegna'),
            ],
            // altre props...
        ]);
    }


    // Salva un nuovo appuntamento
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'DataInizio' => 'required|date',
            'DataFine' => 'required|date|after_or_equal:DataInizio',

            'DataConferma' => 'required|date',
            'DataConsegna' => 'required|date|after_or_equal:DataConferma',

            // ✅ i tuoi 5 stati
            'status' => 'required|in:Da Pianificare,Pianificato,Completato,Sospeso,Cancellato',

            'StatoMagazzino' => 'required|in:Magazzino,Ordinato,Arrivato,In ritardo',

            'Nordine' => 'required|integer|min:1',
            'Riferimento' => 'nullable|string|max:255',
            'Annotazioni' => 'nullable|string|max:255',

            // ✅ RIGHE
            'items' => ['nullable', 'array'],
            'items.*.prodotto' => ['nullable', 'string', 'max:255'],
            'items.*.colore' => ['nullable', 'string', 'max:255'],
            'items.*.descrizione' => ['nullable', 'string'],
            'items.*.Lotto' => ['nullable', 'string'],
            'items.*.pezzi' => ['nullable', 'integer', 'min:0'],
            'items.*.taglio' => ['nullable', 'boolean'],
            'items.*.Accessori' => ['nullable','boolean'],
            'items.*.Coprifili' => ['nullable','boolean'],
            'items.*.Fermavetri' => ['nullable','boolean'],
            'items.*.assemblaggio' => ['nullable', 'boolean'],
            'items.*.comandi' => ['nullable', 'boolean'],
            'items.*.taglio_zoccolo' => ['nullable', 'boolean'],
            'items.*.taglio_lamelle' => ['nullable', 'boolean'],
            'items.*.montaggio_lamelle' => ['nullable', 'boolean'],
            'items.*.Ferramenta' => ['nullable', 'boolean'],
            'items.*.Fermavetri' => ['nullable', 'boolean'],
            'items.*.Vetratura' => ['nullable', 'boolean'],
            'items.*.OrdineVetri' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($validated) {

            // ✅ prodotti unici (da items)
            $prodotti = collect($validated['items'] ?? [])
                ->pluck('prodotto')
                ->filter()
                ->unique()
                ->values()
                ->all(); // es: ["IA","PA","SC"]

            // ✅ normalizza date (Y-m-d)
            $validated['DataInizio']    = Carbon::parse($validated['DataInizio'])->format('Y-m-d');
            $validated['DataFine']      = Carbon::parse($validated['DataFine'])->format('Y-m-d');
            $validated['DataConferma']  = Carbon::parse($validated['DataConferma'])->format('Y-m-d');
            $validated['DataConsegna']  = Carbon::parse($validated['DataConsegna'])->format('Y-m-d');

            // ✅ separo righe
            $items = $validated['items'] ?? [];
            unset($validated['items']);
            $validated['user_id'] = auth()->id();
            // ✅ crea testata
            $appointment = Appointment::create($validated);

            // ✅ crea righe
            $mapped = collect($items)->map(function ($it) use ($appointment) {
                return [
                    // se usi FK su Nordine (come nel tuo update)
                    'Nordine' => $appointment->Nordine,

                    'Prodotto' => $it['prodotto'] ?? null,
                    'Descrizione' => $it['descrizione'] ?? null,
                    'Lotto' => $it['Lotto'] ?? null,

                    'Colore' => $it['colore'] ?? null,
                    'Pezzi' => (int)($it['pezzi'] ?? 0),

                    'Taglio' => (bool)($it['taglio'] ?? false),
                    'Assemblaggio' => (bool)($it['assemblaggio'] ?? false),
                    'Comandi' => (bool)($it['comandi'] ?? false),

                    'TaglioZoccolo' => (bool)($it['taglio_zoccolo'] ?? false),
                    'TaglioLamelle' => (bool)($it['taglio_lamelle'] ?? false),
                    'MontaggioLamelle' => (bool)($it['montaggio_lamelle'] ?? false),

                    'Ferramenta' => (bool)($it['Ferramenta'] ?? false),
                    'Vetratura' => (bool)($it['Vetratura'] ?? false),
                    'Accessori' => (bool)($it['Accessori'] ?? false),
                    'Coprifili' => (bool)($it['Coprifili'] ?? false),
                    'Fermavetri' => (bool)($it['Fermavetri'] ?? false),
                    'OrdineVetri' => (bool)($it['OrdineVetri'] ?? false),

                ];
            })->all();

            if (!empty($mapped)) {
                $appointment->items()->createMany($mapped);
            }

            // ✅ calcolo pezzi e salvo prodotto[] sulla testata
            $sum = $appointment->items()->sum('Pezzi');

            $appointment->update([
                'Pezzi' => (int) $sum,
                'Prodotto' => $prodotti,
            ]);

            // redirect dove preferisci:
            return redirect()
                ->route('appointments.edit', $appointment->id)
                ->with('success', 'Ordine creato con successo.');
            // oppure:
            // ->route('appointments.calendar')
        });
    }

    // Visualizza un singolo appuntamento
    public function show(Appointment $appointment)
    {
        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
        ]);
    }

    // Form di modifica
    public function edit(Appointment $appointment)
    {


        $appointment->load('items'); // ✅ fondamentale


        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
        ]);
    }

    // Aggiorna un appuntamento
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'DataInizio' => 'required|date',
            'DataFine' => 'required|date|after_or_equal:DataInizio',
            'DataConferma' => 'required|date',
            'DataConsegna' => 'required|date|after_or_equal:DataConferma',
            'status' => 'required',
            'StatoMagazzino' => 'required|in:Magazzino,Ordinato,Arrivato,In ritardo',
            'Nordine' => 'required|integer|min:1',
            'Riferimento' => 'nullable|string|max:255',
            'Annotazioni' => 'nullable|string|max:255',
            // ✅ RIGHE
            'items' => ['nullable', 'array'],
            'items.*.prodotto' => ['nullable', 'string', 'max:255'],
            'items.*.colore' => ['nullable', 'string', 'max:255'],
            'items.*.descrizione' => ['nullable', 'string'],
            'items.*.Lotto' => ['nullable', 'string'],
            'items.*.pezzi' => ['nullable', 'integer', 'min:0'],
            'items.*.taglio' => ['nullable', 'boolean'],
            'items.*.assemblaggio' => ['nullable', 'boolean'],
            'items.*.comandi' => ['nullable', 'boolean'],
            'items.*.taglio_zoccolo' => ['nullable', 'boolean'],
            'items.*.taglio_lamelle' => ['nullable', 'boolean'],
            'items.*.montaggio_lamelle' => ['nullable', 'boolean'],
            'items.*.Ferramenta' => ['nullable', 'boolean'],
            'items.*.Fermavetri' => ['nullable', 'boolean'],
            'items.*.Vetratura' => ['nullable', 'boolean'],
            'items.*.Coprifili' => ['nullable', 'boolean'],
            'items.*.Accessori' => ['nullable', 'boolean'],
            'items.*.OrdineVetri' => ['nullable', 'boolean'],

        ]);
        $prodotti = collect($validated['items'] ?? [])
            ->pluck('prodotto')
            ->filter()                 // rimuove null/""
            ->unique()
            ->values()
            ->all();                   // es: ["IA","PA","SC"]

        // Normalizza datetime in formato MySQL (se ti serve uniformità)
        $validated['DataInizio'] = Carbon::parse($validated['DataInizio'])->format('Y-m-d');
        $validated['DataFine'] = Carbon::parse($validated['DataFine'])->format('Y-m-d');
        $validated['DataConferma'] = Carbon::parse($validated['DataConferma'])->format('Y-m-d');
        $validated['DataConsegna'] = Carbon::parse($validated['DataConsegna'])->format('Y-m-d');


        // ✅ separo righe
        $items = $validated['items'];
        unset($validated['items']);

        // ✅ aggiorno testata
        $appointment->update($validated);

        // ✅ riscrivo righe (semplice e robusto)
        $appointment->items()->delete();

        $mapped = collect($items)->map(function ($it) use ($appointment) {
            return [
                // se usi FK su Nordine:
                'Nordine' => $appointment->Nordine,
                'Prodotto' => $it['prodotto'],
                'Descrizione' => $it['descrizione'] ?? null,
                'Lotto' => $it['Lotto'] ?? null,

                'Colore' => $it['colore'] ?? null,
                'Pezzi' => (int)($it['pezzi'] ?? 0),
                'Taglio' => (bool)($it['taglio'] ?? false),
                'Assemblaggio' => (bool)($it['assemblaggio'] ?? false),
                'Comandi' => (bool)($it['comandi'] ?? false),
                'TaglioZoccolo' => (bool)($it['taglio_zoccolo'] ?? false),
                'TaglioLamelle' => (bool)($it['taglio_lamelle'] ?? false),
                'MontaggioLamelle' => (bool)($it['montaggio_lamelle'] ?? false),
                'Ferramenta' => (bool)($it['Ferramenta'] ?? false),
                'Fermavetri' => (bool)($it['Fermavetri'] ?? false),
                'Vetratura' => (bool)($it['Vetratura'] ?? false),
                'Coprifili' => (bool)($it['Coprifili'] ?? false),
                'Accessori' => (bool)($it['Accessori'] ?? false),
                'OrdineVetri' => (bool)($it['OrdineVetri'] ?? false),
            ];
        })->all();

        $appointment->items()->createMany($mapped);
        $sum = $appointment->items()->sum('Pezzi');

        $appointment->update([
            'Pezzi' => (int) $sum,
            'Prodotto' => $prodotti,
        ]);
        // return redirect()->route('appointments.calendar')->with('success', 'Ordine aggiornato con successo.');

        //  return redirect()->route('appointments.calendar')->with('success', 'Appuntamento aggiornato');
    }



    // Elimina un appuntamento
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.calendar')->with('success', 'Appuntamento eliminato.');
    }

    // Sposta evento dal calendario (drag & drop)
    public function move(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'start'  => ['required'],
            'allDay' => ['required', 'boolean'],
        ]);

        $tz = config('app.timezone', 'Europe/Rome');

        if ($validated['allDay']) {
            // 🔑 allDay → solo data, niente end
            $start = Carbon::parse($validated['start'])
                ->setTimezone('Europe/Rome')
                ->startOfDay();

            $appointment->update([
                'DataInizio' => $start->toDateTimeString(),
                'DataFine'   => $start->copy()->endOfDay(),
            ]);
        } else {
            // evento con orario
            $start = Carbon::parse($validated['start'])->setTimezone('Europe/Rome');
            $end   = Carbon::parse($request->end)->setTimezone('Europe/Rome');

            $appointment->update([
                'DataInizio' => $start->toDateTimeString(),
                'DataFine'   => $end->toDateTimeString(),
            ]);
        }

        // return response()->json(['success' => true]);
    }
}
