<?php

namespace Dinithoshan\Racelab\Http\Controllers;

use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('racelab::app');
    }
}