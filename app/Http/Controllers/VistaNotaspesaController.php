<?php

namespace App\Http\Controllers;

use App\Models\VistaNotaspesa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VistaNotaspesaController extends Controller
{
    public function index()
    {
        $data = VistaNotaspesa::all();

        return Inertia::render('NotaSpesa/Index', [
            'noteSpese' => $data,
        ]);
    }
}
