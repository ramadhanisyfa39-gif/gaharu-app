<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $data = Customer::all();
        return view('customer.index', compact('data'));
    }

    public function create()
    {
        return view('customer.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'jenis' => 'required|in:Internal,Reseller,Horeca,Corporate',
            'no_hp' => 'required',
            'alamat' => 'required',
        ]);

        Customer::create($request->all());

        return redirect()->route('customer.index')
            ->with('success', 'Data berhasil ditambahkan');
    }

    public function edit($id)
    {
        $data = Customer::findOrFail($id);
        return view('customer.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'jenis' => 'required|in:Internal,Reseller,Horeca,Corporate',
            'no_hp' => 'required',
            'alamat' => 'required',
        ]);

        $data = Customer::findOrFail($id);
        $data->update($request->all());

        return redirect()->route('customer.index')
            ->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        $data = Customer::findOrFail($id);
        $data->delete();

        return redirect()->route('customer.index')
            ->with('success', 'Data berhasil dihapus');
    }
}