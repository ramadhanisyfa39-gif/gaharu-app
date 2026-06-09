<?php

namespace App\Http\Controllers;

use App\Models\StokGudangBatch;

class StokGudangBatchController extends Controller
{
    public function index()
    {
        $data = StokGudangBatch::with([

                'supplier',

                'barang',

                'gudang',

                'pembelian'

            ])
            ->orderByDesc('id')
            ->paginate(20);

        return view(
            'stok-gudang-batch.index',
            compact('data')
        );
    }
}