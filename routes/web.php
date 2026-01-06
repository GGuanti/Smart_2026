<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\{
    ProfileController,
    ProfileTabbedController,
    DashboardController,
    UserController,
    UserPreferenceController,

    OrdineController,
    OrdineReportController,
    PreventivoController,

    ListinoController,
    ArticoloController,

    AppointmentController,
    VisiteMedicheController,

    AnagraficaController,
    AttivitaController,
    ProgettoController,
    CorsoController,

    GiornateController,
    VistaGiornateController,

    NotaSpesaController,
    VistaNotaspesaController,

    ContrattiController,

    AllegatiController,
    DropboxOAuthController,

    ReportJobController,
    ReportGiornateController,

    PrintController,

    Auth\AuthenticatedSessionController,
};

/*
|--------------------------------------------------------------------------
| Home / Redirect iniziale
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {

        $map = [
            'Isomax' => '/ordini',
            'Nurith' => '/calendar',
        ];

        return redirect($map[auth()->user()->profilo] ?? '/dashboard');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Guest (Login)
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'usersSummary'])
        ->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Profilo
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/profilo/tabbed', [ProfileTabbedController::class, 'index'])
        ->name('profilo.tabbed');
});

/*
|--------------------------------------------------------------------------
| Ordini
|--------------------------------------------------------------------------
*/


Route::middleware(['auth', 'isomax'])->group(function () {
    Route::resource('ordini', OrdineController::class);
    Route::post('/ordini/{id}/copia', [OrdineController::class, 'copia'])
        ->name('ordini.copia');
    // Report ordine
    Route::get('/ordini/{id}/conferma', [OrdineReportController::class, 'conferma'])
        ->name('ordini.report.conferma');
    Route::post('/ordini/{ordine}/email/conferma', [OrdineReportController::class, 'emailConferma'])
        ->name('ordini.email.conferma');
    // Preventivi (righe ordine)
    Route::get('ordini/{ordine}/preventivi/create', [PreventivoController::class, 'create'])
        ->name('preventivi.create');
    Route::post('ordini/{ordine}/preventivi', [PreventivoController::class, 'store'])
        ->name('preventivi.store');
    Route::get('ordini/{ordine}/preventivi/{elemento}/edit', [PreventivoController::class, 'edit'])
        ->name('preventivi.edit');
    Route::put('ordini/{ordine}/preventivi/{elemento}', [PreventivoController::class, 'update'])
        ->name('preventivi.update');
    Route::delete('ordini/{ordine}/preventivi/{elemento}', [PreventivoController::class, 'destroy'])
        ->name('preventivi.destroy');
    Route::get('/listini/crea', [ListinoController::class, 'create'])->name('listini.create');
    Route::post('/listini', [ListinoController::class, 'store'])->name('listini.store');
    Route::get('/listini/{listino}/modifica', [ListinoController::class, 'edit'])->name('listini.edit');
    Route::put('/listini/{listino}', [ListinoController::class, 'update'])->name('listini.update');
    Route::put('/listini/{listino}/valpred', [ListinoController::class, 'saveValPred'])
        ->name('listini.valpred.save');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('articoli', ArticoloController::class);
});

/*
|--------------------------------------------------------------------------
| Listini
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Appuntamenti / Calendario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'nurith'])->group(function () {
    Route::get('/calendar', [AppointmentController::class, 'index'])->name('appointments.calendar');
    Route::post('/appointments/import-all', [AppointmentController::class, 'ImportaDati'])
        ->name('appointments.ImportaDati');
    Route::post('/appointments/import-csv', [AppointmentController::class, 'importAppointmentsCsv'])
        ->name('appointments.import.csv');
    Route::resource('appointments', AppointmentController::class)
        ->except(['show']);
});

/*
|--------------------------------------------------------------------------
| Anagrafica & Moduli collegati
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::resource('anagrafica', AnagraficaController::class);
    Route::get('/anagrafica/list', [AnagraficaController::class, 'list']);
    Route::resource('attivita', AttivitaController::class);
    Route::resource('progetti', ProgettoController::class);
    Route::resource('corsi', CorsoController::class)->except(['show']);
});

/*
|--------------------------------------------------------------------------
| Giornate
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/vistagiornate', [VistaGiornateController::class, 'index'])
        ->name('vistagiornate.index');
    Route::delete('/giornate/{id}', [GiornateController::class, 'destroy'])
        ->name('giornate.destroy');
    Route::get('/giornate/anteprima', [GiornateController::class, 'anteprima'])
        ->name('giornate.anteprima');
    Route::get('/giornate/stampa-pdf', [GiornateController::class, 'stampaPDF'])
        ->name('giornate.stampaPDF');
});

/*
|--------------------------------------------------------------------------
| Note spese
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::resource('note-spese', NotaSpesaController::class)->except(['show', 'create', 'edit']);
});

/*
|--------------------------------------------------------------------------
| Contratti
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::resource('contratti', ContrattiController::class)
        ->except(['create', 'edit', 'show']);
    Route::get('/contratti/{id}/report', [ContrattiController::class, 'generaPdf'])
        ->name('contratti.pdf');
});

/*
|--------------------------------------------------------------------------
| Allegati
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/allegati/{idProg}', [AllegatiController::class, 'index'])
        ->whereNumber('idProg')
        ->name('allegati.index');
    Route::post('/allegati/{idProg}', [AllegatiController::class, 'store'])
        ->whereNumber('idProg')
        ->name('allegati.store');
    Route::get('/allegato/{allegato}', [AllegatiController::class, 'show'])
        ->whereNumber('allegato')
        ->name('allegati.show');
    Route::delete('/allegato/{allegato}', [AllegatiController::class, 'destroy'])
        ->whereNumber('allegato')
        ->name('allegati.destroy');
});

/*
|--------------------------------------------------------------------------
| Report
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/report', [ReportJobController::class, 'index'])->name('report.index');
    Route::post('/report/genera', [ReportJobController::class, 'genera'])->name('report.genera');
    Route::get('/report/check/{id}', [ReportJobController::class, 'check'])->name('report.check');
    Route::get('/report/giornate/preview', [ReportGiornateController::class, 'preview'])
        ->name('report.giornate.preview');
    Route::post('/report/giornate/email', [ReportGiornateController::class, 'generaEInviaEmail'])
        ->name('report.giornate.email');
});

/*
|--------------------------------------------------------------------------
| Dropbox OAuth
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/oauth/dropbox/redirect', [DropboxOAuthController::class, 'redirect'])
        ->name('dropbox.redirect');
    Route::get('/oauth/dropbox/callback', [DropboxOAuthController::class, 'callback'])
        ->name('dropbox.callback');
});

/*
|--------------------------------------------------------------------------
| Utenti / Admin
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::get('/admin', fn () => Inertia::render('Admin'))->name('admin');
});

/*
|--------------------------------------------------------------------------
| Auth scaffolding
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
