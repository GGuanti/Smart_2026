<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Appointment;
use Inertia\Inertia;
use Carbon\Carbon;

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
            'DataInizio' => $request->get('DataInizio'), // da query string
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
            'status' => 'required|in:scheduled,active,completed,cancelled',
            'StatoMagazzino' => 'required|in:Magazzino,In arrivo,Arrivato,In ritardo',
            'Nordine' => 'required|string|max:50',
            'Riferimento' => 'nullable|string|max:255',
            'Colore' => 'nullable|string|max:30',
            'Pezzi' => 'nullable|integer|min:0',
            'T'  => 'nullable|boolean',
            'Tz' => 'nullable|boolean',
            'TL' => 'nullable|boolean',
            'A'  => 'nullable|boolean',
            'C'  => 'nullable|boolean',
            'L'  => 'nullable|boolean',
            'Annotazioni' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        Appointment::create($validated);

        return redirect()->route('appointments.calendar')->with('success', 'Appuntamento salvato con successo.');
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
            'status' => 'required|in:scheduled,active,completed,cancelled',
            'StatoMagazzino' => 'required|in:Magazzino,In arrivo,Arrivato,In ritardo',
            'Nordine' => 'required|string|max:50',
            'Riferimento' => 'nullable|string|max:255',
            'Colore' => 'nullable|string|max:30',
            'Pezzi' => 'nullable|integer|min:0',
            'T'  => 'nullable|boolean',
            'Tz' => 'nullable|boolean',
            'TL' => 'nullable|boolean',
            'A'  => 'nullable|boolean',
            'C'  => 'nullable|boolean',
            'L'  => 'nullable|boolean',
            'Annotazioni' => 'nullable|string|max:255',
        ]);

        // Normalizza datetime in formato MySQL (se ti serve uniformità)
        $validated['DataInizio'] = Carbon::parse($validated['DataInizio'])->format('Y-m-d');
        $validated['DataFine'] = Carbon::parse($validated['DataFine'])->format('Y-m-d');
        $validated['DataConferma'] = Carbon::parse($validated['DataConferma'])->format('Y-m-d');
        $validated['DataConsegna'] = Carbon::parse($validated['DataConsegna'])->format('Y-m-d');


        // Checkbox: assicurati che siano veri boolean (anche se non arrivano in request)
        foreach (['T','Tz','TL','A','C','L'] as $k) {
            $validated[$k] = $request->boolean($k);
        }

        $appointment->update($validated);

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
        'start' => ['required', 'date'],
        'end'   => ['nullable', 'date'],
    ]);

    $start = Carbon::parse($validated['start'])->format('Y-m-d H:i:s');

    // ✅ Se end non esiste → uguale a start
    $end = !empty($validated['end'])
        ? Carbon::parse($validated['end'])->format('Y-m-d H:i:s')
        : $start;

    $appointment->update([
        'DataInizio' => $start,
        'DataFine'   => $end,
    ]);

    //return response()->json(['success' => true]);
}
}
