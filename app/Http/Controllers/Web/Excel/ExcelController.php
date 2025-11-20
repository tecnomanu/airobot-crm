<?php

namespace App\Http\Controllers\Web\Excel;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ExcelController extends Controller
{
    /**
     * Mostrar la vista principal de Excel/Spreadsheet
     */
    public function index(): Response
    {
        return Inertia::render('Excel/Index');
    }
}

