<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\{
    ProfileController,
    ClientController,
    TabOrdineController,
    AppointmentController,
    ArticoloController,
    TabellaGenericaController,
    AnagraficaController,
    ChatController,
    Api\ChatUserController,
    UserPreferenceController,
    UserController,
    DashboardController,
    Auth\AuthenticatedSessionController,
    CorsoController,
    ProgettoController,
    AttivitaController,
    ReportJobController,
    VistaNotaspesaController,
    NotaSpesaController,
    ContrattiController,
    GiornateController,
    ReportController,
    ReportGiornateController,
    VistaGiornateController,
    DropboxOAuthController,
    AllegatiController
};
use Illuminate\Http\Request;
// routes/web.php
use App\Http\Controllers\DisegniDXFController;

Route::middleware('auth')->group(function () {
    Route::get('/oauth/dropbox/redirect', [DropboxOAuthController::class, 'redirect'])
        ->name('dropbox.redirect');

    Route::get('/oauth/dropbox/callback', [DropboxOAuthController::class, 'callback'])
        ->name('dropbox.callback');
});




// LISTA per progetto (idProg)
Route::get('/allegati/{idProg}', [AllegatiController::class, 'index'])
    ->whereNumber('idProg')
    ->name('allegati.index');

// UPLOAD su progetto (idProg)
Route::post('/allegati/{idProg}', [AllegatiController::class, 'store'])
    ->whereNumber('idProg')
    ->name('allegati.store');

// STREAM singolo allegato (id riga tabella)
Route::get('/allegato/{allegato}', [AllegatiController::class, 'show'])
    ->whereNumber('allegato')
    ->name('allegati.show');

// DELETE singolo allegato
Route::delete('/allegato/{allegato}', [AllegatiController::class, 'destroy'])
    ->whereNumber('allegato')
    ->name('allegati.destroy');

// Route::get('/allegati/{id}', [AllegatiController::class, 'show'])->name('allegati.show');
// Route::get   ('/allegati/open/{allegato}', [AllegatiController::class, 'show'])->name('allegati.show');
// Route::get   ('/allegati/{idProg}',               [AllegatiController::class, 'index'])->name('allegati.index');
// Route::post  ('/allegati/{idProg}',               [AllegatiController::class, 'store'])->name('allegati.store');
// Route::delete('/allegati/{allegato}',             [AllegatiController::class, 'destroy'])->name('allegati.destroy');
// Route::get   ('/allegati/{allegato}/download',    [AllegatiController::class, 'download'])->name('allegati.download');




Route::get('/contratti/{id}/report', [ContrattiController::class, 'generaPdf'])->name('contratti.report');

// routes/web.php
Route::get('/_diag/test-mail', function () {
    \Mail::raw('Test OK da Cloud', fn($m) =>
        $m->to('gguanti@gmail.com')->subject('Prova SMTP'));
    return 'ok';
});


Route::get('/disegni/{id?}', [DisegniDXFController::class, 'show'])->name('disegni.show');
Route::post('/disegni', [DisegniDXFController::class, 'store'])->name('disegni.store');
Route::put('/disegni/{id}', [DisegniDXFController::class, 'update'])->name('disegni.update');

// opzionale, piccola API per recuperare solo il testo DXF
// Route::get('/api/disegni/{id}', [DisegniDXFController::class, 'apiShow'])->name('api.disegni.show');

Route::get('/dxf-test', function () {
    return Inertia::render('Drawing/DxfTest');
});
Route::get('/dxf/save-svg', [DxfController::class, 'saveSvg'])->name('dxf.saveSvg.get');
// Route::post('/dxf/save-svg', [DxfController::class, 'saveSvg'])->name('dxf.saveSvg'); // salva/crea PDF



Route::get('/report/giornate/preview', [ReportGiornateController::class, 'preview'])->name('report.giornate.preview');
Route::post('/report/giornate/email', [ReportGiornateController::class, 'generaEInviaEmail'])->name('report.giornate.email'); // ->middleware('auth');
Route::resource('professioni', \App\Http\Controllers\TabProfessioniController::class)->only(['index', 'store', 'update', 'destroy']);

Route::get('/professioni/ccnl', function (\Illuminate\Http\Request $request) {
    $professione = $request->query('professione');
    return \DB::table('tab_professioni')
        ->where('Professione', $professione)
        ->value('LivelloCCNL');
});

// Route::get('/report/giornate/preview', [ReportController::class, 'preview'])->name('report.giornate.preview');
// Route::post('/report/giornate/email', [ReportController::class, 'generaEInviaEmail']);


Route::get('/giornate/stampa-pdf', [GiornateController::class, 'stampaPDF'])->name('giornate.stampaPDF');
Route::get('/giornate/anteprima', [GiornateController::class, 'anteprima'])->name('giornate.anteprima');

Route::get('/giornate', [GiornateController::class, 'index'])->name('giornate.index'); // opzionale


// Route::get('/note-spese', [VistaNotaspesaController::class, 'index'])->name('note-spese.index');

Route::get('/contratti', [ContrattiController::class, 'index']);
Route::delete('/contratti/{id}', [ContrattiController::class, 'destroy']);
Route::put('/contratti/{id}', [ContrattiController::class, 'update']);
Route::post('/contratti', [ContrattiController::class, 'store'])->name('contratti.store');

Route::get('/note-spese', [NotaSpesaController::class, 'index']);
Route::post('/note-spese', [NotaSpesaController::class, 'store']);
Route::put('/note-spese/{id}', [NotaSpesaController::class, 'update']);
Route::delete('/note-spese/{id}', [NotaSpesaController::class, 'destroy']);


