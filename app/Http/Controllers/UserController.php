<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::all()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profilo' => $user->profilo, // deve esistere nella tabella users!
                    'listino' => $user->listino, // deve esistere nella tabella users!
                    'trasporto' => $user->trasporto, // deve esistere nella tabella users!
                    'azienda' => $user->azienda, // deve esistere nella tabella users!
                ];
            }),
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email|unique:users',
        'profilo' => 'required|in:admin,user',
        'password' => 'required|min:6',
        'azienda' => 'nullable|string',
        'listino' => 'nullable|string',
        'trasporto' => 'nullable|string',
    ]);

    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'profilo' => $request->profilo,
        'password' => bcrypt($request->password),
        'listino' => $request->listino,
        'azienda' => $request->azienda,
        'trasporto' => $request->trasporto,

    ]);

    return redirect()->back();
}
public function destroy(User $user)
{
    $user->delete();

    return redirect()->back()->with('success', 'Utente eliminato con successo.');
}
    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);
    $user->update([
        'name' => $request->name,
        'email' => $request->email,
        'profilo' => $request->profilo,
        'listino' => $request->listino,
        'azienda' => $request->azienda,
        'trasporto' => $request->trasporto,

    ]);

    return redirect()->back(); // o a una rotta Inertia se preferisci
}
}
