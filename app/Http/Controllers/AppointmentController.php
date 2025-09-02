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
            'DataFine' => 'nullable|date|after_or_equal:DataInizio',
            'status' => 'required|in:scheduled,completed,cancelled',
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
        $appointment->update([
            'title' => $request->title,
            'description' => $request->description,
            'DataInizio' => Carbon::parse($request->DataInizio)->format('Y-m-d H:i:s'),
            'DataFine' => $request->DataFine ? Carbon::parse($request->DataFine)->format('Y-m-d H:i:s') : null,
            'status' => $request->status,
        ]);

        return redirect()->route('appointments.calendar')->with('success', 'Appuntamento aggiornato');
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
        $appointment->update([
            'DataInizio' => \Carbon\Carbon::parse($request->start)->format('Y-m-d H:i:s'),
            'DataFine' => $request->end ? \Carbon\Carbon::parse($request->end)->format('Y-m-d H:i:s') : null,
        ]);

       // return response()->json(['success' => true]);
    }
}