Route::get('/dashboard', [DashboardController::class, 'usersSummary'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

//Gestione report
Route::get('/report', [ReportJobController::class, 'index'])->name('report.index');
Route::post('/report/genera', [ReportJobController::class, 'genera'])->name('report.genera');
Route::get('/report/check/{id}', [ReportJobController::class, 'check'])->name('report.check');


//Salvataggio Colonne griglia
Route::middleware('auth')->group(function () {
    Route::get('/user/columns', [UserPreferenceController::class, 'get']);
    Route::post('/user/columns', [UserPreferenceController::class, 'save']);
    Route::get('/preferences', [UserPreferenceController::class, 'get']);
});
Route::middleware(['auth'])->group(function () {
    //    Route::post('/user/columns', [UserPreferenceController::class, 'save']);
});



Route::middleware(['auth'])->group(function () {
    Route::get('/anagrafica', [AnagraficaController::class, 'index'])->name('anagrafica.index');
    Route::get('/anagrafica/create', [AnagraficaController::class, 'create'])->name('anagrafica.create');
    Route::get('/anagrafica/list', [AnagraficaController::class, 'list']);
    // Form modifica
    Route::get('/anagrafica/{anagrafica}/edit', [AnagraficaController::class, 'edit'])->name('anagrafica.edit');
    Route::post('/user/columns/reset', [UserPreferenceController::class, 'reset']);

    Route::post('/anagrafica', [AnagraficaController::class, 'store'])->name('anagrafica.store');
    Route::put('/anagrafica/{anagrafica}', [AnagraficaController::class, 'update'])->name('anagrafica.update');
    Route::delete('/anagrafica/{anagrafica}', [AnagraficaController::class, 'destroy'])->name('anagrafica.destroy');


    Route::get('/corsi', [CorsoController::class, 'index']);
    Route::post('/corsi', [CorsoController::class, 'store']);
    Route::put('/corsi/{id}', [CorsoController::class, 'update']);
    Route::delete('/corsi/{id}', [CorsoController::class, 'destroy']);

    Route::get('/attivita', [AttivitaController::class, 'index']);
    Route::post('/attivita', [AttivitaController::class, 'store']);
    Route::put('/attivita/{id}', [AttivitaController::class, 'update']);
    Route::delete('/attivita/{id}', [AttivitaController::class, 'destroy']);
    Route::resource('attivita', AttivitaController::class);




    Route::get('/progetti',            [ProgettoController::class, 'index'])->name('progetti.index');
    Route::post('/progetti',            [ProgettoController::class, 'store'])->name('progetti.store');
    Route::get('/progetti/{id}/edit',  [ProgettoController::class, 'edit'])->name('progetti.edit');
    Route::put('/progetti/{id}', [ProgettoController::class, 'update'])
    ->where('id', '.*') // ⬅️ consente gli slash nel parametro
    ->name('progetti.update');// ⬅️ mancava
    Route::delete('/progetti/{id}',       [ProgettoController::class, 'destroy'])->name('progetti.destroy');


        // 🔎 Lista giornate dalla vista "vistagiornate" filtrata via query ?IdProg=...
        // Ziggy: route('vistagiornate.index', { IdProg: '...' }) -> /vistagiornate?IdProg=...
        Route::get('/vistagiornate', [VistaGiornateController::class, 'index'])
            ->name('vistagiornate.index');

        // 🗑️ Elimina una giornata (dalla tabella reale, NON dalla vista)
        // Ziggy: route('giornate.destroy', { id: <IdGiornate> })
        Route::delete('/giornate/{id}', [GiornateController::class, 'destroy'])
            ->whereNumber('id')
            ->name('giornate.destroy');


});




// Rotte protette da autenticazione
Route::middleware(['auth'])->group(function () {
    // Elenco (pagina con Tabulator)
    //    Route::get('/anagrafica', [AnagraficaController::class, 'index'])->name('anagrafica.index');

    // Dati JSON per Tabulator
    //Route::get('/anagrafica/list', [AnagraficaController::class, 'list'])->name('anagrafica.list');
    //    Route::get('/anagrafica/list', [AnagraficaController::class, 'list']);
    // Form creazione

    // Salvataggio nuovo
    //Route::post('/anagrafica', [AnagraficaController::class, 'store'])->name('anagrafica.store');

    // Aggiorna esistente
    //    Route::put('/anagrafica/{anagrafica}', [AnagraficaController::class, 'update'])->name('anagrafica.update');

    // Elimina
    //  Route::delete('/anagrafica/{anagrafica}', [AnagraficaController::class, 'destroy'])->name('anagrafica.destroy');
});


// 👤 Guest (Login)

Route::get('/', [AuthenticatedSessionController::class, 'create'])->middleware('guest');


Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::resource('users', UserController::class);
});

// Articoli
Route::get('/articoli', [ArticoloController::class, 'index'])->name('articoli.index');
Route::post('/articoli', [ArticoloController::class, 'store'])->name('articoli.store');
Route::put('/articoli/{articolo}', [ArticoloController::class, 'update'])->name('articoli.update');
Route::delete('/articoli/{articolo}', [ArticoloController::class, 'destroy'])->name('articoli.destroy');
Route::get('/articoli/export', [ArticoloController::class, 'export'])->name('articoli.export');
Route::get('/articoli/export-pdf', [ArticoloController::class, 'exportPdf'])->name('articoli.export.pdf');


// Appuntamenti



Route::middleware(['auth'])->group(function () {
    Route::get('/calendar', [AppointmentController::class, 'index'])->name('appointments.calendar');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::get('/appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
    Route::put('/appointments/{appointment}/move', [AppointmentController::class, 'move'])->name('appointments.move');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
});



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin', fn () => Inertia::render('Admin'))->name('admin');
});

require __DIR__ . '/auth.php';
