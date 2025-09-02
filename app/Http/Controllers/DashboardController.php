<?php
namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function usersSummary()
    {
        return Inertia::render('Dashboard/UsersSummary', [
            'totalUsers' => User::count(),

        ]);
    }
}
