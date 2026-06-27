<?php

namespace App\Http\Controllers;


 use App\Models\Contact;
 use App\Models\GridLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    /**
     * Elenco contatti
     */
    public function index(Request $request): Response
    {
        $query = Contact::query()
            ->with([
                'company:id,name'
            ]);

        /*
        |--------------------------------------------------------------------------
        | Ricerca
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filtri
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where('status', $request->status);
        }

        if ($request->filled('company_id')) {

            $query->where('company_id', $request->company_id);
        }

        /*
        |--------------------------------------------------------------------------
        | Ordinamento
        |--------------------------------------------------------------------------
        */

        $sortField = $request->get('sort_field', 'name');
        $sortDir   = $request->get('sort_dir', 'asc');

        $allowedSorts = [
            'name',
            'email',
            'phone',
            'city',
            'status',
            'value',
        ];

        if (! in_array($sortField, $allowedSorts)) {

            $sortField = 'name';
        }

        $query->orderBy($sortField, $sortDir);

        /*
        |--------------------------------------------------------------------------
        | Paginazione
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Layout salvato
        |--------------------------------------------------------------------------
        */

        $savedLayout = GridLayout::forUser(
            Auth::id(),
            'contatti'
        );

        return Inertia::render('Contacts/Index', [

            'queryName' => 'contatti',

            'rows' => $rows,

            'savedLayout' => $savedLayout,

            'filters' => [

                'search'     => $request->search,
                'status'     => $request->status,
                'company_id' => $request->company_id,
                'sort_field' => $sortField,
                'sort_dir'   => $sortDir,
            ],


        ]);
    }

    /**
     * Form creazione
     */
    public function create(): Response
    {
        return Inertia::render('Contacts/Form', [

            'contact' => null,

        ]);
    }

    /**
     * Salvataggio
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([

            'company_id' => ['nullable', 'exists:companies,id'],

            'name' => ['required', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],

            'phone' => ['nullable', 'string', 'max:255'],

            'status' => ['nullable', 'string', 'max:100'],

            'city' => ['nullable', 'string', 'max:255'],

            'value' => ['nullable', 'numeric'],
        ]);

        Contact::create($validated);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contatto creato correttamente.');
    }

    /**
     * Form modifica
     */
    public function edit(Contact $contact): Response
    {
        $contact->load([
            'company:id,name'
        ]);

        return Inertia::render('Contacts/Form', [

            'contact' => $contact,


        ]);
    }

    /**
     * Aggiornamento
     */
    public function update(
        Request $request,
        Contact $contact
    ): RedirectResponse {

        $validated = $request->validate([

            'company_id' => ['nullable', 'exists:companies,id'],

            'name' => ['required', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],

            'phone' => ['nullable', 'string', 'max:255'],

            'status' => ['nullable', 'string', 'max:100'],

            'city' => ['nullable', 'string', 'max:255'],

            'value' => ['nullable', 'numeric'],
        ]);

        $contact->update($validated);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contatto aggiornato correttamente.');
    }

    /**
     * Eliminazione
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contatto eliminato correttamente.');
    }
}
