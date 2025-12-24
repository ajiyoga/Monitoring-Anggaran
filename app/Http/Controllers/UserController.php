<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Langsung arahkan ke halaman Program Kerja
        return redirect()->route('programkerja.index');
    }
}
