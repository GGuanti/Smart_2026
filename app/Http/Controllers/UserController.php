<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
{
    // ------------------ regioni ------------------
    $regioni = DB::table('tab_costo_trasporto')
        ->select('regione')
        ->whereNotNull('regione')
        ->distinct()
        ->orderBy('regione')
        ->pluck('regione')
        ->values();

    // ------------------ utenti ------------------
    $users = User::query()
        ->select(['id','name','email','profilo','listino','trasporto','azienda','datiazienda','logo_path'])
        ->orderBy('name')
        ->get();

    // ------------------ pivot TipoDoc dinamica ------------------
    $tipiDoc = DB::table('tab_ordine')
        ->select('TipoDoc')
        ->whereNotNull('TipoDoc')
        ->distinct()
        ->orderBy('TipoDoc')
        ->pluck('TipoDoc')
        ->values();

    // Se non esistono ordini/documenti, evita query inutili
    $preventiviPivot = collect();

    if ($tipiDoc->count() > 0) {

        // colonne dinamiche: SUM(CASE WHEN TipoDoc='X' THEN 1 ELSE 0 END) AS `X`
        $selects = [
            "users.name as name",
        ];

        foreach ($tipiDoc as $td) {
            // alias "safe" per MySQL (evita backtick); NON trasformo spazi per ora
            $alias = str_replace('`', '', (string) $td);

            // escape semplice per stringa SQL
            $tdEsc = addslashes((string) $td);

            $selects[] = "SUM(CASE WHEN tab_ordine.TipoDoc = '{$tdEsc}' THEN 1 ELSE 0 END) as `{$alias}`";
        }

        $selects[] = "COUNT(tab_ordine.Nordine) as Totale";

        $preventiviPivot = DB::table('tab_ordine')
            ->join('users', 'tab_ordine.user_id', '=', 'users.id')
            ->selectRaw(implode(",\n", $selects))
            ->groupBy('users.name')
            ->orderBy('users.name')
            ->get();
    }

    return Inertia::render('Users/Index', [
        'users' => $users,
        'regioniTrasporto' => $regioni,

        // ✅ nuove props per la tabella pivot
        'tipiDoc' => $tipiDoc,
        'preventiviPivot' => $preventiviPivot,
    ]);
}


    public function store(Request $request)
{
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6',
        'profilo' => 'required|string',
        'azienda' => 'nullable|string',
        'listino' => 'nullable|string',
        'trasporto' => 'nullable|string',
        'datiazienda' => 'nullable|string',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    // 1️⃣ crea utente PRIMA
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => bcrypt($data['password']),
        'profilo' => $data['profilo'],
        'azienda' => $data['azienda'] ?? null,
        'listino' => $data['listino'] ?? null,
        'trasporto' => $data['trasporto'] ?? null,
        'datiazienda' => $data['datiazienda'] ?? null,
    ]);

    // 2️⃣ ORA $user esiste → salva logo
    if ($request->hasFile('logo')) {

        $file = $request->file('logo');
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';

        $fileName = $user->id . '.' . $ext;
        $destination = public_path('Foto/Utente');

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $fileName);

        $user->logo_path = 'Foto/Utente/' . $fileName;
        $user->save();
    }

    return redirect()->back();
}

public function update(Request $request, User $user)
{
    $data = $request->validate([
        'name' => ['required','string','max:255'],
        'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
        'profilo' => ['required','string','max:50'],
        'password' => ['nullable','string','min:6'],
        'azienda' => ['nullable','string','max:255'],
        'listino' => ['nullable','string','max:50'],
        'trasporto' => ['nullable','string','max:50'],
        'datiazienda' => ['nullable','string'],

        // ✅ IMPORTANTE: se usi move() su public_path, evita mime guessing aggressivo
        // puoi anche lasciare image+mimes, ma se ti ha già dato errore temp file, meglio così:
        'logo' => ['nullable','file','max:2048'],

        'remove_logo' => ['nullable','boolean'],
    ]);

    // ------------------ campi base ------------------
    $user->name = $data['name'];
    $user->email = $data['email'];
    $user->profilo = $data['profilo'];
    $user->azienda = $data['azienda'] ?? null;
    $user->listino = $data['listino'] ?? null;
    $user->trasporto = $data['trasporto'] ?? null;
    $user->datiazienda = $data['datiazienda'] ?? null;

    if (!empty($data['password'])) {
        $user->password = bcrypt($data['password']);
    }

    // ------------------ gestione logo (SOLO public/) ------------------
    $destination = public_path('Foto/Utente');
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    // Rimuovi logo
    if ($request->boolean('remove_logo')) {
        if ($user->logo_path && file_exists(public_path($user->logo_path))) {
            unlink(public_path($user->logo_path));
        }
        $user->logo_path = null;
    }

    // Nuovo logo
    if ($request->hasFile('logo')) {
        $file = $request->file('logo');

        if (!$file->isValid()) {
            return back()->withErrors(['logo' => 'Upload logo non valido']);
        }

        // cancella precedente
        if ($user->logo_path && file_exists(public_path($user->logo_path))) {
            unlink(public_path($user->logo_path));
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = $user->id . '.' . $ext;

        $file->move($destination, $fileName);

        $user->logo_path = 'Foto/Utente/' . $fileName;
    }

    $user->save();

    return redirect()->back();
}


public function destroy(User $user)
{
    $user->delete();

    return redirect()->back()->with('success', 'Utente eliminato con successo.');
}

}
