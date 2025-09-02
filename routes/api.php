<?php
use App\Http\Controllers\Api\ClientiSearchController;
Route::get('/clienti/search', [ClientiSearchController::class,'__invoke'])->name('api.clienti.search');
