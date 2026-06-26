<?php

namespace App\Http\Controllers;

use App\Models\StokGudang;

class ReportInventoryController extends Controller
{
    public function index()
    {
        return view(
            'reports.inventory.index'
        );
    }
}